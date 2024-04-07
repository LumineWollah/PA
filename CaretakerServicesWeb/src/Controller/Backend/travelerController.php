<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class travelerController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/traveler/list', name: 'travelerList')]
    public function travelerList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_TRAVELER'
            ]
        ]);
        
        $travelersList = $response->toArray();

        $request->getSession()->remove('traveler');

        return $this->render('backend/travelers.html.twig', [
            'travelers' => $travelersList['hydra:member']
        ]);
    }

    #[Route('/admin-panel/traveler/delete', name: 'travelerDelete')]
    public function travelerDelete(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_users/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('travelerList');
        
    }

    #[Route('/admin-panel/traveler/edit', name: 'travelerEdit')]
    public function travelerEdit(Request $request)
    {
        $travelerData = $request->request->get('traveler');
        $traveler = json_decode($travelerData, true);

        $storedTraveler = $request->getSession()->get('traveler');

        if (!$storedTraveler) {
            $request->getSession()->set('traveler', $traveler);
            $storedTraveler = $traveler;
        }

        $form = $this->createFormBuilder()
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>$storedTraveler["email"],
            ],
            "empty_data"=>$storedTraveler["email"],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedTraveler["firstname"],
            ], 
            "empty_data"=>$storedTraveler["firstname"],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedTraveler["lastname"],
            ],
            "required"=>false,
            "empty_data"=>$storedTraveler["lastname"],
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedTraveler["telNumber"],
            ],
            "required"=>false,
            "empty_data"=>$storedTraveler["telNumber"],
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedTraveler['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('travelerList');
            }      
            return $this->render('backend/editTraveler.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/traveler/show', name: 'travelerShow')]
    public function travelerShow(Request $request)
    {
        $travelerData = $request->request->get('traveler');
        $traveler = json_decode($travelerData, true);

        $storedTraveler = $request->getSession()->get('traveler');

        if (!$storedTraveler) {
            $request->getSession()->set('traveler', $traveler);
            $storedTraveler = $traveler;
        }
        
        return $this->render('backend/showTraveler.html.twig', [
            'traveler'=>$storedTraveler
        ]);
    }

}
