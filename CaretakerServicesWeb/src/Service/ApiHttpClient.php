<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class ApiHttpClient
{

    public function getClient($bearerToken, $contentType = false)
    {
        $headers = [
            'Accept' => 'application/ld+json',
            'Authorization' => 'Bearer ' . $bearerToken
        ];
        if ($contentType){
            $headers['Content-Type'] = $contentType;
        }
        return HttpClient::create([
            'base_uri' => 'https://api.caretakerservices.fr/api/',
            'headers' => $headers
        ]);
    }

    public function getClientWithoutBearer()
    {
        return HttpClient::create([
            'base_uri' => 'https://api.caretakerservices.fr/api/',
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json'
            ]
        ]);
    }
}
