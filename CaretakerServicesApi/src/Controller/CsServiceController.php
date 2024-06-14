<?php

namespace App\Controller;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\CsService;
use App\Entity\CsCompany;
use App\Repository\CsCompanyRepository;
use App\Repository\CsCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[ApiResource]
class CsServiceController extends AbstractController
{
    #[Route('/api/cs_services/', name: 'createService', methods: ['POST'])]
    public function createService(Request $request, EntityManagerInterface $entityManager, CsCompanyRepository $companyRepository, CsCategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? null;
        $description = $data['description'] ?? null;
        $price = $data['price'] ?? null;
        $daysOfWeek = $data['daysOfWeek'] ?? null;
        $startTime = $data['startTime'] ?? null;
        $endTime = $data['endTime'] ?? null;
        $price = $data['price'] ?? null;
        $company = explode('/', $data['company']);
        $categoy = explode('/', $data['category']);
        $companyId = end($company) ?? null;
        $categoryId = end($categoy) ?? null;
        $addressInputs = $data['addressInputs'] ?? null;

        if (!$name || !$price || !$companyId || !$categoryId) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $company = $companyRepository->find($companyId);
        $category = $categoryRepository->find($categoryId);

        if (!$company || !$category) {
            return new JsonResponse(['error' => 'Company or Category not found'], 404);
        }

        $service = new CsService();
        $service->setName($name);
        $service->setDescription($description);
        $service->setPrice($price);
        $service->setCompany($company);
        $service->setCategory($category);
        $service->setAddressInputs($addressInputs);
        $service->setDaysOfWeek($daysOfWeek);
        $service->setStartTime($startTime);
        $service->setEndTime($endTime);

        if (!$company->getCategories()->contains($category)) {
            $company->addCategory($category);
        }

        $entityManager->persist($service);
        $entityManager->persist($company);
        $entityManager->flush();

        $jsonService = $serializer->serialize($service, 'json', ['groups' => 'getServices']);

        return new JsonResponse($jsonService, 201, [], true);
    }
}
