<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class serviceController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
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
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
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

        $response = $client->request('GET', 'cs_categories', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $categoriesList = $response->toArray();
        $categoryChoice = array();

        foreach ($categoriesList['hydra:member'] as $category) {
            $categoryChoice += [ $category['name'] => $category['id'] ];
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
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            "required"=>false,
        ])
        ->add("category", ChoiceType::class, [
            "choices" => $categoryChoice,
        ])
        ->add("company", ChoiceType::class, [
            "choices" => $companyChoice,
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

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
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

        $response = $client->request('GET', 'cs_categories', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $categoriesList = $response->toArray();
        $categoryChoice = array();

        foreach ($categoriesList['hydra:member'] as $category) {
            $categoryChoice += [ $category['name'] => $category['id'] ];
        }
        $form = $this->createFormBuilder()
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
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            'constraints'=>[
                new GreaterThanOrEqual(1),
            ],
            "required"=>false,
        ])
        ->add("coverImage", FileType::class, [
            'constraints' => [
                new File([
                    'maxSize' => '10m',
                    'mimeTypes' => [
                        'image/png', 
                        'image/jpeg', 
                    ],
                    'mimeTypesMessage' => 'Please upload a valid jpeg or png document',
                ])
            ],
        ])
        ->add("addressInputs", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Nombre d'adresses",
            ],
            'constraints'=>[
                new GreaterThanOrEqual(1),
            ],
        ])
        ->add("daysOfWeek", ChoiceType::class, [
            "choices" => [
                "Lundi" => 0,
                "Mardi" => 1,
                "Mercredi" => 2,
                "Jeudi" => 3,
                "Vendredi" => 4,
                "Samedi" => 5,
                "Dimanche" => 6,
            ],
            "expanded" => True,
            "multiple" => True,
        ])
        ->add('startTime', TimeType::class, [
            'input'  => 'datetime',
            'widget' => 'choice',
        ])
        ->add('endTime', TimeType::class, [
            'input'  => 'datetime',
            'widget' => 'choice',
        ])
        ->add("category", ChoiceType::class, [
            "choices" => $categoryChoice,
        ])
        ->add("company", ChoiceType::class, [
            "choices" => $companyChoice,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $results = $this->amazonS3Client->insertObject($data['coverImage']);
            $data['coverImage'] = $results['link'];

            $data['category'] = 'api/cs_categories/'.$data['category'];
            $data['company'] = 'api/cs_companies/'.$data['company'];

            $data['startTime'] = $data['startTime']->format('H:i');
            $data['endTime'] = $data['endTime']->format('H:i');

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
    
    #[Route('/admin-panel/service/unavailable', name: 'serviceUnavailable')]
    public function serviceUnavailable(Request $request)
    {
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_LESSOR')) {
            return $this->redirectToRoute('login');
        }
        
        $serviceData = $request->request->get('service');
        $service = json_decode($serviceData, true);

        $storedService = $request->getSession()->get('serviceId');

        if (!$storedService) {
            $request->getSession()->set('serviceId', $service['id']);
        }

        $serId = $storedService;
        
        $form = $this->createFormBuilder()
        ->add("indisponibilities", HiddenType::class, [
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

                $indispo = explode(",", $data['indisponibilities']);

                unset($data['indisponibilities']);
      
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

                for ($i=0; $i < count($indispo); $i++) { 
                    
                    if (trim($indispo[$i]) != "") {

                        $dates = explode(" ", trim($indispo[$i]));

                        $startDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                        $endDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[2]));

                        $data['startingDate'] = $startDateTime->format('Y-m-d');
                        $data['endingDate'] = $endDateTime->format('Y-m-d');
                        $data['price'] = 0;
                        $data['service'] = '/api/cs_services/'.$serId;
                        $data['unavailability'] = true;
                        
                        $response = $client->request('POST', 'cs_reservations', [
                            'json' => $data,
                        ]);
                    }
                }
                return $this->redirectToRoute('serviceList');
            }

        return $this->render('backend/service/serviceUnavailable.html.twig', [
            'form'=>$form
        ]);
    }

}
