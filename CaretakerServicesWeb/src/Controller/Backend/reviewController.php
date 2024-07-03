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
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;

class reviewController extends AbstractController
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

    #[Route('/admin-panel/review/list', name: 'reviewList')]
    public function reviewList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_reviewss', [
            'query' => [
                'page' => 1
            ]
        ]);

        $reviewsList = $response->toArray(); 

        return $this->render('backend/review/reviews.html.twig', [
            'reviews' => $reviewsList['hydra:member'],
        ]);
    }
    
    #[Route('/admin-panel/review/delete', name: 'reviewDelete')]
    public function reviewDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_reviewss/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('reviewList');
    }

    #[Route('/admin-panel/review/edit', name: 'reviewEdit')]
    public function reviewEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reviewData = $request->request->get('review');
        $review = json_decode($reviewData, true);

        $storedReview = $request->getSession()->get('review');

        if (!$storedReview) {
            $request->getSession()->set('review', $review);
        }
        
        try {
            $defaults = [
                'content' => $storedReview['content'],
                'rate' => $storedReview['rate'],
                'author' => $storedReview['author']['firstname'] . ' ' . $storedReview['author']['lastname'],
                'service' => $storedReview['service']['name'],
                'apartment' => $storedReview['apartment']['name'],
            ];
        } catch (Exception $e) {
            if ($e->getMessage() == 'Warning: Undefined array key "apartment"') {
                $defaults = [
                    'content' => $storedReview['content'],
                    'rate' => $storedReview['rate'],
                    'author' => $storedReview['author']['firstname'] . ' ' . $storedReview['author']['lastname'],
                    'service' => $storedReview['service']['name'],
                ];
            } else if ($e->getMessage() == 'Warning: Undefined array key "service"') {
                $defaults = [
                    'content' => $storedReview['content'],
                    'rate' => $storedReview['rate'],
                    'author' => $storedReview['author']['firstname'] . ' ' . $storedReview['author']['lastname'],
                    'apartment' => $storedReview['apartment']['name'],
                ];
            } else {
                dd($e);
            }
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

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();
        $serviceChoice = array();
        $serviceChoice += [ 'Aucun' => null ];

        foreach ($servicesList['hydra:member'] as $service) {
            $serviceChoice += [ $service['name'] => $service['id'] ];
        }
        
        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $apartmentsList = $response->toArray();
        $apartmentChoice = array();
        $apartmentChoice += [ 'Aucun' => null ];

        foreach ($apartmentsList['hydra:member'] as $apartment) {
            $apartmentChoice += [ $apartment['name'] => $apartment['id'] ];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("content", TextType::class, [
            "attr"=>[
                "placeholder"=>"Contenu",
            ],
            "required"=>false,
        ])
        ->add("rate", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Note",
            ],
        ])
        ->add("author", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->add("service", ChoiceType::class, [
            "choices" => $serviceChoice,
        ])
        ->add("apartment", ChoiceType::class, [
            "choices" => $apartmentChoice,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $data['author'] = 'api/cs_users/'.$data['author'];
            
            if (isset($data['apartment']) && isset($data['service'])) {
                $errorMessage = "Veuillez sélectionner un seul appartment ou service";
                return $this->render('backend/review/createReview.html.twig', [
                    'form'=>$form,
                    'errorMessage'=>$errorMessage
                ]);
            }

            if (isset($data['service'])) {
                $data['service'] = 'api/cs_services/'.$data['service'];
                unset($data['apartment']);
            } 

            if (isset($data['apartment'])) {
                $data['apartment'] = 'api/cs_apartments/'.$data['apartment'];
                unset($data['service']);
            } 
            
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $response = $client->request('PATCH', 'cs_reviewss/'.$storedReview['id'], [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('reviewId');

            return $this->redirectToRoute('reviewList');
        }
        return $this->render('backend/review/editReview.html.twig', [
            'defaults' => $defaults,
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/admin-panel/review/show', name: 'reviewShow')]
    public function reviewShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reviewData = $request->request->get('review');
        $review = json_decode($reviewData, true);

        return $this->render('backend/review/showReview.html.twig', [
            'review'=>$review
        ]);
    }
    
    #[Route('/admin-panel/review/create', name: 'reviewCreate')]
    public function reviewCreate(Request $request)
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

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();
        $serviceChoice = array();
        $serviceChoice += [ 'Aucun' => null ];

        foreach ($servicesList['hydra:member'] as $service) {
            $serviceChoice += [ $service['name'] => $service['id'] ];
        }
        
        $response = $client->request('GET', 'cs_apartments', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $apartmentsList = $response->toArray();
        $apartmentChoice = array();
        $apartmentChoice += [ 'Aucun' => null ];

        foreach ($apartmentsList['hydra:member'] as $apartment) {
            $apartmentChoice += [ $apartment['name'] => $apartment['id'] ];
        }
        
        $response = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $reservationsList = $response->toArray();
        $reservationChoice = array();
        $reservationChoice += [ 'Aucun' => null ];

        foreach ($reservationsList['hydra:member'] as $reservation) {
            $reservationChoice += [ $reservation['id'] => $reservation['id'] ];
        }


        $form = $this->createFormBuilder()
        ->add("content", TextType::class, [
            "attr"=>[
                "placeholder"=>"Contenu",
            ],
            "required"=>false,
        ])
        ->add("rate", IntegerType::class, [
            "attr"=>[
                "placeholder"=>"Note",
            ],
            'constraints'=>[
                new GreaterThanOrEqual([
                    'value' => 0,
                    'message' => 'La note doit être comprise entre 0 et 5',
                
                ]),
                new LessThanOrEqual([
                    'value' => 5,
                    'message' => 'La note doit être comprise entre 0 et 5',
                
                ]),
            ],
        ])
        ->add("author", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->add("reservation", ChoiceType::class, [
            "choices" => $reservationChoice,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $data['author'] = 'api/cs_users/'.$data['author'];

            $response = $client->request('GET', 'cs_reservations/'.$data['reservation'], [
                'query' => [
                    'page' => 1,
                ]
            ]);

            $reservationData = $response->toArray();

            if (isset($data['apartment']) && isset($data['service'])) {
                $errorMessage = "Veuillez sélectionner un seul appartment ou service";
                return $this->render('backend/review/createReview.html.twig', [
                    'form'=>$form,
                    'errorMessage'=>$errorMessage
                ]);
            }

            if (isset($reservationData['service'])) {
                $data['service'] = 'api/cs_services/'.$reservationData['service']['id'];
                unset($reservationData['apartment']);
            } 

            if (isset($reservationData['apartment'])) {
                $data['apartment'] = 'api/cs_apartments/'.$reservationData['apartment']['id'];
                unset($reservationData['service']);
            } 

            $data['reservation'] = 'api/cs_reservations/'.$data['reservation'];

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_reviewss', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('reviewList');
        }
            
        return $this->render('backend/review/createReview.html.twig', [
            'form'=>$form,
            'errorMessage'=>null,
        ]);
    }
}
