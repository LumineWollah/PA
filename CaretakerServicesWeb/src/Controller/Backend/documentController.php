<?php

namespace App\Controller\Backend;

use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\File;

class documentController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
    }

    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }

    #[Route('/admin-panel/document/list', name: 'documentList')]
    public function documentList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_documents', [
            'query' => [
                'page' => 1
            ]
        ]);
        
        $documentsList = $response->toArray();

        return $this->render('backend/document/documents.html.twig', [
            'documents' => $documentsList['hydra:member']
        ]);
    }
    
    #[Route('/admin-panel/document/delete', name: 'documentDelete')]
    public function documentDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_documents/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);

        return $this->redirectToRoute('documentList');
    }

    #[Route('/admin-panel/document/edit', name: 'documentEdit')]
    public function documentEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $documentData = $request->request->get('document');
        $document = json_decode($documentData, true);

        $storedDocument = $request->getSession()->get('documentId');

        if (!$storedDocument) {
            $request->getSession()->set('documentId', $document['id']);
        }
        
        try {
            $defaults = [
                'name' => $document['name'],
                'type' => $document['type'],
                'url' => $document['url'],
                'owner' => $document['owner']['id'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [ $user['firstname'].' '.$user['lastname'] => $user['id'] ];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
            "required"=>false,
        ])
        ->add("type", TextType::class, [
            "attr"=>[
                "placeholder"=>"Type",
            ], 
            "required"=>false,
        ])
        ->add("url", UrlType::class, [
            "attr"=>[
                "placeholder"=>"URL",
            ],
            "required"=>false,
        ])
        ->add("owner", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            
            $data['owner'] = 'api/cs_users/'.$data['owner'];

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $response = $client->request('PATCH', 'cs_documents/'.$storedDocument, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('documentId');

            return $this->redirectToRoute('documentList');
        }      
        return $this->render('backend/document/editDocument.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
    
    #[Route('/admin-panel/document/create', name: 'documentCreate')]
    public function documentCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [ $user['firstname'].' '.$user['lastname'] => $user['id'] ];
        }

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
        ])
        ->add("type", TextType::class, [
            "attr"=>[
                "placeholder"=>"Type",
            ], 
        ])
        ->add("url", FileType::class, [
            'constraints' => [
                new File([
                    'maxSize' => '10m',
                    'mimeTypesMessage' => 'Please upload a valid document',
                ])
            ],
        ])
        ->add("owner", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->getForm()->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $results = $this->amazonS3Client->insertObject($data['url']);
            $data['url'] = $results['link'];
            $data['owner'] = 'api/cs_users/'.$data['owner'];
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_documents', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('documentList');
        }

        return $this->render('backend/document/createDocument.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
}
