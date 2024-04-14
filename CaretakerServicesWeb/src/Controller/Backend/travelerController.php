<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

        $request->getSession()->remove('user');
        $request->getSession()->remove('traveler');

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

        $request->getSession()->set('traveler', $traveler);
        $storedTraveler = $traveler;

        $defaults = [
            'email' => $storedTraveler['email'],
            'firstname' => $storedTraveler['firstname'],
            'lastname' => $storedTraveler['lastname'],
            'telNumber' => $storedTraveler['telNumber'],
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

                $response = $client->request('PATCH', 'cs_users/'.$storedTraveler['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('travelerList');
            }      
            return $this->render('backend/traveler/editTraveler.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }
}
