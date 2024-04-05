<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;

class providerController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/provider/list', name: 'providerList')]
    public function providerList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_PROVIDER'
            ]
        ]);
        
        $providersList = $response->toArray();

        // echo "<pre>";
        // print_r($providersList);
        // echo "</pre>";

        return $this->render('backend/providers.html.twig', [
            'providers' => $providersList['hydra:member']
        ]);
    }

#[Route('/admin-panel/provider/delete', name: 'providerDelete')]
public function providerDelete(Request $request)
{
    $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

    $id = $request->query->get('id');

    $response = $client->request('DELETE', 'cs_users', [
        'query' => [
            'id' => $id
        ]
    ]);
}
}
