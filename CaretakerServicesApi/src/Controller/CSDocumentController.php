<?php

namespace App\Controller;

use App\Repository\CSDocumentRepository;
use App\Entity\CSDocument;
use App\Repository\CSUserRepository;
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
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class CSDocumentController extends AbstractController
{
//     #[Route('/api/documents/', name: 'documents', methods: ['GET'])]
//     public function getDocumentsList(CSDocumentRepository $docRepository, SerializerInterface $serializer): JsonResponse
//     {
//         $docsList = $docRepository->findAll();
//         $jsonDocsList = $serializer->serialize($docsList, 'json', ['groups' => 'getDocuments']);
//         return new JsonResponse($jsonDocsList, Response::HTTP_OK, [], true);
//     }

//     #[Route('/api/document/{id}/', name: 'document', methods: ['GET'])]
//     public function getDetailDocument(CSDocument $document = null, SerializerInterface $serializer): JsonResponse
//     {
//         if (!$document) {throw new NotFoundHttpException('Document not found');}
//         $jsonDocsList = $serializer->serialize($document, 'json', ['groups' => 'getDocuments']);
//         return new JsonResponse($jsonDocsList, Response::HTTP_OK, ['accept' => 'json'], true);
//     }

//     #[Route('/api/document/{id}', name: 'deleteDocument', methods: ['DELETE'])]
//     public function deleteUser(CSDocument $doc, EntityManagerInterface $em): JsonResponse 
//     {
//         $em->remove($doc);
//         $em->flush();
//         return new JsonResponse(null, Response::HTTP_NO_CONTENT);
//     }

//     #[Route('/api/document', name:"createDocument", methods: ['POST'])]
//     public function createDocument(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, CSUserRepository $CSUserRepository): JsonResponse 
//     {
//         $doc = $serializer->deserialize($request->getContent(), CSDocument::class, 'json');

//         $content = $request->toArray();
//         $idOwner = $content['owner_id'] ?? -1;
//         $owner = $CSUserRepository->find($idOwner);
//         if (!$owner) {throw new NotFoundHttpException('User not found');}

//         $doc->setOwner($owner);

//         $em->persist($doc);
//         $em->flush();

//         $jsonDoc = $serializer->serialize($doc, 'json', ['groups' => 'getDocuments']);
//         $location = $urlGenerator->generate('document', ['id' => $doc->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
//         return new JsonResponse($jsonDoc, Response::HTTP_CREATED, ["Location" => $location], true);
//    }

//    #[Route('/api/document/{id}', name:"updateDoc", methods:['PUT'])]
//    public function updateDoc(Request $request, SerializerInterface $serializer, CSDocument $currentDoc, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, CSUserRepository $CSUserRepository): JsonResponse 
//    {
//        $updatedDoc = $serializer->deserialize($request->getContent(), CSDocument::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentDoc]);
//        $content = $request->toArray();

//        $idOwner = $content['owner_id'] ?? -1;
//        if ($idOwner != -1){$updatedDoc->setOwner($CSUserRepository->find($idOwner));}

//        $em->persist($updatedDoc);
//        $em->flush();

//        $jsonDoc = $serializer->serialize($updatedDoc, 'json', ['groups' => 'getDocuments']);
//        $location = $urlGenerator->generate('document', ['id' => $updatedDoc->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
//        return new JsonResponse($jsonDoc, Response::HTTP_CREATED, ["Location" => $location], true);
//   }
}
