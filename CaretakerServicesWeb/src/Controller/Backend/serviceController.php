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

class serviceController extends AbstractController
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

    #[Route('/admin-panel/service/list', name: 'serviceList')]
    public function serviceList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();

        return $this->render('backend/service/services.html.twig', [
            'services' => $servicesList['hydra:member']
        ]);
    }

    #[Route('/admin-panel/service/delete', name: 'serviceDelete')]
    public function serviceDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_services/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);

        return $this->redirectToRoute('serviceList');
    }

    #[Route('/admin-panel/service/edit', name: 'serviceEdit')]
    public function serviceEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $serviceData = $request->request->get('service');
        $service = json_decode($serviceData, true);

        $storedService = $request->getSession()->get('serviceId');

        if (!$storedService) {
            $request->getSession()->set('serviceId', $service['id']);
        }

        try {
            $defaults = [
                'name' => $service['name'],
                'description' => $service['description'],
                'price' => $service['price'],
                'category' => $service['category']['id'],
                'company' => $service['company']['id'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ], 
            "required"=>false,
        ])
        ->add("description", TextType::class, [
            "attr"=>[
                "placeholder"=>"Description",
            ],
            "required"=>false,
        ])
        ->add("category", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Catégorie ID",
            ],
            "required"=>false,
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            "required"=>false,
        ])
        ->add("company", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Entreprise ID",
            ],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();

                $data['company'] = 'api/cs_companies/'.$data['company'];
                $data['category'] = 'api/cs_categories/'.$data['category'];
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_services/'.$storedService, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('userId');
                $request->getSession()->remove('serviceId');

                return $this->redirectToRoute('serviceList');
            }      
            return $this->render('backend/service/editService.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/service/show', name: 'serviceShow')]
    public function serviceShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $serviceData = $request->request->get('service');
        $service = json_decode($serviceData, true);
        
        return $this->render('backend/service/showService.html.twig', [
            'service'=>$service
        ]);
    }
    
    #[Route('/admin-panel/service/create', name: 'serviceCreate')]
    public function serviceCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $form = $this->createFormBuilder()
        ->add("provider", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prestataire ID",
            ],
            "required"=>false,
        ])
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
            "required"=>false,
        ])
        ->add("category", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Catégorie ID",
            ], 
            "required"=>false,
        ])
        ->add("price", FloatType::class, [
            "attr"=>[
                "placeholder"=>"Prix",   
            ],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $data['category'] = 'api/cs_categories/'.$data['category'];

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_services', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('serviceList');
        }      
        return $this->render('backend/service/createService.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
}
