<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class actionUserController extends AbstractController
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

    #[Route('/admin-panel/user/delete', name: 'userDelete')]
    public function userDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');
        $origin = $request->query->get('origin');


        $response = $client->request('DELETE', 'cs_users/'.$id);

        return $this->redirectToRoute($origin);
    }


    #[Route('/admin-panel/user/show', name: 'userShow')]
    public function userShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $userData = $request->request->get('user');
        $user = json_decode($userData, true);
        
        return $this->render('backend/user/showUser.html.twig', [
            'user'=>$user
        ]);
    }

    #[Route('/admin-panel/user/create', name: 'userCreate')]
    public function userCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $role = $request->query->get('role');
        
        try {
            $defaults = [
                'roles' => [$role],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Email",
            ],
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
        ])
        ->add("password", PasswordType::class, [
            "attr"=>[
                "placeholder"=>"Mot de passe"
            ],
            'constraints'=>[
                new Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*.?&])[A-Za-z\d@$!%*.?&]{8,}$/',
                    'message' => "Le mot de passe doit contenir 8 caractères minimum, au moins 1 lettre majuscule, 1 lettre minuscule, 1 chiffre et 1 caractère spécial"
                ]),
            ]
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
            
            if ($response->toArray()["hydra:totalItems"] > 0){
                $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";

                return $this->render('backend/user/createUser.html.twig', [
                    'form'=>$form,
                    'errorMessages'=>$errorMessages
                ]);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 13]);

            $response = $client->request('POST', 'cs_users', [
                'json' => $data,
            ]);
            $response = json_decode($response->getContent(), true);

            if ($role[0] == 'ROLE_LESSOR') {
                $route = 'lessorList';
            } elseif ($role[0] == 'ROLE_PROVIDER') {
                $route = 'providerList';
            } else {
                $route = 'travelerList';
            }

            return $this->redirectToRoute($route);
        }      
        return $this->render('backend/user/createUser.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
    
    #[Route('/admin-panel/user/ban', name: 'userBan')]
    public function userBan(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $userData = $request->request->get('user');
        $user = json_decode($userData, true);
        
        $origin = $request->request->get('origin');
        $storedUser = $request->getSession()->get('userId');

        if (!$storedUser) {
            $request->getSession()->set('userId', $user['id']);
        }

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');


        $data = array('isBan' => true);

        $response = $client->request('PATCH', 'cs_users/'.$user['id'], [
            'json' => $data,
        ]);

        $var = $response->getContent(false);

        
        $response = $response->getStatusCode();
        return $this->redirectToRoute($origin);
    }
}
