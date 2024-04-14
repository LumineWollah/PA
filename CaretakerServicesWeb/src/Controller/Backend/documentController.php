<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class documentController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
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

        $request->getSession()->remove('document');
        $request->getSession()->remove('user');

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

        $request->getSession()->set('document', $document);
        $storedDocument = $document;
        
        $defaults = [
            'name' => $storedDocument['name'],
            'type' => $storedDocument['type'],
            'url' => $storedDocument['url'],
            'owner' => $storedDocument['owner']['id'],
        ];

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
        ->add("url", TextType::class, [
            "attr"=>[
                "placeholder"=>"URL",
            ],
            "required"=>false,
        ])
        ->add("owner", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Owner ID",
            ],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $data['owner'] = 'api/cs_users/'.$data['owner'];

                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_documents/'.$storedDocument['id'], [
                    'json' => $data,
                ]);

                //$response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('documentList');
            }      
            return $this->render('backend/document/editDocument.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }
}
