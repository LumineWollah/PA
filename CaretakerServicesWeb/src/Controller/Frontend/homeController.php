<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;


class homeController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route("/" , name: 'home')]
    public function home()
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);
        $response = $response->toArray();

        return $this->render('frontend/home.html.twig',[
            'userCount'=>$response["hydra:totalItems"],
        ]);
    }

    #[Route("/waiting-room" , name: 'waitingRoom')]
    public function waitingRoom(Request $request)
    {
        $lastname = $request->cookies->get('lastname');
        $firstname = $request->cookies->get('firstname');
        return $this->render('frontend/waitingRoom.html.twig', [
            'lastname' => $lastname,
            'firstname' => $firstname
        ]);
    }

}