<?php

namespace App\Controller;

use App\Entity\CsCompany;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class CsCompanyController extends AbstractController
{
    #[Route('/api/cs_companies/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CsCompany $company, EntityManagerInterface $em): JsonResponse
    {
        $users = $company->getUsers();
        foreach ($users as $user) {
            $user->setCompany(null);
            $em->persist($user);
        }

        $em->remove($company);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
