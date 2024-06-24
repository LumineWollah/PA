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

    #[Route("/" ,name: 'home')]
    public function home(Request $request)
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

}