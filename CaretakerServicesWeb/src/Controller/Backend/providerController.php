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

class providerController extends AbstractController
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


    #[Route('/admin-panel/provider/list', name: 'providerList')]
    public function providerList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $request->getSession()->remove('userId');
        $request->getSession()->remove('providerId');
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_PROVIDER'
            ]
        ]);
        
        $providersList = $response->toArray();

        $verifiedProviders = array();
        $unverifiedProviders = array();

        foreach ($providersList['hydra:member'] as $provider) {
            $provider['isVerified'] == 1 ? $verifiedProviders[] = $provider : $unverifiedProviders[] = $provider;
        }

        return $this->render('backend/provider/providers.html.twig', [
            'verifiedProviders' => $verifiedProviders,
            'unverifiedProviders' => $unverifiedProviders
        ]);
    }

    #[Route('/admin-panel/provider/edit', name: 'providerEdit')]
    public function providerEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $providerData = $request->request->get('provider');
        $provider = json_decode($providerData, true);

        $storedProvider = $request->getSession()->get('providerId');

        $response = $client->request('GET', 'cs_companies', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $companiesList = $response->toArray();
        $companyChoice = array();

        foreach ($companiesList['hydra:member'] as $company) {
            $companyChoice += [ $company['companyName'] => $company['id'] ];
        }

        if (!$storedProvider) {
            $request->getSession()->set('providerId', $provider['id']);
        }

        try { 
            $defaults = [
                'email' => $provider['email'],
                'firstname' => $provider['firstname'],
                'lastname' => $provider['lastname'],
                'telNumber' => $provider['telNumber'],
                'roles' => $provider['roles'],
                'isVerified' => $provider['isVerified'],
            ];
            if (isset($provider['company'])) {
                $defaults['company'] = $provider['company']['id'];
            }
            if (isset($provider['profilePict'])) {
                $defaults['profilePict'] = $provider['profilePict'];
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
                "placeholder"=>"Numéro de Télephone",
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
        ->add("company", ChoiceType::class, [
            "choices" => $companyChoice,
            "expanded" => False,
            "multiple" => False,
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
                
                if ($response->toArray()["hydra:totalItems"] > 0 && $response->toArray()["hydra:member"][0]['id'] != $storedProvider) {
                    $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";
    
                    return $this->render('backend/provider/editProvider.html.twig', [
                        'form'=>$form,
                        'errorMessages'=>$errorMessages
                    ]);
                }

                $data['company'] = 'api/cs_companies/'.$data['company'];
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedProvider, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('userId');
                $request->getSession()->remove('providerId');

                return $this->redirectToRoute('providerList');
            }      
            return $this->render('backend/provider/editProvider.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/provider/accept', name: 'providerAccept')]
    public function providerAccept(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_users/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('providerList');
    }
}
