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

    private function extractValueByPrefix($data, $prefix) {
        foreach ($data as $item) {
            if (is_array($item) && strpos($item['id'], $prefix) === 0) {
                return $item['text'];
            }
        }
        return null;
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

    #[Route('/services/delete', name: 'serviceDeleteProvider')]
    public function serviceDeleteProvider(Request $request, MailerInterface $mailer)
    { 
        $id = $request->query->get('id');
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $responseReserv = $client->request('GET', 'cs_reservations?unavailability=false&service='.$id);
        $reservations = $responseReserv->toArray()['hydra:member'];

        if (count($reservations) == 0){
            $response = $client->request('DELETE', 'cs_services/'.$id);
        }else{
            $now = new DateTime();
            $today = $now->format('Y-m-d');

            $responseAvailable = $client->request('POST', 'cs_services/availables/'.$id, [
                'json' => [
                    'starting_date' => $today,
                    'ending_date' => $today
                ]
            ]);

            if (!(($responseAvailable->toArray())['available'])) {
                return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Le service est réservé pour aujourd\'hui, vous ne pouvez pas le supprimer', 'title'=>'Suppression impossible']);
            }

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            $customers = [];
            foreach ($reservations as $reserv) {
                if (!in_array($reserv['user']['email'], $customers)){
                    $customers[] = $reserv['user']['email'];
                }
                
                $paymentIntent = \Stripe\PaymentIntent::retrieve($reserv['payementId']);

                $chargeId = $paymentIntent->latest_charge;

                $refund = \Stripe\Refund::create([
                    'charge' => $chargeId,
                    'amount' => $reserv['price'] * 100,
                ]);
            
                if ($refund->status == 'succeeded') {
                    $client->request('PATCH', 'cs_reservations/'.$id, [
                        'json' => [
                            'active' => false
                        ]
                    ]);
                }
            }

            foreach ($customers as $customer) {
                $email = (new Email())
                    ->from('ne-pas-repondre@caretakerservices.fr')
                    ->to($customer)
                    ->subject('Votre réservation')
                    ->html('<p>Votre réservation pour le #### dans le service #### a été annulée, car le service a du être supprimé, vous serez remboursé dans les prochains jours</p>');

                $mailer->send($email);
            }

            $response = $client->request('DELETE', 'cs_services/'.$id);
            return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Les réservations ont été annulées et les clients remboursés', 'title'=>'Suppression réussie']);
        }

        return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Le service a été supprimé', 'title'=>'Suppression réussie']);
        
    }

    #[Route('/service/update', name: 'serviceUpdate')]
    public function serviceUpdate(Request $request)
    {
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_PROVIDER')) {
            return $this->redirectToRoute('login');
        }

        $serviceData = $request->request->get('service');
        $service = json_decode($serviceData, true);

        if ($service != null) {
            $request->getSession()->set('service', $service);
        }

        if ($service == null) {
            $service = $request->getSession()->get('service');
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
            "name"=>$service['name'],
            "description"=>$service['description'],
            'companyEmail' => $comp['companyEmail'],
            'addressInputs' => $service['addressInputs'],
            'category' => $service['category']['id'],
            // 'mainPict' => $service['coverImage'],
            'daysOfWeek' => $service['daysOfWeek'],
            'startTime' => new DateTime($service['startTime']),
            'endTime' => new DateTime($service['endTime']),
        ];

        if (isset($service['price'])) {
            $defaults['price'] = $service['price'];
        }

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
            "required"=>false,
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

        if ($service == null && !$form->isSubmitted()) {
            return $this->redirectToRoute('myServicesList', ['showPopup'=>true, 'content'=>'Le service n\'existe pas', 'title'=>'Erreur']);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $service = $request->getSession()->get('service');

            $data = $form->getData();
    
            if ($data['mainPict']) {
                $results = $this->amazonS3Client->insertObject($data['mainPict']);
                if ($results['success']) {
                    $data['coverImage'] = $results['link'];
                }
            } else {
                if (isset($service['coverImage'])) {
                    $data['coverImage'] = $service['coverImage'];
                }
            }

            unset($data['companyEmail']);
            unset($data['mainPict']);
    
            if ($data['category'] != $service['category']['id']){ 
                $data['category'] = '/api/cs_categories/'.$data['category'];
            }else{
                unset($data['category']);
            }
            if ($data['startTime'] instanceof DateTime) {
                $data['startTime'] = $data['startTime']->format('H:i:s');
            }else{
                unset($data['startTime']);
            }
            if ($data['endTime'] instanceof DateTime) {
                $data['endTime'] = $data['endTime']->format('H:i:s');
            }else{
                unset($data['endTime']);
            }
    
            $id = $service['id'];
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $client->request('PATCH', 'cs_services/' . $id, ['json' => $data]);
            
            $request->getSession()->remove('service');

            return $this->redirectToRoute('myServicesList');
        }

        $dataToRender = [
            'form'=>$form,
        ];

        if(isset($service['coverImage'])) {
            $dataToRender['mainPict'] = $service['coverImage'];
        }else{
            $dataToRender['mainPict'] = null;
        }
    
        return $this->render('frontend/services/servicesCreate.html.twig', $dataToRender);
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

    #[Route('/services/{id}', name: 'serviceDetail')]
    public function serviceDetail(int $id, Request $request, MailerInterface $mailer)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseServ = $client->request('GET', 'cs_services/'.$id);
        
        if ($responseServ->getStatusCode() == 404) {
            return $this->redirectToRoute('servicesList');
        }

        $serv = $responseServ->toArray();

        $responseReserv = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
                'service' => $serv['id']
            ]
        ]);

        $daysOfWeek = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];

        $result = [];
        foreach ($serv["daysOfWeek"] as $number) {
            if (isset($daysOfWeek[$number])) {
                $result[] = $daysOfWeek[$number];
            }
        }

        $serv["daysOfWeek"] = $result;

        $reservs = $responseReserv->toArray();

        $datesRangeReservs = [];

        foreach($reservs['hydra:member'] as $reserv) {
            $formattedStartingDate = substr($reserv["startingDate"], 0, 10);
            $datesRangeReservs[] = [$formattedStartingDate];
        }

        $form = $this->createFormBuilder()
        ->add("date", TextType::class, [
            "attr"=>[
                "placeholder"=>"Date de l'intervention",
                'autocomplete'=>"off",
                'readonly'=>'readonly',
                'required'=>true
            ],
            'constraints'=>[
                new NotBlank(),
            ]
            ]);

        for ($i=0; $i < $serv['addressInputs']; $i++) { 
            $form = $form->add("address".$i, HiddenType::class, [
                "constraints"=>[
                    new NotBlank([
                        'message' => 'L\'adresse est obligatoire',
                    ]),
                ],
            ]);
        }

        $form = $form->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $id = $request->cookies->get('id');
            $emailUser = $request->cookies->get('email');
            $lastname = ucfirst($request->cookies->get('lastname'));
            
            if ($id == null) {
                return $this->redirectToRoute('login', ['redirect'=>'serviceDetail', 'id'=>$serv['id']]);
            }

            $action = $request->request->get('action');

            if ($action === 'devis') {

                $addresses = '';

                for ($i=0; $i < $serv['addressInputs']; $i++) { 
                    $data['address'.$i] = json_decode($data['address'.$i], true);
                    
                    $data['otherData']["address".$i] = [];
                
                    $data['otherData']["address".$i]["country"] = $this->extractValueByPrefix($data["address".$i]['context'], 'country');
                    $data['otherData']["address".$i]["city"] = $this->extractValueByPrefix($data["address".$i]['context'], 'place');
                    $data['otherData']["address".$i]["postalCode"] = $this->extractValueByPrefix($data["address".$i]['context'], 'postcode');
                    $data['otherData']["address".$i]["centerGps"] = $data['address'.$i]['center'];                
                    $data['otherData']["address".$i]["address"] = $data['address'.$i]['place_name'];

                    $addresses .= '<p>Adresse n°'.($i+1).' : '.$data['otherData']["address".$i]["address"].'</p>';

                    unset($data['address'.$i]);
                }

                $email = (new Email())
                    ->from('ne-pas-repondre@caretakerservices.fr')
                    ->to($serv['company']['companyEmail'])
                    ->subject('Demande de devis')
                    ->html('<p>Un client a demandé un devis pour votre service : '.$serv['name'].'</p><p>Voici les informations du client : </p><p>Nom : '.$lastname.'</p><p>Email : '.$emailUser.'</p><p>Date de l\'intervention : '.$data['date'].'</p>'.$addresses.'<p>Merci de le contacter pour plus d\'informations</p>');

                $mailer->send($email);

                $date = explode(" ", $data['date']);
                $startDateTime = DateTime::createFromFormat('d/m/Y', trim($date[1]));
                $startDateTime = $startDateTime->format('Y-m-d');

                $data = [
                    'request' => true,
                    'user' => 'api/cs_users/'.$id,
                    'service' => 'api/cs_services/'.$serv['id'],
                    'startingDate' => $startDateTime,
                    'endingDate' => $startDateTime,
                    'otherData' => $data['otherData'],
                    'price' => 0,
                    'active' => false,
                ];

                $response = $client->request('POST', 'cs_reservations', [
                    'json' => $data
                ]);

                return $this->redirectToRoute('myRequests');

            } elseif ($action === 'reservation') {
                $data['otherData'] = [];

                for ($i=0; $i < $serv['addressInputs']; $i++) { 
                    $data['address'.$i] = json_decode($data['address'.$i], true);
                    
                    $data['otherData']["address".$i] = [];
                
                    $data['otherData']["address".$i]["country"] = $this->extractValueByPrefix($data["address".$i]['context'], 'country');
                    $data['otherData']["address".$i]["city"] = $this->extractValueByPrefix($data["address".$i]['context'], 'place');
                    $data['otherData']["address".$i]["postalCode"] = $this->extractValueByPrefix($data["address".$i]['context'], 'postcode');
                    $data['otherData']["address".$i]["centerGps"] = $data['address'.$i]['center'];                
                    $data['otherData']["address".$i]["address"] = $data['address'.$i]['place_name'];

                    unset($data['address'.$i]);
                }          

                $date = explode(" ", $data['date']);
                $startDateTime = DateTime::createFromFormat('d/m/Y', trim($date[1]));
                $startDateTime = $startDateTime->format('Y-m-d');
                $data['startingDate'] = $startDateTime;
                $data['endingDate'] = $startDateTime;
                unset($data['date']);

                $data['price'] = $serv['price'];
                
                $data['user'] = 'api/cs_users/'.$id;
                $data['service'] = 'api/cs_services/'.$serv['id'];

                $response = $client->request('POST', 'cs_services/availables/'.$serv['id'], [
                    'json' => [
                        'starting_date' => $startDateTime,
                        'ending_date' => $startDateTime
                    ]
                ]);

                if (!(($response->toArray())['available'])) {
                    return $this->redirectToRoute('servicesList', ['id' => $serv['id']]);
                }

                $request->getSession()->set('reservData', $data);
                $request->getSession()->set('objName', $serv['name']);
                return $this->redirectToRoute('reservPay', ['id'=>$serv['id']]);
            }
        }

        return $this->render('frontend/services/servicesDetail.html.twig', [
            'service'=>$serv,
            'form'=>$form,
            'datesRangeReservs'=>$datesRangeReservs
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
        if (count($companyResp->toArray()['hydra:member']) > 0) {
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
        } else {
            $sortedServices = [];
        }

        return $this->render('frontend/services/servicesListProvider.html.twig', [
            'serv'=>$sortedServices,
            'showPopup'=>$showPopup,
            'content'=>$content,
            'title'=>$title
        ]);
        
    }

}
