<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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

        $request->getSession()->remove('userId');
        $request->getSession()->remove('lessorId');

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
            $lessor['isVerified'] == 1 ? $verifiedLessors[] = $lessor : $unverifiedLessors[] = $lessor;
        }

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

        $storedLessor = $request->getSession()->get('lessorId');

        if (!$storedLessor) {
            $request->getSession()->set('lessorId', $lessor['id']);
        }

        try {
            $defaults = [
                'email' => $lessor['email'],
                'firstname' => $lessor['firstname'],
                'lastname' => $lessor['lastname'],
                'telNumber' => $lessor['telNumber'],
                'roles' => $lessor['roles'],
                'isVerified' => $lessor['isVerified'],
            ];
            if (isset($lessor['profilePict'])) {
                $defaults['profilePict'] = $lessor['profilePict'];
            }
        } catch (Exception $e) {
            $defaults = [];
        }
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
            "constraints"=>[
                new Length([
                    'min' => 3,
                    'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                    'max' => 150,
                    'maxMessage' => 'Le prénom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ],
            "constraints"=>[
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Le nom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
            "required"=>false,
        ])
        ->add("roles", ChoiceType::class, [
            "multiple"=>true,
            "expanded"=>false,   
            "choices"=>[
                "Lessor"=>"ROLE_LESSOR",
                "Provider"=>"ROLE_PROVIDER",
                "Traveler"=>"ROLE_TRAVELER",
                "Admin"=>"ROLE_ADMIN",
            ],
            "required"=>false,
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro de Téléphone",
            ],
            "constraints"=>[
                new Length([
                    'max' => 10,
                    'min' => 10,
                    'exactMessage' => 'Le numéro de téléphone doit contenir {{ limit }} chiffres',
                ]),
                new Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Le numéro de téléphone doit contenir uniquement des chiffres',
                ]),
            ],
            "required"=>false,
        ])
        ->add("profilePict", UrlType::class, [
            "attr"=>[
                "placeholder"=>"URL de la photo de profil",
            ],
            "required"=>false,
        ])
        ->add("isVerified", CheckboxType::class, [
            "attr"=>[
                "placeholder"=>"Vérifié",
            ],
            'required'=>false,
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');
            
            $response = $client->request('GET', 'cs_users', [
                'query' => [
                    'page' => 1,
                    'email' => $data['email']
                    ]
                ]);
            if ($response->toArray()["hydra:totalItems"] > 0 && $response->toArray()["hydra:member"][0]['id'] != $storedLessor) {
                $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";

                return $this->render('backend/lessor/editLessor.html.twig', [
                    'form'=>$form,
                    'errorMessages'=>$errorMessages
                ]);
            }
            
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            
            $response = $client->request('PATCH', 'cs_users/'.$storedLessor, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('userId');
            $request->getSession()->remove('lessorId');

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
