<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class ApiHttpClient
{

    public function getClient($bearerToken)
    {
        return HttpClient::create([
            'base_uri' => 'https://127.0.0.1:8000/api/',
            'headers' => [
                'Accept' => 'application/ld+json',
                'Authorization' => 'Bearer ' . $bearerToken
            ],
            'verify_peer' => false,
            'verify_host' => false
        ]);
    }
}
