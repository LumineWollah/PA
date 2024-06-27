<?php

namespace App\Controller\Frontend;

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

class reviewsController extends AbstractController
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
    
    #[Route('/review/delete', name: 'reviewDeleteFE')]
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
        
        return $this->redirectToRoute('reservationsPast');
    }

    #[Route('/review/show', name: 'reviewShowFE')]
    public function reviewShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reviewData = $request->query->get('review');
        $review = json_decode($reviewData, true);
        
        return $this->render('frontend/reviews/reviewsShow.html.twig', [
            'review'=>$review
        ]);
    }
    
    #[Route('review/create', name: 'myReservationOpinion')]
    public function apartmentCreateCrud(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->cookies->get('id');
        $apartment = $request->query->get('apartment');
        $service = $request->query->get('service');

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
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $data['author'] = 'api/cs_users/'.$id;
            
            if ($service != null) {
                $data['service'] = 'api/cs_services/'.$service;
                unset($data['apartment']);
            } 

            if ($apartment != null) {
                $data['apartment'] = 'api/cs_apartments/'.$apartment;
                unset($data['service']);
            } 

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_reviewss', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('reservationsPast');
        }
            
        return $this->render('frontend/reviews/reviewsCreate.html.twig', [
            'form'=>$form,
            'errorMessage'=>null,
        ]);
    }
}
