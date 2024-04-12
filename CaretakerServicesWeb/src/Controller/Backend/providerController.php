<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
            //$provider['telNumber'] = implode(".", str_split($provider['telNumber'], 2));
            $provider['isVerified'] == 1 ? $verifiedProviders[] = $provider : $unverifiedProviders[] = $provider;
        }

        // echo "<pre>";
        // print_r($providersList);
        // echo "</pre>";

        $request->getSession()->remove('user');
        $request->getSession()->remove('provider');

        // return;

        return $this->render('backend/provider/providers.html.twig', [
            'verifiedProviders' => $verifiedProviders,
            'unverifiedProviders' => $unverifiedProviders
        ]);
    }

    #[Route('/admin-panel/provider/edit', name: 'providerEdit')]
    public function providerEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $providerData = $request->request->get('provider');
        $provider = json_decode($providerData, true);

        $storedProvider = $request->getSession()->get('provider');

        if (!$storedProvider) {
            $request->getSession()->set('provider', $provider);
            $storedProvider = $provider;
        }

        $defaults = [
            'email' => $storedProvider['email'],
            'firstname' => $storedProvider['firstname'],
            'lastname' => $storedProvider['lastname'],
            'telNumber' => $storedProvider['telNumber'],
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
                "placeholder"=>"Numéro de Télephone",
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

                $response = $client->request('PATCH', 'cs_users/'.$storedProvider['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('providerList');
            }      
            return $this->render('backend/provider/editProvider.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/provider/show', name: 'providerShow')]
    public function providerShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $providerData = $request->request->get('provider');
        $provider = json_decode($providerData, true);

        $storedProvider = $request->getSession()->get('provider');

        if (!$storedProvider) {
            $request->getSession()->set('provider', $provider);
            $storedProvider = $provider;
        }
        
        return $this->render('backend/provider/showProvider.html.twig', [
            'provider'=>$storedProvider
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

    // #[Route('/admin-panel/provider/refuse', name: 'providerRefuse')]
    // public function providerRefuse(Request $request)
    // {        
    // }

}
