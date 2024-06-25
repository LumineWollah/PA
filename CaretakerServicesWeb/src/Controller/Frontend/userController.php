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

    #[Route('/profile/me', name: 'myProfile')]
    public function myProfile(Request $request)
    {
        return $this->render('frontend/user/base.html.twig', [
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
