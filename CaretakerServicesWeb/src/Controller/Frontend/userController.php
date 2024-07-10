<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use App\Service\AmazonS3Client;
use DateTime;
use Exception;
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
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

class userController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    private function generateDateLabels(int $days): array
    {
        $labels = [];
        $now = new \DateTime();
        $now = $now->modify('+1 day');

        for ($i = 1; $i <= $days; $i++) {
            $labels[] = $now->modify('-1 day')->format('Y-m-d');
        }

        return array_reverse($labels);
    }
    
    private function fetchData(Request $request, string $endpoint, array $query = ['page' => 1]): array
    {
        //$client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', $endpoint, [
            'query' => $query,
        ]);

        return $response->toArray();
    }

    private function generateRandomString(): string
    {
        $characters = '0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    #[Route('/profile/me', name: 'myProfile')]
    public function myProfile(Request $request)
    {
        $id = $request->cookies->get('id');

        $reservationsList = $this->fetchData($request, 'cs_reservations');

        $dateLabels = $this->generateDateLabels(7);

        $dailyEarnings = [0,0,0,0,0,0,0];

        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_users/'.$id, [
            'json' => [
                'page' => 1,
            ]
        ]);

        $user = $response->toArray();
        
        if ( in_array('ROLE_LESSOR', $user['roles'])) {
            $user['apartmentsNumber'] = 0;
            for ($i = 0; $i < sizeof($user['apartments']); $i++) {
                if ($user['apartments'][$i]['active'] == true) {
                    $user['apartmentsNumber']++;
                }
            }
        }

        $sum = 0;

        for ($i = 0; $i < sizeof($user['roles']); $i++) {
            if ($user['roles'][$i] == 'ROLE_LESSOR') {
                $now = new \DateTime();

                $client = $this->apiHttpClient->getClientWithoutBearer();
        
                $response = $client->request('GET', 'cs_apartments?active=1&owner='.$id, [
                    'json' => [
                        'page' => 1
                    ]
                ]);
        
                $apartments = $response->toArray();

                for ($j = 0; $j < sizeof($apartments); $j++) {
                    foreach ($apartments as $apartment) {
                        if (isset($apartment['reservations'])) {
                            foreach ($apartment['reservations'] as $reservation) {
                                if ($reservation['endingDate'] < $now) {
                                    $sum += $reservation['price'];
                                }
                                if (substr($reservation['dateCreation'], 0, 10) == $dateLabels[$j]) {
                                    $dailyEarnings[$j] += $reservation['price'];
                                }
                            }
                        }
                    }
                }
            }

            if ($user['roles'][$i] == 'ROLE_PROVIDER') {
                $now = new \DateTime();

                $client = $this->apiHttpClient->getClientWithoutBearer();
        
                $response = $client->request('GET', 'cs_services?company='.$user['company']['id'], [
                    'json' => [
                        'page' => 1
                    ]
                ]);
        
                $services = $response->toArray();
                $services = $services['hydra:member'];
                for ($j = 0; $j < sizeof($services); $j++) {
                    foreach ($services as $service) {
                        foreach ($service['reservations'] as $reservation) {
                            if ($reservation['endingDate'] < $now) {
                                $sum += $reservation['price'];
                            }
                            if (substr($reservation['dateCreation'], 0, 10) == $dateLabels[$j]) {
                                $dailyEarnings[$j] += $reservation['price'];
                            }
                        }
                    }
                }
            }
        }
        $user['earnings'] = $sum;
        $user['dailyEarnings'] = ['labels' => $dateLabels, 'data' => $dailyEarnings];

        return $this->render('frontend/user/dashboard.html.twig', [
            'user'=>$user
        ]);
    }

    #[Route('/profile/subscriptions', name: 'subscriptions')]
    public function subscriptions(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'subscriptions']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_users/'.$id, [
            'json' => [
                'page' => 1,
            ]
        ]);

        $user = $response->toArray();

        if (array_key_exists('subsId', $user)) {
            $subsId = $user['subsId'];
            $subscription = $user['subscription'];
            $subsDate = $user['subsDate'];
        } else {
            $subsId = null;
            $subscription = null;
            $subsDate = null;
        }

        return $this->render('frontend/user/subscriptions.html.twig', [
            'user'=>$user,
            'subsId'=>$subsId,
            'subscription'=>$subscription,
            'subsDate'=>$subsDate
        ]);
    }

    #[Route('/profile/reservations/past', name: 'reservationsPast')]
    public function reservationsPast(Request $request)
    {

        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'PAST',
                'obj' => 'apartment'
            ]
        ]);

        $reserv = $response->toArray();
        
        return $this->render('frontend/user/reservPast.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/reservations/present', name: 'reservationsPresent')]
    public function reservationsPresent(Request $request)
    {

        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'PRESENT',
                'obj' => 'apartment'
            ]
        ]);

        $reserv = $response->toArray();

        return $this->render('frontend/user/reservPresent.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/reservations/future', name: 'reservationsFuture')]
    public function reservationsFuture(Request $request)
    {

        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'FUTURE',
                'obj' => 'apartment'
            ]
        ]);

        $reserv = $response->toArray();

        return $this->render('frontend/user/reservFuture.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/services/past', name: 'servicesPast')]
    public function servicesPast(Request $request)
    {
        $id = $request->cookies->get('id');

        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'PAST',
                'obj' => 'service'
            ]
        ]);

        $reserv = $response->toArray();

        return $this->render('frontend/user/servPast.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/services/present', name: 'servicesPresent')]
    public function servicesPresent(Request $request)
    {

        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'PRESENT',
                'obj' => 'service'
            ]
        ]);

        $reserv = $response->toArray();

        return $this->render('frontend/user/servPresent.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/services/future', name: 'servicesFuture')]
    public function servicesFuture(Request $request)
    {

        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('POST', 'cs_users/'.$id.'/reservations', [
            'json' => [
                'time' => 'FUTURE',
                'obj' => 'service'
            ]
        ]);

        $reserv = $response->toArray();

        return $this->render('frontend/user/servFuture.html.twig', [
            'reservations'=>$reserv
        ]);
    }

    #[Route('/profile/reservations/refund', name: 'reservationsRefund')]
    public function reservationsRefund(Request $request)
    {
        $id = $request->cookies->get('id');
        $reservation = $request->request->get('reservation');
        $reservation = json_decode($reservation, true);
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myProfile']);
        }

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($reservation['payementId']);

            $chargeId = $paymentIntent->latest_charge;

            $refund = \Stripe\Refund::create([
                'charge' => $chargeId,
                'amount' => intval($reservation['price'] * 100),
            ]);

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            if ($refund->status == 'succeeded') {
                $client->request('DELETE', 'cs_reservations/'.$reservation['id']);
            }
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            $client->request('DELETE', 'cs_reservations/'.$reservation['id']);
        }
        
        if (isset($reservation['apartment']) && $reservation['apartment'] != null) {
            return $this->redirectToRoute('reservationsFuture');
        } else {
            return $this->redirectToRoute('servicesFuture');
        }
    }

    #[Route('/profile/requests', name: 'myRequests')]
    public function myRequests(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myRequests']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_reservations?user='.$id.'&active=false');

        $requests = $response->toArray();
        
        $requestsPending = [];
        $requestsAccepted = [];
        $requestsRejected = [];

        foreach ($requests['hydra:member'] as $key => $value) {
            if ($value['isRequest'] == false) {
                unset($requests['hydra:member'][$key]);
            }
            if ($value['status'] == 0) {
                $requestsPending[] = $value;
            } elseif ($value['status'] == 1) {
                $requestsAccepted[] = $value;
            } elseif ($value['status'] == 2) {
                $requestsRejected[] = $value;
            }
        }

        return $this->render('frontend/user/requestsList.html.twig', [
            'requestsPending'=>$requestsPending,
            'requestsAccepted'=>$requestsAccepted,
            'requestsRejected'=>$requestsRejected
        ]);
    }

    #[Route('/profile/documents', name: 'myDocuments')]
    public function myDocuments(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myRequests']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_documents?owner='.$id);

        $documents = $response->toArray();
        
        return $this->render('frontend/user/documentsList.html.twig', [
            'documents'=>$documents['hydra:member']
        ]);
    }

    #[Route('/profile/edit', name: 'profileEdit')]
    public function profileEdit(Request $request)
    {
        $userData = $request->query->get('user');
        $user = json_decode($userData, true);

        $storedUser = $request->getSession()->get('user');

        if (!$storedUser) {
            $request->getSession()->set('user', $user['id']);
        }

        if (!isset($user['profilePict'])) {
            $user['profilePict'] = null;
        }

        try {
            $defaults = [
                'email' => $user['email'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'telNumber' => $user['telNumber'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }
        $form = $this->createFormBuilder($defaults)
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>"E-mail",
            ],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Prénom",
            ], 
            "constraints"=>[
                new Length([
                    'min' => 3,
                    'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                    'max' => 150,
                    'maxMessage' => 'Le prénom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ],
            "constraints"=>[
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Le nom doit contenir au plus {{ limit }} caractères',
                ]),
            ],
            "required"=>false,
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro de Téléphone",
            ],
            "constraints"=>[
                new Length([
                    'max' => 10,
                    'min' => 10,
                    'exactMessage' => 'Le numéro de téléphone doit contenir {{ limit }} chiffres',
                ]),
                new Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Le numéro de téléphone doit contenir uniquement des chiffres',
                ]),
            ],
            "required"=>false,
        ])
        ->add("profilePict", FileType::class, [
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

            $results = $this->amazonS3Client->insertObject($data['profilePict']);
            $data['profilePict'] = $results['link'];

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');
            
            $response = $client->request('GET', 'cs_users?email='.$data['email'], [
                'query' => [
                    'page' => 1,
                ]
            ]);

            if ($response->toArray()["hydra:totalItems"] > 0 && $response->toArray()["hydra:member"][0]['id'] != $storedUser) {
                $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";

                return $this->render('frontend/user/editProfile.html.twig', [
                    'form'=>$form,
                    'errorMessages'=>$errorMessages
                ]);
            }
            
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            
            $response = $client->request('PATCH', 'cs_users/'.$storedUser, [
                'json' => $data,
            ]);

            $redirectResponse = new RedirectResponse($this->generateUrl('myProfile'));

            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'token':
                        $redirectResponse->headers->setCookie(Cookie::create('token', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'roles':
                        $redirectResponse->headers->setCookie(Cookie::create('roles', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'id':
                        $redirectResponse->headers->setCookie(Cookie::create('id', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'profilePict':
                        $redirectResponse->headers->setCookie(Cookie::create('profile_pict', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'lastname':
                        $redirectResponse->headers->setCookie(Cookie::create('lastname', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'firstname':
                        $redirectResponse->headers->setCookie(Cookie::create('firstname', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'email':
                        $redirectResponse->headers->setCookie(Cookie::create('email', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    case 'subscription':
                        $redirectResponse->headers->setCookie(Cookie::create('subscription', $value, 0, '/', null, false, true, false, 'Lax'));
                        break;
                    default:
                        break;
                }
            }

            $response = json_decode($response->getContent(), true);
            $request->getSession()->remove('userId');

            return $redirectResponse;
        }      
        return $this->render('frontend/user/editProfile.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
}
