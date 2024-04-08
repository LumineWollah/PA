<?php

namespace App\Controller;

use App\Repository\CsUserRepository;
// use App\Entity\CsUser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
// use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// use Symfony\Component\Security\Core\Exception\BadCredentialsException;
// use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
// use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
// use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
// use Symfony\Component\Validator\Validator\ValidatorInterface;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpKernel\Exception\HttpException;
// use Symfony\Component\Security\Http\Attribute\IsGranted;
// use Symfony\Contracts\Cache\ItemInterface;
// use Symfony\Contracts\Cache\TagAwareCacheInterface;
// use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class CsUserController extends AbstractController
{
    #[Route('/api/cs_users/me', name: 'me', methods: ['GET'])]
    public function me(UserInterface $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUsersList = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUsersList, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
