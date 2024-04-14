<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class lessorController extends AbstractController
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

    #[Route('/admin-panel/lessor/list', name: 'lessorList')]
    public function lessorList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}       

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

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
            //$lessor['telNumber'] = implode(".", str_split($lessor['telNumber'], 2));
            $lessor['isVerified'] == 1 ? $verifiedLessors[] = $lessor : $unverifiedLessors[] = $lessor;
        }

        $request->getSession()->remove('user');
        $request->getSession()->remove('lessor');

        // return;

        return $this->render('backend/lessor/lessors.html.twig', [
            'verifiedLessors' => $verifiedLessors,
            'unverifiedLessors' => $unverifiedLessors
        ]);
    }
    
    #[Route('/admin-panel/lessor/edit', name: 'lessorEdit')]
    public function lessorEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $lessorData = $request->request->get('lessor');
        $lessor = json_decode($lessorData, true);

        $request->getSession()->set('lessor', $lessor);
        $storedLessor = $lessor;

        $defaults = [
            'email' => $storedLessor['email'],
            'firstname' => $storedLessor['firstname'],
            'lastname' => $storedLessor['lastname'],
            'telNumber' => $storedLessor['telNumber'],
        ];

        $form = $this->createFormBuilder($defaults)
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>"E-mail",
            ],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Prénom",
            ], 
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ],
            "required"=>false,
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro de Téléphone",
            ],
            "constraints"=>[
                new Length([
                    'min' => 10,
                    'minMessage' => 'Le numéro de téléphone doit contenir au moins {{ limit }} chiffres',
                    'max' => 10,
                    'maxMessage' => 'Le numéro de téléphone doit contenir au plus {{ limit }} chiffres',
                ]),
                new Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Le numéro de téléphone doit contenir uniquement des chiffres',
                ]),
            ],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedLessor['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('lessorList');
            }      
            return $this->render('backend/lessor/editLessor.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/lessor/accept', name: 'lessorAccept')]
    public function lessorAccept(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_users/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('lessorList');
    }
}
