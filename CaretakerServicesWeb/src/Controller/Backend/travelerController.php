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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class travelerController extends AbstractController
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

    #[Route('/admin-panel/traveler/list', name: 'travelerList')]
    public function travelerList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_TRAVELER'
            ]
        ]);

        $travelersList = $response->toArray();

        return $this->render('backend/traveler/travelers.html.twig', [
            'travelers' => $travelersList['hydra:member']

        ]);
    }

    #[Route('/admin-panel/traveler/edit', name: 'travelerEdit')]
    public function travelerEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $travelerData = $request->request->get('traveler');
        $traveler = json_decode($travelerData, true);

        $storedTraveler = $request->getSession()->get('travelerId');

        if (!$storedTraveler) {
            $request->getSession()->set('travelerId', $traveler['id']);
        }

        try {
            $defaults = [
                'email' => $traveler['email'],
                'firstname' => $traveler['firstname'],
                'lastname' => $traveler['lastname'],
                'telNumber' => $traveler['telNumber'],
                'roles' => $traveler['roles'],
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
    
                    return $this->render('backend/traveler/editTraveler.html.twig', [
                        'form'=>$form,
                        'errorMessages'=>$errorMessages
                    ]);
                }
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedTraveler, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('userId');
                $request->getSession()->remove('travelerId');

                return $this->redirectToRoute('travelerList');
            }      
            return $this->render('backend/traveler/editTraveler.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }
}
