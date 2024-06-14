<?php

namespace App\Controller\Frontend;

use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
use Stripe\Stripe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class servicesController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;
    private $stripeKeyPrivate;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    #[Route('/services', name: 'servicesList')]
    public function servicesList(Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseServices = $client->request('GET', 'cs_services');

        $services = $responseServices->toArray()['hydra:member'];

        $sortedServices = [];

        foreach ($services as $service) {
            $categoryName = $service['category']['name'];
            $categoryColor = $service['category']['color'];
            $categoryId = $service['category']['id'];
            $categoryKey = $categoryName . '-' . $categoryColor . '-' . $categoryId;
            
            if (!isset($sortedServices[$categoryKey])) {
                $sortedServices[$categoryKey] = [];
            }
            $sortedServices[$categoryKey][] = $service;
        }

        return $this->render('frontend/services/servicesList.html.twig', [
            'services'=>$sortedServices
        ]); 
    }

    #[Route('/services/category/{id}', name: 'servicesCategoryList')]
    public function servicesCategoryList(Request $request, int $id)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseCat = $client->request('GET', 'cs_categories/'.$id);

        $category = $responseCat->toArray();
        
        return $this->render('frontend/services/servicesCategoryList.html.twig', [
            'category'=>$category
        ]); 
    }

    #[Route('/my-services', name: 'myServicesList')]
    public function myServicesList(Request $request)
    { 
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_PROVIDER')) {
            return $this->redirectToRoute('login');
        }

        $showPopup = $request->query->get('showPopup', false);
        $content = $request->query->get('content', null);
        $title = $request->query->get('title', null);

        $request->query->set('showPopup', false);
        $request->query->set('content', null);
        $request->query->set('title', null);
        
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $companyResp = $client->request('GET', 'cs_companies?users[]='.$id);
        $comp = $companyResp->toArray()['hydra:member'][0];

        $responseServ = $client->request('GET', 'cs_services?company='.$comp['id']);
        $services = $responseServ->toArray()['hydra:member'];

        // $request->getSession()->set('serv', $serv['hydra:member']);

        $sortedServices = [];

        foreach ($services as $service) {
            $categoryName = $service['category']['name'];
            $categoryColor = $service['category']['color'];
            $categoryId = $service['category']['id'];
            $categoryKey = $categoryName . '-' . $categoryColor . '-' . $categoryId;
            
            if (!isset($sortedServices[$categoryKey])) {
                $sortedServices[$categoryKey] = [];
            }
            $sortedServices[$categoryKey][] = $service;
        }

        return $this->render('frontend/services/servicesListProvider.html.twig', [
            'serv'=>$sortedServices,
            'showPopup'=>$showPopup,
            'content'=>$content,
            'title'=>$title
        ]);
        
    }

    #[Route('/services/create', name: 'serviceCreateProvider')]
    public function serviceCreateProvider(Request $request)
    {
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_PROVIDER')) {
            return $this->redirectToRoute('login');
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $companyResp = $client->request('GET', 'cs_companies?users[]='.$id);
        $comp = $companyResp->toArray()['hydra:member'][0];

        $responseCat = $client->request('GET', 'cs_categories');
        $categories = $responseCat->toArray()['hydra:member'];
        $catChoice = array();

        foreach ($categories as $categorie) {
            $catChoice += [ $categorie['name'] => $categorie['id'] ];
        }

        $defaults = [
            'companyEmail' => $comp['companyEmail'],
        ];

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom de votre service",
            ],
            "constraints"=>[
                new Length([
                    'max' => 50,
                    'maxMessage' => 'Le nom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
        ])
        ->add("description", TextareaType::class, [
            "attr"=>[
                "placeholder"=>"Description de votre service",
            ],
            "constraints"=>[
                new Length([
                    'min' => 50,
                    'maxMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                ]),
            ],
        ])
        ->add("price", NumberType::class, [
            'required' => false,
        ])
        ->add("addressInputs", ChoiceType::class, [
            'choices' => [
                0 => 0,
                1 => 1,
                2 => 2,
            ]
        ])
        ->add('category', ChoiceType::class, [
            'choices' => $catChoice,
        ])
        ->add("mainPict", FileType::class, [
            "attr"=>[
                "placeholder"=>"Image principale",
            ], 
            'constraints' =>  new File([
                'maxSize' => '10m',
                'mimeTypes' => [
                    'image/png', 
                    'image/jpeg', 
                ],
                'mimeTypesMessage' => 'Please upload a valid jpeg or png document',
            ])
        ])
        ->add("companyEmail", TextType::class, [
            "disabled"=>true,
        ])
        ->add('daysOfWeek', ChoiceType::class, [
            'choices' => [
                'Lundi' => 0,
                'Mardi' => 1,
                'Mercredi' => 2,
                'Jeudi' => 3,
                'Vendredi' => 4,
                'Samedi' => 5,
                'Dimanche' => 6,
            ],
            'multiple' => true,
            'expanded' => true,
        ])
        ->add('startTime', TimeType::class, [
            'widget' => 'single_text',
        ])
        ->add('endTime', TimeType::class, [
            'widget' => 'single_text',
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $results = $this->amazonS3Client->insertObject($data['mainPict']);

            if ($results['success']) {

                unset($data['companyEmail']);
                unset($data['mainPict']);

                $data['coverImage'] = $results['link'];
                $data['company'] = '/api/cs_companies/'.$comp['id'];
                $data['category'] = '/api/cs_categories/'.$data['category'];
                $data['startTime'] = $data['startTime']->format('H:i:s');
                $data['endTime'] = $data['endTime']->format('H:i:s');

                $clientConnect = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

                $response = $clientConnect->request('POST', 'cs_services', [
                    'json' => $data
                ]);

                $content = json_decode($response->getContent(), true);


                if ($response->getStatusCode() == 201) {
                    return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Service créé avec succès', 'title'=>'Création de service']);
                } else {
                    return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Erreur lors de la création du service', 'title'=>'Création de service']);
                }
            } else {
                return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Erreur lors de l\'upload de l\'image', 'title'=>'Création de service']);
            }
        }

        return $this->render('frontend/services/servicesCreate.html.twig', [
            'form'=>$form,
            'mainPict'=>null
        ]);
    }

}
