<?php

namespace App\Controller;

use App\Repository\CSUserRepository;
use App\Entity\CSUser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class CSUserController extends AbstractController
{
    #[Route('/api/cs_users/me', name: 'me', methods: ['GET'])]
    public function me(UserInterface $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUsersList = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUsersList, Response::HTTP_OK, ['accept' => 'json'], true);
    }

//     #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les autorisations nécessaires pour ceci")]
//     #[Route('/api/users/', name: 'users', methods: ['GET'])]
//     public function getUsersList(CSUserRepository $userRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
//     {
//         $offset = $request->get('offset', 1);
//         $limit = $request->get('limit', null);

//         $idCache = "getUsersList-" . $offset . "-" . $limit;
//         $jsonUsersList = $cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $offset, $limit, $serializer) {
//             $item->tag("usersCache");
//             $usersList = $userRepository->findAllWithPagination($offset, $limit);
//             return $serializer->serialize($usersList, 'json', ['groups' => 'getUsers']);
//         });

//         return new JsonResponse($jsonUsersList, Response::HTTP_OK, [], true);
//     }

//     #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les autorisations nécessaires pour ceci")]
//     #[Route('/api/user/{id}/', name: 'user', methods: ['GET'])]
//     public function getDetailUser(CSUser $user = null, SerializerInterface $serializer): JsonResponse
//     {
//         if (!$user) {throw new NotFoundHttpException('User not found');}
//         $jsonUsersList = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
//         return new JsonResponse($jsonUsersList, Response::HTTP_OK, ['accept' => 'json'], true);
//     }

//     #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les autorisations nécessaires pour ceci")]
//     #[Route('/api/user/{id}', name: 'deleteUser', methods: ['DELETE'])]
//     public function deleteUser(CSUser $user, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
//     {
//         $cachePool->invalidateTags(["usersCache"]);
//         $em->remove($user);
//         $em->flush();
//         return new JsonResponse(null, Response::HTTP_NO_CONTENT);
//     }

//     #[Route('/api/user', name:"createUser", methods: ['POST'])]
//     public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
//     {
//         $cachePool->invalidateTags(["usersCache"]);
//         $user = $serializer->deserialize($request->getContent(), CSUser::class, 'json');

//         $errors = $validator->validate($user);

//         if ($errors->count() > 0) {
//             return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
//         }
        
//         $em->persist($user);
//         $em->flush();
//         $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
//         $location = $urlGenerator->generate('user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
//         return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
//    }

//    #[Route('/api/user/{id}', name:"updateUser", methods:['PUT'])]
//    public function updateUser(Request $request, SerializerInterface $serializer, CSUser $currentUser, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, TagAwareCacheInterface $cachePool): JsonResponse 
//    {
//         $cachePool->invalidateTags(["usersCache"]);
//         $updatedUser = $serializer->deserialize($request->getContent(), CSUser::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);
        
//         $em->persist($updatedUser);
//         $em->flush();

//         $jsonUser = $serializer->serialize($updatedUser, 'json', ['groups' => 'getUsers']);
//         $location = $urlGenerator->generate('user', ['id' => $updatedUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
//         return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
//   }
}
