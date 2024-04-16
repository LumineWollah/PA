<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class reservationController extends AbstractController
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

    #[Route('/admin-panel/reservation/list', name: 'reservationList')]
    public function reservationList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $reservationsList = $response->toArray();

        return $this->render('backend/reservation/reservations.html.twig', [
            'reservations' => $reservationsList['hydra:member']
        ]);
    }

    #[Route('/admin-panel/reservation/delete', name: 'reservationDelete')]
    public function reservationDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_reservations/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);

        return $this->redirectToRoute('reservationList');
    }

    #[Route('/admin-panel/reservation/edit', name: 'reservationEdit')]
    public function reservationEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reservationData = $request->request->get('reservation');
        $reservation = json_decode($reservationData, true);

        $storedReservation = $request->getSession()->get('reservationId');

        if (!$storedReservation) {
            $request->getSession()->set('reservationId', $reservation['id']);
        }

        try {
            $defaults = [
                'startingDate' => $reservation['startingDate'],
                'endingDate' => $reservation['endingDate'],
                'price' => $reservation['price'],
                'client' => $reservation['client']['id'],
                'service' => $reservation['service']['id'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("startingDate", TextType::class, [
            "attr"=>[
                "placeholder"=>"Date de départ",
            ], 
            "required"=>false,
        ])
        ->add("endingDate", TextType::class, [
            "attr"=>[
                "placeholder"=>"Date de fin",
            ], 
            "required"=>false,
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            "required"=>false,
        ])
        ->add("service", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Service",
            ],
            "required"=>false,
        ])
        ->add("client", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Client",
            ],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();

                $data['service'] = 'api/cs_services/'.$data['service'];
                $data['client'] = 'api/cs_users/'.$data['client'];
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_reservations/'.$storedReservation, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('userId');
                $request->getSession()->remove('reservationId');

                return $this->redirectToRoute('reservationList');
            }      
            return $this->render('backend/reservation/editReservation.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/reservation/show', name: 'reservationShow')]
    public function reservationShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reservationData = $request->request->get('reservation');
        $reservation = json_decode($reservationData, true);
        
        return $this->render('backend/reservation/showReservation.html.twig', [
            'reservation'=>$reservation
        ]);
    }
}
