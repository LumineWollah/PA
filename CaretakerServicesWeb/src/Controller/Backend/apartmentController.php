<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class apartmentController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/apartment/list', name: 'apartmentList')]
    public function apartmentList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1
            ]
        ]);
        
        $apartmentsList = $response->toArray();

        $request->getSession()->remove('apartment');

        return $this->render('backend/apartment/apartments.html.twig', [
            'apartments' => $apartmentsList['hydra:member']
        ]);
    }
    
    #[Route('/admin-panel/apartment/delete', name: 'apartmentDelete')]
    public function apartmentDelete(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_apartments/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('apartmentList');
        
    }

    #[Route('/admin-panel/apartment/edit', name: 'apartmentEdit')]
    public function apartmentEdit(Request $request)
    {
        $apartmentData = $request->request->get('apartment');
        $apartment = json_decode($apartmentData, true);

        $storedApartment = $request->getSession()->get('apartment');

        if (!$storedApartment) {
            $request->getSession()->set('apartment', $apartment);
            $storedApartment = $apartment;
        }

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedApartment["name"],
            ],
            "empty_data"=>$storedApartment["name"],
            "required"=>false,
        ])
        ->add("description", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedApartment["description"],
            ], 
            "empty_data"=>$storedApartment["description"],
            "required"=>false,
        ])
        ->add("bedrooms", IntegerType::class, [
            "attr"=>[
                "placeholder"=>$storedApartment["bedrooms"],
            ],
            "required"=>false,
            "empty_data"=>$storedApartment["bedrooms"],
        ])
        ->add("travelers_max", IntegerType::class, [
            "attr"=>[
                "placeholder"=>$storedApartment["travelers_max"],
            ],
            "required"=>false,
            "empty_data"=>$storedApartment["travelers_max"],
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>$storedApartment["price"],
            ],
            "required"=>false,
            "empty_data"=>$storedApartment["price"],
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_apartments/'.$storedApartment['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('apartmentList');
            }      
            return $this->render('backend/apartment/editApartment.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/apartment/show', name: 'apartmentShow')]
    public function apartmentShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $apartmentData = $request->request->get('apartment');
        $apartment = json_decode($apartmentData, true);

        $storedApartment = $request->getSession()->get('apartment');

        if (!$storedApartment) {
            $request->getSession()->set('apartment', $apartment);
            $storedApartment = $apartment;
        }
        
        return $this->render('backend/apartment/showApartment.html.twig', [
            'apartment'=>$storedApartment
        ]);
    }
}
