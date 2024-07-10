<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Dompdf\Dompdf;
use App\Service\AmazonS3Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class reservationController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
    }

    function cropString($string, $maxLength) {
        if (mb_strlen($string) > $maxLength) {
            return mb_substr($string, 0, $maxLength) . '...';
        }
        return $string;
    }

    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }

    #[Route('/admin-panel/reservation/list', name: 'reservationList')]
    public function reservationList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $request->getSession()->remove('reservationId');

        $response = $client->request('GET', 'cs_reservations?unavailability=false', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $reservationsList = $response->toArray();
        
        return $this->render('backend/reservation/reservations.html.twig', [
            'reservations' => $reservationsList['hydra:member']
        ]);
    }

    #[Route('/admin-panel/reservation/delete', name: 'reservationDelete')]
    public function reservationDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_reservations/'.$id);

        return $this->redirectToRoute('reservationList');
    }

    #[Route('/admin-panel/reservation/edit', name: 'reservationEdit')]
    public function reservationEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reservationData = $request->request->get('reservation');
        $reservation = json_decode($reservationData, true);

        $storedReservation = $request->getSession()->get('reservationId');

        if (!$storedReservation) {
            $request->getSession()->set('reservationId', $reservation['id']);
        }

        try {
            $defaults = [
                'startingDate' => new \DateTime($reservation['startingDate']),
                'endingDate' => new \DateTime($reservation['endingDate']),
                'price' => $reservation['price'],
                'client' => $reservation['user']['id'],
                'isRequest' => $reservation['isRequest']
            ];

            if (isset($reservation['service'])) {
                $defaults['service'] = $reservation['service']['id'];
            }elseif (isset($reservation['apartment'])) {
                $defaults['apartment'] = $reservation['apartment']['id'];
            }
        } catch (Exception $e) {
            $defaults = [];
        }
        
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [ $user['firstname'].' '.$user['lastname'] => $user['id'] ];
        }
        
        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();
        $serviceChoice = array();

        foreach ($servicesList['hydra:member'] as $service) {
            $serviceChoice += [ $service['name'] => $service['id'] ];
        }

        $response = $client->request('GET', 'cs_apartments?active=1', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $apartmentsList = $response->toArray();
        $apartmentChoice = array();

        foreach ($apartmentsList['hydra:member'] as $apartment) {
            $apartmentChoice += [ $apartment['name'] => $apartment['id'] ];
        }

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();
        $serviceChoice = array();

        foreach ($servicesList['hydra:member'] as $service) {
            $serviceChoice += [ $service['name'] => $service['id'] ];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("startingDate", DateType::class, [
            "attr"=>[
                "placeholder"=>"Date de départ",
            ], 
        ])
        ->add("endingDate", DateType::class, [
            "attr"=>[
                "placeholder"=>"Date de fin",
            ], 
        ])
        ->add("price", NumberType::class, [
            "attr"=>[
                "placeholder"=>"Prix",
            ],
            'constraints'=>[
                new GreaterThanOrEqual(0),
            ],
        ])
        ->add("service", ChoiceType::class, [
            "choices" => $serviceChoice,
            "required"=>false,
        ])
        ->add("apartment", ChoiceType::class, [
            "choices" => $apartmentChoice,
            "required"=>false,
        ])
        ->add("client", ChoiceType::class, [
            "choices" => $userChoice,
        ])
        ->add("isRequest", CheckboxType::class, [
            "label" => "Demande",
            "required" => false,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            if ($data['service'] != null) {
                $data['service'] = 'api/cs_services/'.$data['service'];
            }else{
                unset($data['service']);
            }

            if ($data['apartment'] != null) {
                $data['apartment'] = 'api/cs_apartments/'.$data['apartment']; 
            }else{
                unset($data['apartment']);
            }
            $data['client'] = 'api/cs_users/'.$data['client'];
            
            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');
            
            $data['startingDate'] = $data['startingDate']->format('Y-m-d');
            $data['endingDate'] = $data['endingDate']->format('Y-m-d');
            
            $response = $client->request('PATCH', 'cs_reservations/'.$storedReservation, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('userId');
            $request->getSession()->remove('reservationId');

            return $this->redirectToRoute('reservationList');
        }

        return $this->render('backend/reservation/editReservation.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/admin-panel/reservation/show', name: 'reservationShow')]
    public function reservationShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $reservationData = $request->request->get('reservation');
        $reservation = json_decode($reservationData, true);
        
        return $this->render('backend/reservation/showReservation.html.twig', [
            'reservation'=>$reservation
        ]);
    }

    #[Route('/admin-panel/reservation/create', name: 'reservationCreate')]
    public function reservationCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {
            return $this->redirectToRoute('login');
        }

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $usersList = $response->toArray();
        $userChoice = array();

        foreach ($usersList['hydra:member'] as $user) {
            $userChoice += [$user['firstname'] . ' ' . $user['lastname'] => $user['id']];
        }

        $response = $client->request('GET', 'cs_services', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $servicesList = $response->toArray();
        $serviceChoice = array();

        foreach ($servicesList['hydra:member'] as $service) {
            $serviceChoice += [$service['name'] => $service['id']];
        }

        $response = $client->request('GET', 'cs_apartments?active=1', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $apartmentsList = $response->toArray();
        $apartmentChoice = array();

        foreach ($apartmentsList['hydra:member'] as $apartment) {
            $apartmentChoice += [$apartment['name'] => $apartment['id']];
        }

        $form = $this->createFormBuilder()
            ->add("startingDate", DateType::class, [
                "attr" => [
                    "placeholder" => "Date de départ",
                ],
            ])
            ->add("endingDate", DateType::class, [
                "attr" => [
                    "placeholder" => "Date de fin",
                ],
            ])
            ->add("price", NumberType::class, [
                "attr" => [
                    "placeholder" => "Prix",
                ],
                'constraints' => [
                    new GreaterThanOrEqual(0),
                ],
            ])
            ->add("service", ChoiceType::class, [
                "choices" => $serviceChoice,
                "required" => false,
            ])
            ->add("apartment", ChoiceType::class, [
                "choices" => $apartmentChoice,
                "required" => false,
            ])
            ->add("client", ChoiceType::class, [
                "choices" => $userChoice,
            ])
            ->add("isRequest", CheckboxType::class, [
                "label" => "Demande",
                "required" => false,
            ])
            ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($data['service'] != null) {
                $data['service'] = 'api/cs_services/' . $data['service'];
            } else {
                unset($data['service']);
            }

            if ($data['apartment'] != null) {
                $data['apartment'] = 'api/cs_apartments/' . $data['apartment'];
            } else {
                unset($data['apartment']);
            }
            $data['user'] = 'api/cs_users/' . $data['client'];

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $data['startingDate'] = $data['startingDate']->format('Y-m-d');
            $data['endingDate'] = $data['endingDate']->format('Y-m-d');

            $response = $client->request('POST', 'cs_reservations', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);
            $reservId = $response['id'];
            $userId = $data['client'];

            if (isset($data['apartment'])){
                $objId = explode('/', $data['apartment'])[2];
                $apResp = $client->request('GET', 'cs_apartments/'.$objId);
                $obj = $apResp->toArray();
            } else {
                $objId = explode('/', $data['service'])[2];
                $servResp = $client->request('GET', 'cs_services/'.$objId);
                $obj = $servResp->toArray();
            }

            $userResp = $client->request('GET', 'cs_users/'.$userId);
            $user = $userResp->toArray();

            $qteArray = "";
            $servName = "";
            $servPrice = "";
            $total = $data['price'];

            $customerName = strtoupper($user['lastname']).' '.ucfirst($user['firstname']);

            $html = '
            <style>
            @import url(\'https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap\');
            @import url(\'https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap\');
            img { height: 100px; position: absolute; right: 0; top: 0; }
            h1 { font-family: \'Roboto Condensed\', sans-serif; font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
            p { margin: 0px; margin-bottom: 5px; font-family: \'Quicksand\', sans-serif; }
            </style>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="">FACTURE</h1>
                <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/4_dark_mode_little.png" alt="Logo PCS">
            </div>

            <div style="position: absolute; margin-top: 50px;">
                <p><b>FACTURÉ À</b></p><p>'.$customerName.'</p><p>'.$user['telNumber'].'</p><p>'.$this->cropString($user['email'], 20).'</p>
            </div>
            <div style="position: absolute; left: 30%; margin-top: 50px;">
                <p><b>ENVOYÉ À</b></p><p>'.$customerName.'</p><p>'.$user['telNumber'].'</p><p>'.$this->cropString($user['email'], 20).'</p>
            </div>
            <div style="position: absolute; right: 0; margin-top: 50px;">
                <p><b>RÉSERVATION N° : </b>'.$reservId.'</p><p><b>FACTURE N° : </b>851</p><p><b>PAYÉ LE : </b>'.date('d/m/Y').'</p><p><b>ENVOYÉ LE : </b>'.date('d/m/Y').'</p>
            </div>
            <span style="display: block; width: 95% height: 2%; background-color: black; margin-top: 190px; "></span>
            <p style="position: absolute; font-size: 42px;">Total de la facture</p>
            <p style="position: absolute; font-size: 42px; right: 0; ">'.number_format($total, 2).' €</p>
            <span style="display: block; width: 95% height: 1px; background-color: black; margin-top: 84px;"></span>
            <div style="position: absolute; left: 0; margin-top: 25px;">
                <p><b>QTÉ</b></p>
                <p>1</p>'.$qteArray.'
            </div>
            <div style="position: absolute; left: 20%; margin-top: 25px;">
                <p><b>DÉSIGNATION</b></p>
                <p>'.$obj['name'].'</p>'.$servName.'
            </div>
            <div style="position: absolute; right: 25%; margin-top: 25px; text-align: right;">
                <p><b>PRIX UNIT. H.T.</b></p>
                <p>'.number_format($data['price'], 2).'</p>'.$servPrice.'<p></p><p></p><p>Total H.T.</p><p>Taxes</p>
            </div>
            <div style="position: absolute; right: 0; margin-top: 25px; text-align: right;">
                <p><b>MONTANT H.T.</b></p>
                <p>'.number_format($data['price'], 2).'</p>'.$servPrice.'<p><p></p><p></p><p>'.number_format($total, 2).'</p><p>15.00</p>
            </div>
            <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/Capture+d\'%C3%A9cran+2024-06-11+212043.png" style="position: absolute; right: 0; width: 250px; top: 60%;">
            <p style="position: absolute; bottom: 0;"><a href="https://www.caretakerservices.fr" style="color: black;">Paris Caretaker Services</a> â€¢ 21 Rue Erard, 75012 Paris</p>';
            
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

            $responseDoc = $client->request('POST', 'cs_documents', [
                'json' => [
                    "name" => $file_name,
                    "type" => "Facture",
                    "url" => $resultS3['link'],
                    "owner" => "api/cs_users/".$userId,
                    "attachedReserv" => "api/cs_reservations/".$reservId
                ],
            ]);

                return $this->redirectToRoute('reservationList');
            }

        return $this->render('backend/reservation/createReservation.html.twig', [
            'form' => $form,
            'errorMessage' => null,
        ]);
    }
}
