<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class lessorController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/lessor/list', name: 'lessorList')]
    public function lessorList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_LESSOR'
            ]
        ]);
        
        $lessorsList = $response->toArray();

        $verifiedLessors = array();
        $unverifiedLessors = array();

        foreach ($lessorsList['hydra:member'] as $lessor) {
            $lessor['telNumber'] = implode(".", str_split($lessor['telNumber'], 2));
            $lessor['isVerified'] == 1 ? $verifiedLessors[] = $lessor : $unverifiedLessors[] = $lessor;
        }

        $request->getSession()->remove('lessor');

        // return;

        return $this->render('backend/lessors.html.twig', [
            'verifiedLessors' => $verifiedLessors,
            'unverifiedLessors' => $unverifiedLessors
        ]);
    }
    
    #[Route('/admin-panel/lessor/delete', name: 'lessorDelete')]
    public function lessorDelete(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_users/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('lessorList');
        
    }

    #[Route('/admin-panel/lessor/edit', name: 'lessorEdit')]
    public function lessorEdit(Request $request)
    {
        $lessorData = $request->request->get('lessor');
        $lessor = json_decode($lessorData, true);

        $storedLessor = $request->getSession()->get('lessor');

        if (!$storedLessor) {
            $request->getSession()->set('lessor', $lessor);
            $storedLessor = $lessor;
        }

        $form = $this->createFormBuilder()
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>$storedLessor["email"],
            ],
            "empty_data"=>$storedLessor["email"],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedLessor["firstname"],
            ], 
            "empty_data"=>$storedLessor["firstname"],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedLessor["lastname"],
            ],
            "required"=>false,
            "empty_data"=>$storedLessor["lastname"],
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedLessor["telNumber"],
            ],
            "required"=>false,
            "empty_data"=>$storedLessor["telNumber"],
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedLessor['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('lessorList');
            }      
            return $this->render('backend/editLessor.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/lessor/show', name: 'lessorShow')]
    public function lessorShow(Request $request)
    {
        $lessorData = $request->request->get('lessor');
        $lessor = json_decode($lessorData, true);

        $storedLessor = $request->getSession()->get('lessor');

        if (!$storedLessor) {
            $request->getSession()->set('lessor', $lessor);
            $storedLessor = $lessor;
        }
        
        return $this->render('backend/showLessor.html.twig', [
            'lessor'=>$storedLessor
        ]);
    }

    #[Route('/admin-panel/lessor/accept', name: 'lessorAccept')]
    public function lessorAccept(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_users/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('lessorList');
        
    }

    // #[Route('/admin-panel/lessor/refuse', name: 'lessorRefuse')]
    // public function lessorRefuse(Request $request)
    // {        
    // }

}
