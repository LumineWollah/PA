<?php

namespace App\Controller\Frontend;

use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
use Exception;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Stripe\Stripe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

class apartmentsController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;
    private $imageAnnotator;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client, string $stripeKeyPrivate, string $keyCertFilePath)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
        $this->imageAnnotator = new ImageAnnotatorClient([
            'credentials' => $keyCertFilePath
        ]);
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

    private function isApartmentImage($path) {
        $isApartment = false;

        $imageData = file_get_contents($path);

        $image = new \Google\Cloud\Vision\V1\Image();
        $image->setContent($imageData);

        $response = $this->imageAnnotator->labelDetection($image);
        $labels = $response->getLabelAnnotations();
        
        if ($labels) {
            $keywords = ['apartment', 'interior', 'furniture', 'room', 'living room', 'bedroom', 'kitchen', 'bathroom', 'building', 'parking', 'street'];
            foreach ($labels as $label) {
                if (in_array(strtolower($label->getDescription()), $keywords)) {
                    $isApartment = true;
                    break;
                }
            }
        }

        if ($response->getError() && $response->getError()->getMessage()) {
            throw new Exception(
                $response->getError()->getMessage() .
                "\nFor more info on error messages, check: " .
                "https://cloud.google.com/apis/design/errors"
            );
        }

        return $isApartment;        
    }

    private function getImageConstraints()
    {
        return [
            new File([
                'maxSize' => '10m',
                'mimeTypes' => [
                    'image/png', 
                    'image/jpeg', 
                ],
                'mimeTypesMessage' => 'Please upload a valid jpeg or png document',
            ])
        ];
    }

    #[Route('/apartments/delete', name: 'apartmentDeleteLessor')]
    public function apartmentDeleteLessor(Request $request, MailerInterface $mailer)
    { 
        $id = $request->query->get('id');
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $responseReserv = $client->request('GET', 'cs_reservations?unavailability=false&apartment='.$id);
        $reservations = $responseReserv->toArray()['hydra:member'];

        if (count($reservations) == 0){
            $response = $client->request('DELETE', 'cs_apartments/'.$id);
        }else{
            $now = new DateTime();
            $today = $now->format('Y-m-d');

            $responseAvailable = $client->request('POST', 'cs_apartments/availables/'.$id, [
                'json' => [
                    'starting_date' => $today,
                    'ending_date' => $today
                ]
            ]);

            if (!(($responseAvailable->toArray())['available'])) {
                return $this->redirectToRoute('myApartmentsList', ['showPopup'=>true, 'content'=>'Le logement est réservé pour aujourd\'hui, vous ne pouvez pas le supprimer', 'title'=>'Suppression impossible']);
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
                    ->html('<p>Votre réservation pour le #### dans le logement #### a été annulée, car le logement a du être supprimé, vous serez remboursé dans les prochains jours</p>');

                $mailer->send($email);
            }

            $response = $client->request('DELETE', 'cs_apartments/'.$id);
            return $this->redirectToRoute('myApartmentsList', ['showPopup'=>true, 'content'=>'Les réservations ont été annulées et les clients remboursés', 'title'=>'Suppression réussie']);
        }

        return $this->redirectToRoute('myApartmentsList', ['showPopup'=>true, 'content'=>'Le logement a été supprimé', 'title'=>'Suppression réussie']);
        
    }

    #[Route('/apartment/update', name: 'apartmentUpdate')]
    public function apartmentUpdate(Request $request)
    {
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_LESSOR')) {
            return $this->redirectToRoute('login');
        }

        $apartmentData = $request->request->get('apartment');
        $apartment = json_decode($apartmentData, true);

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_addonss');
        $addons = $response->toArray()['hydra:member'];

        if ($apartment != null) {
            $request->getSession()->set('apartment', $apartment);
        }

        if ($apartment == null) {
            $apartment = $request->getSession()->get('apartment');
        }

        $defaults = [
            "bedrooms"=>$apartment['bedrooms'],
            "bathrooms"=>$apartment['bathrooms'],
            "travelersMax"=>$apartment['travelersMax'],
            "area"=>$apartment['area'],
            "name"=>$apartment['name'],
            "description"=>$apartment['description'],
            "isFullhouse"=>$apartment['isFullhouse'],
            "isHouse"=>$apartment['isHouse'],
            "price"=>$apartment['price'],
            // "mainPict"=>$apartment['mainPict'],
            // "address" => json_encode($apartment['address']),
            // "indisponibilities"=>$apartment['indisponibilities'],

        ];

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom de votre appartement",
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
                "placeholder"=>"Description de votre appartement",
            ], 
        ])
        ->add("bedrooms", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de chambres doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("bathrooms", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de chambres doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("travelersMax", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre maximum de voyageurs doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("area", IntegerType::class, [
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
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le prix doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>['class'=>"form-control"]
        ])
        ->add("address", HiddenType::class, [
            "required"=>true,
            "constraints"=>[
                new NotBlank([
                    'message' => 'L\'adresse est obligatoire',
                ]),
            ],
        ])
        ->add("addons", HiddenType::class, [
            'required'=>false,
        ])
        ->add("mainPict", FileType::class, [
            "attr"=>[
                "placeholder"=>"Image principale",
            ], 
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict1", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict2", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict3", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict4", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict5", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict6", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict7", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict8", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict9", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict10", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("indisponibilities", HiddenType::class, [
            'required'=>false,
        ])
        ->getForm()->handleRequest($request);

        if ($apartment == null && !$form->isSubmitted()) {
            return $this->redirectToRoute('myApartmentsList', ['showPopup'=>true, 'content'=>'Le logement n\'existe pas', 'title'=>'Erreur']);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $apartment = $request->getSession()->get('apartment');

            $data = $form->getData();
    
            if ($data['mainPict']) {
                $results = $this->amazonS3Client->insertObject($data['mainPict']);
                if ($results['success']) {
                    $data['mainPict'] = $results['link'];
                }
            } else {
                $data['mainPict'] = $apartment['mainPict'];
            }
    
            $data['address'] = json_decode($data['address'], true);
            $data["country"] = $this->extractValueByPrefix($data["address"]['context'], 'country');
            $data["city"] = $this->extractValueByPrefix($data["address"]['context'], 'place');
            $data["postalCode"] = $this->extractValueByPrefix($data["address"]['context'], 'postcode');
            $data["centerGps"] = $data['address']['center'];
            $data["address"] = $data['address']['place_name'];
    
            $data['addons'] = explode(",", $data['addons']);

            foreach ($data['addons'] as $key => $addons) {
                $data['addons'][$key] = '/api/cs_addonss/'.$addons;
            }

            $pictures = array($data['mainPict']);
            for ($i = 1; $i <= 10; $i++) {
                if (isset($data['pict' . $i])) {
                    $results = $this->amazonS3Client->insertObject($data['pict' . $i]);
                    if ($results['success']) {
                        $pictures[] = $results['link'];
                    }
                } elseif (isset($apartment['pictures'][$i - 1])) {
                    $pictures[] = $apartment['pictures'][$i - 1];
                }
                unset($data['pict' . $i]);
            }
    
            $data['pictures'] = $pictures;
            $id = $apartment['id'];
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $client->request('PATCH', 'cs_apartments/' . $id, ['json' => $data]);
            
            $request->getSession()->remove('apartment');

            return $this->redirectToRoute('myApartmentsList');
        }

        $apPict = array_pad($apartment['pictures'], 11, null);

        return $this->render('frontend/apartments/apartmentCreate.html.twig', [
            'form'=>$form,
            'addons'=>$addons,
            'mainPict'=>$apartment['mainPict'],
            'pict1'=>$apPict[1],
            'pict2'=>$apPict[2],
            'pict3'=>$apPict[3],
            'pict4'=>$apPict[4],
            'pict5'=>$apPict[5],
            'pict6'=>$apPict[6],
            'pict7'=>$apPict[7],
            'pict8'=>$apPict[8],
            'pict9'=>$apPict[9],
            'pict10'=>$apPict[10],
            'error'=>null
        ]);
    }

    #[Route('/apartments/create', name: 'apartmentCreate')]
    public function apartmentCreate(Request $request)
    {
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_LESSOR')) {
            return $this->redirectToRoute('login');
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $isVerified = json_decode($client->request('GET', 'cs_users/'.$id.'/is-verified')->getContent(), true);

        if (!$isVerified) {
            return $this->redirectToRoute('waitingRoom');
        }

        $response = $client->request('GET', 'cs_addonss');
        $addons = $response->toArray()['hydra:member'];

        $defaults = [
            "bedrooms"=>1,
            "bathrooms"=>1,
            "travelersMax"=>1,
            "area"=>1
        ];

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom de votre appartement",
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
                "placeholder"=>"Description de votre appartement",
            ], 
        ])
        ->add("bedrooms", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de chambres doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("bathrooms", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre de chambres doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("travelersMax", IntegerType::class, [
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le nombre maximum de voyageurs doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>[
                'min'=>1
            ]
        ])
        ->add("area", IntegerType::class, [
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
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 1,
                    'message' => 'Le prix doit être égal ou supérieur à 1',
                
                ]),
            ],
            'attr'=>['class'=>"form-control"]
        ])
        ->add("address", HiddenType::class, [
            "constraints"=>[
                new NotBlank([
                    'message' => 'L\'adresse est obligatoire',
                ]),
            ],
        ])
        ->add("addons", HiddenType::class, [
            'required'=>false,
        ])
        ->add("mainPict", FileType::class, [
            "attr"=>[
                "placeholder"=>"Image principale",
            ], 
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict1", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict2", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict3", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict4", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict5", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict6", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict7", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict8", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict9", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("pict10", FileType::class, [
            'required'=>false,
            'constraints' => $this->getImageConstraints(),
        ])
        ->add("indisponibilities", HiddenType::class, [
            'required'=>false,
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $imagePaths = [$data['mainPict']->getPathname()];

            for ($i = 1; $i <= 10; $i++) {
                if ($data['pict' . $i] != null) {
                    $imagePaths[] = $data['pict' . $i]->getPathname();
                }
            }

            foreach ($imagePaths as $imagePath) {
                if (!$this->isApartmentImage($imagePath)) {
                    return $this->render('frontend/apartments/apartmentCreate.html.twig', [
                        'form'=>$form,
                        'addons'=>$addons,
                        'mainPict'=>null,
                        'pict1'=>null,
                        'pict2'=>null,
                        'pict3'=>null,
                        'pict4'=>null,
                        'pict5'=>null,
                        'pict6'=>null,
                        'pict7'=>null,
                        'pict8'=>null,
                        'pict9'=>null,
                        'pict10'=>null,
                        'error'=>true
                    ]);
                }
            }
            $results = $this->amazonS3Client->insertObject($data['mainPict']);

            if ($results['success']) {

                $data['address'] = json_decode($data['address'], true);
                
                $data["country"] = $this->extractValueByPrefix($data["address"]['context'], 'country');
                $data["city"] = $this->extractValueByPrefix($data["address"]['context'], 'place');
                $data["postalCode"] = $this->extractValueByPrefix($data["address"]['context'], 'postcode');
                $data["centerGps"] = $data['address']['center'];                
                $data["address"] = $data['address']['place_name'];                

                $data['mainPict'] = $results['link'];
                
                $pictures = array($results['link']);
                
                for ($i=1; $i <= 10; $i++) { 
                    if ($data['pict'.$i] != null) {
                        $pictures[] = $this->amazonS3Client->insertObject($data['pict'.$i])['link'];
                    }
                    unset($data['pict'.$i]);
                }
                
                $data['pictures'] = $pictures;

                $data['owner'] = 'api/cs_users/'.$id;
                
                if ($data['addons'] != null){

                    $data['addons'] = explode(",", $data['addons']);

                    foreach ($data['addons'] as $key => $addons) {
                        $data['addons'][$key] = '/api/cs_addonss/'.$addons;
                    }
                
                }else{
                    $data['addons'] = [];
                }

                $indispo = explode(",", $data['indisponibilities']);

                unset($data['indisponibilities']);

                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');
                
                $response = $client->request('POST', 'cs_apartments', [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
                $apId = $response["id"];

                for ($i=0; $i < count($indispo); $i++) { 
                    
                    if (trim($indispo[$i]) != "") {

                        $dates = explode(" ", trim($indispo[$i]));

                        $startDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                        $endDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[2]));

                        $response = $client->request('POST', 'cs_reservations', [
                            'json' => [
                                "startingDate" => $startDateTime->format('Y-m-d'),
                                "endingDate" => $endDateTime->format('Y-m-d'),
                                "price" => 0,
                                "apartment" => "/api/cs_apartments/".$apId,
                                "unavailability" => true
                            ],
                        ]);
                    }
                }
                return $this->redirectToRoute('myApartmentsList');
            }
        }

        return $this->render('frontend/apartments/apartmentCreate.html.twig', [
            'form'=>$form,
            'addons'=>$addons,
            'mainPict'=>null,
            'pict1'=>null,
            'pict2'=>null,
            'pict3'=>null,
            'pict4'=>null,
            'pict5'=>null,
            'pict6'=>null,
            'pict7'=>null,
            'pict8'=>null,
            'pict9'=>null,
            'pict10'=>null,
            'error'=>null
        ]);
    }

    #[Route('/apartments/{id}', name: 'apartmentsDetail')]
    public function apartmentDetail(int $id, Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseApart = $client->request('GET', 'cs_apartments/'.$id);
        
        if ($responseApart->getStatusCode() == 404) {
            return $this->redirectToRoute('apartmentsList');
        }

        $ap = $responseApart->toArray();

        $stars = 0;
        $nbReviews = 0;

        foreach ($ap['reviews'] as $review) {
            $stars += $review['rate'];
            $nbReviews++;
        }

        $stars = ($nbReviews > 0) ? $stars / $nbReviews : null;

        $defaults = [
            "adultTravelers"=>0,
            "childTravelers"=>0,
            "babyTravelers"=>0,
            "price"=>0
        ];

        $responseReserv = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
                'apartment' => $ap['id']
            ]
        ]);
                
        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();

        $reservs = $responseReserv->toArray();

        $datesRangeReservs = [];

        foreach($reservs['hydra:member'] as $reserv) {
            $formattedStartingDate = substr($reserv["startingDate"], 0, 10);
            $formattedEndingDate = substr($reserv["endingDate"], 0, 10);
            $datesRangeReservs[] = [$formattedStartingDate, $formattedEndingDate];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("dates", TextType::class, [
            "attr"=>[
                "placeholder"=>"Départ - Arrivée",
                'autocomplete'=>"off",
                'readonly'=>'readonly',
                'required'=>true
            ],
            'constraints'=>[
                new NotBlank(),
            ]
        ])
        ->add("adultTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0,
                'max' => $ap['travelersMax']
            ]
        ])
        ->add("childTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0,
                'max' => $ap['travelersMax']
            ]
        ])
        ->add("babyTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0
            ]
        ])
        ->add("services", HiddenType::class, [
            'required'=>false,
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $id = $request->cookies->get('id');
            
            if ($id == null) {
                return $this->redirectToRoute('login', ['redirect'=>'apartmentsDetail', 'id'=>$ap['id']]);
            }

            // foreach ($ap['mandatoryServices'] as $key => $mandatory) {
            //     if (!in_array($mandatory["@id"], $data['services'])) {
            //         $data['services'][] = $mandatory["@id"];
            //     }
            // }

            $dates = explode(" ", $data['dates']);

            $startDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[1]));
            $endDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[3]));

            $duration = $endDateTime->diff($startDateTime)->format("%a");

            $startDateTime = $startDateTime->format('Y-m-d');
            $endDateTime = $endDateTime->format('Y-m-d');

            $data['startingDate'] = $startDateTime;
            $data['endingDate'] = $endDateTime;
            unset($data['dates']);

            if ($request->cookies->get('subscription') > 0) {
                $price = $duration * $ap['price'] * 0.95;
            }else{
                $price = $duration * $ap['price'];
            }

            if ($request->cookies->get('subscription') == 2) {
                $data['price'] = $price;
            }else{
                $data['price'] = $price + (0.03 * $price) + ($data['adultTravelers'] * (0.005 * $price));
            }
            
            if ($data['services'] == null) {
                $data['services'] = [];
            }else{
                $data['services'] = explode(",", $data['services']);
                $data['servicesCompletes'] = [];

                foreach ($data['services'] as $key => $service) {
                    foreach ($servicesList['hydra:member'] as $serv) {
                        if ($serv['id'] == $service) {
                            $data['servicesCompletes'][] = $serv;
                            $data['services'][$key] = '/api/cs_services/'.$service;
                            $data['price'] += $serv['price'];
                            break;
                        }
                    }
                } 
            }

            $data['user'] = 'api/cs_users/'.$id;
            $data['apartment'] = 'api/cs_apartments/'.$ap['id'];

            if ($data['adultTravelers'] + $data['childTravelers'] > $ap['travelersMax']) {
                return $this->redirectToRoute('apartmentsList', ['id' => $ap['id']]);
            }

            $response = $client->request('POST', 'cs_apartments/availables/'.$ap['id'], [
                'json' => [
                    'starting_date' => $startDateTime,
                    'ending_date' => $endDateTime
                ]
            ]);

            if (!(($response->toArray())['available'])) {
                return $this->redirectToRoute('apartmentsList', ['id' => $ap['id']]);
            }

            $request->getSession()->set('reservData', $data);
            $request->getSession()->set('objName', $ap['name']);
            return $this->redirectToRoute('reservPay', ['id'=>$ap['id']]);
        }

        return $this->render('frontend/apartments/apartmentDetail.html.twig', [
            'stars'=>$stars,
            'apartment'=>$ap,
            'services'=>$servicesList['hydra:member'],
            'form'=>$form,
            'datesRangeReservs'=>$datesRangeReservs,
            'subscription'=>$request->cookies->get('subscription')
        ]);
    }

    #[Route('/apartments', name: 'apartmentsList')]
    public function apartmentList(Request $request)
    { 
        // $client = $this->apiHttpClient->getClientWithoutBearer();

        // $responseAparts = $client->request('GET', 'cs_apartments?active=1');

        // $aps = $responseAparts->toArray();

        // return $this->render('frontend/apartments/apartmentList.html.twig', [
        //     'aps'=>$aps['hydra:member']
        // ]);
        return $this->render('frontend/apartments/apartmentList.html.twig');
    }

    #[Route('/my-apartments', name: 'myApartmentsList')]
    public function myApartmentsList(Request $request)
    { 
        $role = $request->cookies->get('roles');
        $id = $request->cookies->get('id');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_LESSOR')) {
            return $this->redirectToRoute('login');
        }

        $showPopup = $request->query->get('showPopup', false);
        $content = $request->query->get('content', null);
        $title = $request->query->get('title', null);

        $request->query->set('showPopup', false);
        $request->query->set('content', null);
        $request->query->set('title', null);
        
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseAparts = $client->request('GET', 'cs_apartments?active=1&owner='.$id);

        $aps = $responseAparts->toArray();

        $request->getSession()->set('aps', $aps['hydra:member']);

        return $this->render('frontend/apartments/apartmentListLessor.html.twig', [
            'aps'=>$aps['hydra:member'],
            'showPopup'=>$showPopup,
            'content'=>$content,
            'title'=>$title
        ]);
        
    }

    #[Route('/reservations/in-progress', name: 'reservsInProgress')]
    public function reservsInProgress(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'reservsInProgress']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $requestsPast = [];
        $requestsPresent = [];
        $requestsFuture = [];

        $responseAparts = $client->request('GET', 'cs_apartments?active=1&owner='.$id);
        $apartments = $responseAparts->toArray()['hydra:member'];

        $reservation = [];

        foreach ($apartments as $apartment) {
            $responseReserv = $client->request('GET', 'cs_reservations?apartment='.$apartment['id'].'&active=1&isRequest=0&unavailability=0');
            $reservs = $responseReserv->toArray()['hydra:member'];

            foreach ($reservs as $reserv) {
                $reservation[] = $reserv;
            }
        }

        foreach ($reservation as $key => $value) {
            if (str_split($value['endingDate'], 10)[0] < date('Y-m-d')) {
                $requestsPast[] = $value;
            } elseif (str_split($value['startingDate'], 10)[0] > date('Y-m-d')) {
                $requestsFuture[] = $value;
            } else {
                $requestsPresent[] = $value;
            }
        }

        return $this->render('frontend/apartments/reservsInProg.html.twig', [
            'requestsPast'=>$requestsPast,
            'requestsPresent'=>$requestsPresent,
            'requestsFuture'=>$requestsFuture
        ]);
    }

    #[Route('/reservations/in-progress/delete', name: 'reservProgDelete')]
    public function reservProgDelete(Request $request, MailerInterface $mailer)
    {
        $reserv = $request->request->get('reservation');
        $reserv = json_decode($reserv, true);

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $email = (new Email())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($reserv['user']['email'])
            ->subject('Annulation de votre réservation')
            ->html('<p>Votre réservation pour le logement '.$reserv['apartment']['name'].' a été annulée.</p><p>Vous serez remboursé dans les prochains jours</p>');

        $mailer->send($email);

        $paymentIntent = \Stripe\PaymentIntent::retrieve($reserv['payementId']);

        $chargeId = $paymentIntent->latest_charge;

        $refund = \Stripe\Refund::create([
            'charge' => $chargeId,
            'amount' => $reserv['price'] * 100,
        ]);
    
        if ($refund->status == 'succeeded') {
            $client->request('PATCH', 'cs_reservations/'.$reserv['id'], [
                'json' => [
                    'active' => false
                ]
            ]);
        }

        return $this->redirectToRoute('reservsInProgress');
    }
}
