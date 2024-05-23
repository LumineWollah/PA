<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\CsReservation;
use App\Entity\CsUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

// #[ApiResource]
class CsUserController extends AbstractController
{
    #[Route('/api/cs_users/me', name: 'me', methods: ['GET'])]
    public function me(CsUser $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUsersList = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUsersList, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route("/api/cs_users/{id}/reservations", name:"getReservByDate", methods:["POST"])]
    public function getReservByDate(Request $request, EntityManagerInterface $entityManager, CsUser $user): Response
    {
        $requestData = json_decode($request->getContent(), true);
        // PAST - PRESENT - FUTURE
        $time = $requestData['time'];

        switch($time){
            case 'PAST':
                $reserv = $entityManager->getRepository(CsReservation::class)->getPastReserv(new \DateTime(), $user);
                break;
            case 'PRESENT':
                $reserv = $entityManager->getRepository(CsReservation::class)->getPresentReserv(new \DateTime(), $user);
                break;
            default:
                $reserv = $entityManager->getRepository(CsReservation::class)->getFutureReserv(new \DateTime(), $user);
        }

        return $this->json($reserv, 200, [], ['groups' => 'getReservations']);
    }
}