<?php

namespace App\Controller;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\CsDocument;
use App\Entity\CsReservation;
use App\Entity\CsUser;
use App\Service\AmazonS3Client;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[ApiResource]
class CsDocumentController extends AbstractController
{
    private $amazonS3Client;

    public function __construct(AmazonS3Client $amazonS3Client)
    {
        $this->amazonS3Client = $amazonS3Client;
    }

    #[Route('/api/inventory-form/create', name: 'inventoryFormCreate', methods: ['POST'])]
    public function inventoryFormCreate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = [
            'livingRoomStatus',
            'livingRoomComments',
            'kitchenStatus',
            'kitchenComments',
            'bedroomStatus',
            'bedroomComments',
            'bathroomStatus',
            'bathroomComments',
            'userId',
            'reservId'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            return new JsonResponse([
                'error' => 'Missing required fields: ' . implode(', ', $missingFields)
            ], Response::HTTP_BAD_REQUEST);
        }

        $html = '
        <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap\');
        @import url(\'https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap\');
        img { height: 100px; position: absolute; right: 0; top: 0; }
        h1 { font-family: \'Roboto Condensed\', sans-serif; font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
        p { margin: 0px; margin-bottom: 5px; font-family: \'Quicksand\', sans-serif; }
        </style>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="">État des lieux d\'entrée</h1>
            <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/4_dark_mode_little.png" alt="Logo PCS">
        </div>

        <div style="margin-top: 50px;">
            <p><b>Salon:</b> ' . $data['livingRoomStatus'] . '</p>
            <p>' . nl2br($data['livingRoomComments']) . '</p>
        </div>
        <div style="margin-top: 30px;">
            <p><b>Cuisine:</b> ' . $data['kitchenStatus'] . '</p>
            <p>' . nl2br($data['kitchenComments']) . '</p>
        </div>
        <div style="margin-top: 30px;">
            <p><b>Chambre:</b> ' . $data['bedroomStatus'] . '</p>
            <p>' . nl2br($data['bedroomComments']) . '</p>
        </div>
        <div style="margin-top: 30px;">
            <p><b>Salle de bain:</b> ' . $data['bathroomStatus'] . '</p>
            <p>' . nl2br($data['bathroomComments']) . '</p>
        </div>
        <div style="margin-top: 50px;">
            <p><a href="https://www.caretakerservices.fr" style="color: black;">Paris Caretaker Services</a> • 21 Rue Erard, 75012 Paris</p>
        </div>';

        $dompdf = new Dompdf();
        $dompdf->getOptions()->set('defaultFont', 'Arial');
        $dompdf->getOptions()->set('isRemoteEnabled', true);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $output = $dompdf->output();
        $tempFilePath = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tempFilePath, $output);

        $file_name = 'doc-' . uniqid() . '.pdf';
        $mime_type = 'application/pdf';

        $resultS3 = $this->amazonS3Client->finalInsert($file_name, $tempFilePath, $mime_type);

        if (!$resultS3['success']) {
            return new JsonResponse(false, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $filePath = $resultS3['link'];

        $document = new CsDocument();
        $document->setName($file_name);
        $document->setType("Etat des lieux");
        $document->setUrl($filePath);
        $user = $em->find(CsUser::class, $data['userId']);
        $document->setOwner($user);
        $reservation = $em->find(CsReservation::class, $data['reservId']);
        $document->setattachedReserv($reservation);
        $em->persist($document);

        return new JsonResponse(true, Response::HTTP_OK);
    }

}