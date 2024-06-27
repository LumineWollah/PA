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

class userController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
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
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', $endpoint, [
            'query' => $query,
        ]);

        return $response->toArray();
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
        
                $response = $client->request('GET', 'cs_apartments', [
                    'json' => [
                        'page' => 1,
                        'owner' => '/api/cs_users/'.$id
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
        
                $response = $client->request('GET', 'cs_services', [
                    'json' => [
                        'page' => 1,
                        'company' => '/api/cs_companies/'.$user['company']['id']
                    ]
                ]);
        
                $services = $response->toArray();

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
        
        $response = $client->request('GET', 'cs_users/'.$id, [
            'json' => [
                'page' => 1,
            ]
        ]);

        $user = $response->toArray();
        $reviewsString = $user['reviews'];

        // TEMPORAIRE

        for ($i = 0; $i < sizeof($reviewsString); $i++) {
                $response = $client->request('GET', $reviewsString[$i], [
                    'json' => [
                        'page' => 1,
                    ]
                ]);
            
            $review = $response->toArray();
            $reviews[$i] = $review;
        }

        // TEMPORAIRE
        // dd($reviews, $reserv);
        for ($i = 0; $i < sizeof($reviews); $i++) {
            if (isset($reviews[$i]['apartment'])) {
                for ($j = 0; $j < sizeof($reserv); $j++) {
                    if ($reserv[$j]['apartment']['id'] == $reviews[$i]['apartment']['id']) {
                        $reserv[$j]['review'] = $reviews[$i];
                    }
                }
            }
        }

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
        
        foreach ($requests['hydra:member'] as $key => $value) {
            if ($value['isRequest'] == false) {
                unset($requests['hydra:member'][$key]);
            }
        }

        return $this->render('frontend/user/requestsList.html.twig', [
            'requests'=>$requests['hydra:member']
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
}
