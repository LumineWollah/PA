<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class documentController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    private function checkUserRole(Request $request): bool
    {
        $roles = $request->getSession()->get('roles');
        return $roles !== null && in_array('ROLE_ADMIN', $roles);
    }

    #[Route('/admin-panel/document/list', name: 'documentList')]
    public function documentList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_documents', [
            'query' => [
                'page' => 1
            ]
        ]);
        
        $documentsList = $response->toArray();

        $request->getSession()->remove('document');

        return $this->render('backend/document/documents.html.twig', [
            'documents' => $documentsList['hydra:member']
        ]);
    }
    
    #[Route('/admin-panel/document/delete', name: 'documentDelete')]
    public function documentDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

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

        $storedDocument = $request->getSession()->get('document');

        if (!$storedDocument) {
            $request->getSession()->set('document', $document);
            $storedDocument = $document;
        }

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedDocument["name"],
            ],
            "empty_data"=>$storedDocument["name"],
            "required"=>false,
        ])
        ->add("type", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedDocument["type"],
            ], 
            "empty_data"=>$storedDocument["type"],
            "required"=>false,
        ])
        ->add("url", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedDocument["url"],
            ],
            "required"=>false,
            "empty_data"=>$storedDocument["url"],
        ])
        ->add("owner", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedDocument["owner"]["id"],
            ],
            "required"=>false,
            "empty_data"=>$storedDocument["owner"]["id"],
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedDocument['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('documentList');
            }      
            return $this->render('backend/document/editDocument.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }
}
