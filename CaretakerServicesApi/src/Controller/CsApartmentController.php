<?php

namespace App\Controller;

use App\Entity\CsApartment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CsReservation;

class CsApartmentController extends AbstractController
{
    #[Route("/cs_apartments/availables", name:"checkAvailability", methods:["POST"])]
    public function checkAvailability(Request $request, EntityManagerInterface $entityManager): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $startingDate = new \DateTime($requestData['starting_date']);
        $endingDate = new \DateTime($requestData['ending_date']);

        $notAvailableDates = $entityManager->getRepository(CsReservation::class)->findNotAvailableDates($startingDate, $endingDate);

        $apartmentIds = [-1];
        foreach ($notAvailableDates as $occupancyDate) {
            if (isset($occupancyDate->apartment) && !empty($occupancyDate->apartment)) {
                $apartmentId = $occupancyDate->apartment->id;
                if (!in_array($apartmentId, $apartmentIds)) {
                    $apartmentIds[] = $apartmentId;
                }
            }
        }

        $availableAppartments = $entityManager->getRepository(CsApartment::class)->findAvailableApartments($apartmentIds);
        return $this->json($availableAppartments);
    }
}
