<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;

class travelerController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/traveler/list', name: 'travellerList')]
    public function travelerList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_TRAVELER'
            ]
        ]);
        
        $travelersList = $response->toArray();

        // echo "<pre>";
        // print_r($travellersList);
        // echo "</pre>";

        return $this->render('backend/travelers.html.twig', [
            'travelers' => $travelersList['hydra:member']
        ]);
    }
}
