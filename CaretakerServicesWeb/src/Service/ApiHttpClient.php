<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class ApiHttpClient
{

    public function getClient($bearerToken)
    {
        return HttpClient::create([
            'base_uri' => 'http://127.0.0.1:8000/api/',
            'headers' => [
                'Accept' => 'application/ld+json',
                'Authorization' => 'Bearer ' . $bearerToken
            ]
        ]);
    }

    public function getClientWithoutBearer()
    {
        return HttpClient::create([
            'base_uri' => 'http://127.0.0.1:8000/api/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ]);
    }
}
