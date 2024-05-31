<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class apartmentsController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/apartment/create', name: 'apartmentCreate')]
    public function apartmentCreate(Request $request)
    {
        $role = $request->cookies->get('roles');
        if ($role == null || !($role == 'ROLE_ADMIN' || $role == 'ROLE_LESSOR')) {
            return $this->redirectToRoute('login');
        }

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

        return $this->render('frontend/apartments/apartmentCreate.html.twig', [
            'form'=>$form,
        ]);
    }

    #[Route('/apartment/{id}', name: 'apartmentsDetail')]
    public function apartmentDetail(int $id, Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseApart = $client->request('GET', 'cs_apartments/'.$id);
        
        if ($responseApart->getStatusCode() == 404) {
            return $this->redirectToRoute('apartmentsList');
        }

        $ap = $responseApart->toArray();

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
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $id = $request->cookies->get('id');
            
            if ($id == null) {
                return $this->redirectToRoute('login', ['redirect'=>'/apartment/'.$ap['id']]);
            }

            $dates = explode(" ", $data['dates']);

            $startDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[1]));
            $endDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[3]));

            $duration = $endDateTime->diff($startDateTime)->format("%a");

            $startDateTime = $startDateTime->format('Y-m-d');
            $endDateTime = $endDateTime->format('Y-m-d');

            $data['startingDate'] = $startDateTime;
            $data['endingDate'] = $endDateTime;
            unset($data['dates']);

            $price = $duration * $ap['price'];
            $data['price'] = $price + (0.03 * $price) + ($data['adultTravelers'] * (0.005 * $price));
            
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

            // $response = $client->request('POST', 'cs_reservations', [
            //     'json' => $data,
            // ]);
            
            // return $this->redirectToRoute('apartmentsList');

            // $request->getSession()->set('reservId', $ap['id']);
            return $this->redirectToRoute('reservPay', ['id'=>$ap['id']]);
        }

        return $this->render('frontend/apartments/apartmentDetail.html.twig', [
            'apartment'=>$ap,
            'form'=>$form,
            'datesRangeReservs'=>$datesRangeReservs
        ]);
    }

    #[Route('/apartment', name: 'apartmentsList')]
    public function apartmentList(Request $request)
    { 
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseAparts = $client->request('GET', 'cs_apartments');

        $aps = $responseAparts->toArray();

        return $this->render('frontend/apartments/apartmentList.html.twig', [
            'aps'=>$aps['hydra:member']
        ]);
        
    }
}
