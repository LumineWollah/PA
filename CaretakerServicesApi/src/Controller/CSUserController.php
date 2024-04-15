<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\CsUser;

#[ApiResource]
class CsUserController extends AbstractController
{
    #[Route('/api/cs_users/me', name: 'me', methods: ['GET'])]
    public function me(CsUser $user, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse($user);
        // $jsonUsersList = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        // return new JsonResponse($jsonUsersList, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
