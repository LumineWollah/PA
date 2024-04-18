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
    
    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }

    #[Route('/admin-panel/apartment/list', name: 'apartmentList')]
    public function apartmentList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1
            ]
        ]);
        
        $apartmentsList = $response->toArray();

        return $this->render('backend/apartment/apartments.html.twig', [
            'apartments' => $apartmentsList['hydra:member']
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
        
        return $this->redirectToRoute('apartmentList');
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
                'travelersMax' => $apartment['travelersMax'],
                'price' => $apartment['price'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

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
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $response = $client->request('PATCH', 'cs_apartments/'.$storedApartment, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('apartmentId');

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

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
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
        ])
        ->add("travelersMax", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Nombre maximum de voyageurs",
            ],
        ])
        ->add("area", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Superficie",
            ],
        ])
        ->add("isFullHouse", ChoiceType::class, [
            "attr"=>[
                "placeholder"=>"Type de logement",
            ],
            'choices'  => [
                'Logement Entier' => true,
                'Chambre' => false,
            ],
        ])
        ->add("isHouse", ChoiceType::class, [
            "attr"=>[
                "placeholder"=>"Type de propriété",
            ],
            'choices'  => [
                'Maison' => true,
                'Appartement' => false,
            ],
        ])
        ->add("price", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
        ])
        ->add("apartNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro d'appartement (si appartement)",
            ],
            "required"=>false
        ])
        ->add("address", TextType::class, [
            "attr"=>[
                "placeholder"=>"Adresse",
            ],
        ])
        ->add("city", TextType::class, [
            "attr"=>[
                "placeholder"=>"Ville",
            ], 
        ])
        ->add("postalCode", TextType::class, [
            "attr"=>[
                "placeholder"=>"Code postal",
            ], 
        ])
        ->add("country", TextType::class, [
            "attr"=>[
                "placeholder"=>"Pays",
            ], 
        ])
        ->add("owner", ChoiceType::class, [
            "choices" => $userChoice,
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

                $data['mainPict'] = $results['link'];
                $data['owner'] = 'api/cs_users/'.$data['owner'];

                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

                $response = $client->request('POST', 'cs_apartments', [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);

                return $this->redirectToRoute('apartmentList');
            }
        }      
        return $this->render('backend/apartment/createApartment.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
}
