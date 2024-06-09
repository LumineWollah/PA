<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use App\Service\AmazonS3Client;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;

class apartmentController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
    }

    private function extractValueByPrefix($data, $prefix) {
        foreach ($data as $item) {
            if (is_array($item) && strpos($item['id'], $prefix) === 0) {
                return $item['text'];
            }
        }
        return null;
    }
    
    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }

    #[Route('/admin-panel/apartment/list', name: 'apartmentCrud')]
    public function apartmentCrud(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1
            ]
        ]);

        $apartmentsList = $response->toArray();
        
        $verifiedApartments = array();
        $unverifiedApartments = array();

        foreach ($apartmentsList['hydra:member'] as $apartment) {
            $apartment['isVerified'] == 1 ? $verifiedApartments[] = $apartment : $unverifiedApartments[] = $apartment;
        }

        return $this->render('backend/apartment/apartments.html.twig', [
            'verifiedApartments' => $verifiedApartments,
            'unverifiedApartments' => $unverifiedApartments
        ]);
    }
    
    #[Route('/admin-panel/apartment/delete', name: 'apartmentDelete')]
    public function apartmentDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_apartments/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('apartmentCrud');
    }

    #[Route('/admin-panel/apartment/edit', name: 'apartmentEdit')]
    public function apartmentEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $apartmentData = $request->request->get('apartment');
        $apartment = json_decode($apartmentData, true);

        $storedApartment = $request->getSession()->get('apartmentId');

        if (!$storedApartment) {
            $request->getSession()->set('apartmentId', $apartment['id']);
        }

        try {
            $defaults = [
                'name' => $apartment['name'],
                'description' => $apartment['description'],
                'bedrooms' => $apartment['bedrooms'],
                'bathrooms' => $apartment['bathrooms'],
                'travelersMax' => $apartment['travelersMax'],
                'price' => $apartment['price'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        try {
            $defaultValues = [
                'owner' => $apartment['owner']['firstname'] . ' ' . $apartment['owner']['lastname'],
            ];
            
            for ($i = 0; $i < count($apartment['addons']); $i++) {
                $defaultValues['addons'][$i] = $apartment['addons'][$i]['name'];
            }
    
        } catch (Exception $e) {
            $defaultValues = [];
        }
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'role' => 'ROLE_LESSOR',
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [ $user['firstname'].' '.$user['lastname'] => $user['id'] ];
        }

        $response = $client->request('GET', 'cs_addonss', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $addonsList = $response->toArray();
        $addonChoice = array();

        foreach ($addonsList['hydra:member'] as $addon) {
            $addonChoice += [ $addon['name'] => $addon['id'] ];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
            "required"=>false,
        ])
        ->add("description", TextType::class, [
            "attr"=>[
                "placeholder"=>"Description",
            ], 
            "required"=>false,
        ])
        ->add("bedrooms", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Chambres",
            ],
            "required"=>false,
        ])
        ->add("bathrooms", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Toilettes",
            ],
            "required"=>false,
        ])
        ->add("travelersMax", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Nombre maximum de voyageurs",
            ],
            "required"=>false,
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            "required"=>false,
        ])
        ->add("owner", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->add("addons", ChoiceType::class, [
            "choices" => $addonChoice,
            "multiple" => True,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $data['owner'] = 'api/cs_users/'.$data['owner'];
            foreach ($data['addons'] as $key => $addon) {
                $data['addons'][$key] = 'api/cs_addonss/'.$addon;
            }

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            $response = $client->request('PATCH', 'cs_apartments/'.$storedApartment, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('apartmentId');

            return $this->redirectToRoute('apartmentCrud');
        }      
        return $this->render('backend/apartment/editApartment.html.twig', [
            'defaults' => $defaultValues,
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
        
        return $this->render('backend/apartment/showApartment.html.twig', [
            'apartment'=>$apartment
        ]);
    }
    
    #[Route('/admin-panel/apartment/create', name: 'apartmentCreate')]
    public function apartmentCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [ $user['firstname'].' '.$user['lastname'] => $user['id'] ];
        }

        $response = $client->request('GET', 'cs_addonss', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $addonsList = $response->toArray();
        $addonChoice = array();

        foreach ($addonsList['hydra:member'] as $addon) {
            $addonChoice += [ $addon['name'] => $addon['id'] ];
        }

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
            "constraints"=>[
                new Length([
                    'max' => 50,
                    'maxMessage' => 'Le nom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
        ])
        ->add("description", TextType::class, [
            "attr"=>[
                "placeholder"=>"Description",
            ], 
        ])
        ->add("bedrooms", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Chambres",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de chambres doit être égal ou supérieur à 1',
                
                ]),
            ],
        ])
        ->add("bathrooms", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Toilettes",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de toilettes doit être égal ou supérieur à 1',
                
                ]),
            ],
        ])
        ->add("travelersMax", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Nombre maximum de voyageurs",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre maximum de voyageurs doit être égal ou supérieur à 1',
                
                ]),
            ],
        ])
        ->add("area", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Superficie",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'La superficie doit être égal ou supérieur à 1',
                
                ]),
            ],
        ])
        ->add("isFullhouse", ChoiceType::class, [
            'choices'  => [
                'Logement Entier' => true,
                'Chambre' => false,
            ],
        ])
        ->add("isHouse", ChoiceType::class, [
            'choices'  => [
                'Maison' => true,
                'Appartement' => false,
            ],
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le prix doit être égal ou supérieur à 1',
                
                ]),
            ],
        ])
        ->add("apartNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro d'appartement (si appartement)",
            ],
            "required"=>false
        ])
        ->add("address", HiddenType::class, [
            "constraints"=>[
                new NotBlank([
                    'message' => 'L\'adresse est obligatoire',
                ]),
            ],
        ])
        ->add("owner", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->add("addons", ChoiceType::class, [
            "choices" => $addonChoice,
            "expanded" => True,
            "multiple" => True,
        ])
        ->add("mainPict", FileType::class, [
            "attr"=>[
                "placeholder"=>"Image principale",
            ], 
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
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $results = $this->amazonS3Client->insertObject($data['mainPict']);

            if ($results['success']) {

                $data['address'] = json_decode($data['address'], true);
                
                $data["country"] = $this->extractValueByPrefix($data["address"]['context'], 'country');
                $data["city"] = $this->extractValueByPrefix($data["address"]['context'], 'place');
                $data["postalCode"] = $this->extractValueByPrefix($data["address"]['context'], 'postcode');
                $data["centerGps"] = $data['address']['center'];                
                $data["address"] = $data['address']['place_name'];                

                $data['mainPict'] = $results['link'];
                $data['pictures'] = array($results['link']);
                $data['owner'] = 'api/cs_users/'.$data['owner'];


                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

                $response = $client->request('POST', 'cs_apartments', [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);

                return $this->redirectToRoute('apartmentCrud');
            }
        }      
        return $this->render('backend/apartment/createApartment.html.twig', [
            'form'=>$form,
            'errorMessage'=>null,
        ]);
    }
    
    #[Route('/admin-panel/apartment/accept', name: 'apartmentAccept')]
    public function apartmentAccept(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_apartments/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('apartmentCrud');
    }
}
