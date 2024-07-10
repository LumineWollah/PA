<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class ApiHttpClient
{
    private $apiLink;

    public function __construct(string $apiLink)
    {
        $this->apiLink = $apiLink;
    }

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
            // 'base_uri' => 'https://api.caretakerservices.fr/api/',
            'base_uri' => $this->apiLink,
            'headers' => $headers
        ]);
    }

    public function getClientWithoutBearer()
    {
        return HttpClient::create([
            // 'base_uri' => 'https://api.caretakerservices.fr/api/',
            'base_uri' => $this->apiLink,
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json'
            ]
        ]);
    }
}
