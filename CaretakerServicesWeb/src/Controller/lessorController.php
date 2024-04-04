<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;

class lessorController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/lessor/list', name: 'lessorList')]
    public function lessorList()
    {
        $_SESSION["token"] = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTIyNDgyNjcsImV4cCI6MTcxMjI1MTg2Nywicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6Imxlb3BvbGQuZ291ZGllckBnbWFpbC5jb20ifQ.NYPvwAR4IU-ryDMSq-e4lE5FhyQYJvQD7CMeKTdRFmTrZBhilPunS-uPzETG2R_uBRpmyA-e7X6EqSDNLjtAu3X6BlWMng6-YKLI1PTuZCQ7yNDT310zUkgi3BKQcPck0ndTEw9gRvEC0lM2P-JrV5NSNHJUM1v2fhfIOzE3J1UPmw-Fo5LMH4ukihNH5C8hrZTGxcL2dn0DfJz9iTR8NfmH_tdPNFxQTc33O5I499GvgIGARc4-yPoNEPPPSvouRPEDduWLGjI7VuZOE8XzmBj9wSqUYU71RELdp1LFTBMDRuSVRZ-OziKDqH4e546coeIDvPtDCVIHsYsNddbwu00oVIeW5H09UUJLRP8wnucFAmmk0qBwUbmxCpJ32cQo6-CzlpBUvN-ZClXmbAKHZRRI6cbqXys6QHmfTwETmHkxoO3kYEMmXZ6Y2yLC6yNZFYBHwDNouF0XNRcinbphSUTbQEkiiiTRP2QiBAQJZ-Oozub-7jPO9n5bhDR0NRTpROYPS19S3Z05Zkcmmvo50-wET1WdNlfO6dc5Y0Rq0GoO1iaozAKfxvPKUHBlHyqbMyjHbgjGN8SmMBhgaZxpRQTJFm1YqSjyRvFgbi8lo5qricIlWC9te2WgIheDHfU-WNhECeLlG0pwz7YEmjf9in0DWmjLGcgc27CbZFvdldc";

        $client = $this->apiHttpClient->getClient($_SESSION["token"]);

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_LESSOR'
            ]
        ]);
        
        $lessorsList = $response->toArray();

        // echo "<pre>";
        // print_r($lessorsList);
        // echo "</pre>";

        return $this->render('backend/lessors.html.twig', [
            'lessors' => $lessorsList['hydra:member']
        ]);
    }
}
