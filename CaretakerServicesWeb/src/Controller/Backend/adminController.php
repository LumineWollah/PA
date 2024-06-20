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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class adminController extends AbstractController
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

    #[Route('/admin-panel/admin/list', name: 'adminList')]
    public function adminList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}       

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_ADMIN'
            ]
        ]);
        
        $adminsList = $response->toArray();

        return $this->render('backend/admin/admins.html.twig', [
            'admins' => $adminsList,
        ]);
    }
    
    #[Route('/admin-panel/admin/edit', name: 'adminEdit')]
    public function adminEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $adminData = $request->request->get('admin');
        $admin = json_decode($adminData, true);

        $storedAdmin = $request->getSession()->get('adminId');

        if (!$storedAdmin) {
            $request->getSession()->set('adminId', $admin['id']);
        }

        try {
            $defaults = [
                'email' => $admin['email'],
                'firstname' => $admin['firstname'],
                'lastname' => $admin['lastname'],
                'telNumber' => $admin['telNumber'],
                'roles' => $admin['roles'],
                'profilePict' => $admin['profilePict'],
            ];
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
                "Admin"=>"ROLE_ADMIN",
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
            if ($response->toArray()["hydra:totalItems"] > 0 && $response->toArray()["hydra:member"][0]['id'] != $storedAdmin) {
                $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";

                return $this->render('backend/admin/editAdmin.html.twig', [
                    'form'=>$form,
                    'errorMessages'=>$errorMessages
                ]);
            }
            
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            
            $response = $client->request('PATCH', 'cs_users/'.$storedAdmin, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('userId');
            $request->getSession()->remove('adminId');

            return $this->redirectToRoute('adminList');
        }      
        return $this->render('backend/admin/editAdmin.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/admin-panel/admin/accept', name: 'adminAccept')]
    public function adminAccept(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_users/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('adminList');
    }
}
