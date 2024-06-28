<?php

namespace App\Controller\Frontend;

use App\Service\AmazonS3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
use Dompdf\Dompdf;
use Stripe\Stripe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as EmailMime;

class providersController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;
    private $stripeKeyPrivate;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    function cropString($string, $maxLength) {
        if (mb_strlen($string) > $maxLength) {
            return mb_substr($string, 0, $maxLength) . '...';
        }
        return $string;
    }

    #[Route('/providers', name: 'providersList')]
    public function providersList(Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseCompanies = $client->request('GET', 'cs_companies');

        $companies = $responseCompanies->toArray()['hydra:member'];

        return $this->render('frontend/companies/companiesList.html.twig', [
            'companies'=>$companies
        ]); 
    }

    #[Route('/providers/{id}', name: 'providersDetail')]
    public function providersDetail(Request $request, int $id, MailerInterface $mailer)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseCompany = $client->request('GET', 'cs_companies/'.$id);

        $company = $responseCompany->toArray();
        
        $id = $request->cookies->get('id');
        $default = [];

        if ($id != null) {
            $default['name'] = $request->cookies->get('lastname').' '.$request->cookies->get('firstname');
            $default['email'] = $request->cookies->get('email');
        }

        $form = $this->createFormBuilder($default)
        ->add("name", TextType::class, [
            'constraints'=>[
                new NotBlank(),
                new Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Votre nom doit contenir au moins 2 caractères',
                    'maxMessage' => 'Votre nom doit contenir au maximum 50 caractères'
                ])
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("email", EmailType::class, [
            'constraints'=>[
                new NotBlank(),
                new Email(),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("message", TextareaType::class, [
            'constraints'=>[
                new NotBlank(),
                new Length([
                    'min' => 10,
                    'max' => 500,
                    'minMessage' => 'Votre message doit contenir au moins 10 caractères',
                    'maxMessage' => 'Votre message doit contenir au maximum 500 caractères'
                ])
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $email = (new EmailMime())
                ->from('ne-pas-repondre@caretakerservices.fr')
                ->to($company['companyEmail'])
                ->subject('Demande d\'aide')
                ->html('
                    <html>
                        <body>
                            <p>Nom: '.$data['name'].'</p>
                            <p>Email: '.$data['email'].'</p>
                            <p>Message: '.$data['message'].'</p>
                        </body>
                    </html>
                ');

            $mailer->send($email);

            $this->addFlash('success', 'Your message has been sent successfully.');
        }

        return $this->render('frontend/companies/companiesDetail.html.twig', [
            'company'=>$company,
            'form'=>$form
        ]);
    }

    #[Route('/quote-requests', name: 'quoteRequests')]
    public function quoteRequests(Request $request)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'myRequests']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $response = $client->request('GET', 'cs_companies?users=['.$id.']');
        $serv = $response->toArray()['hydra:member'][0]['services'];

        $ids = [];

        foreach ($serv as $service) {
            $ids[] = $service['id'];
        }

        $response = $client->request('GET', 'cs_reservations?service[]='.implode('&service[]=', $ids).'&active=false&status=0');
        $requests = $response->toArray();

        foreach ($requests['hydra:member'] as $key => $value) {
            if ($value['isRequest'] == false) {
                unset($requests['hydra:member'][$key]);
            }
        }

        return $this->render('frontend/services/quoteRequestsList.html.twig', [
            'requests'=>$requests['hydra:member']
        ]);
    }

    #[Route('/quote-requests/delete', name: 'quoteRequestDelete')]
    public function quoteRequestDelete(Request $request, MailerInterface $mailer)
    {
        $userId = $request->cookies->get('id');
        
        if ($userId == null) {
            return $this->redirectToRoute('login', ['redirect'=>'quoteRequestDelete']);
        }

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $reservation = $request->request->get('reservationContent');
        $reservation = json_decode($reservation, true);

        $emailCustomer = $reservation['user']['email'];
        $lastnameCustomer = $reservation['user']['lastname'];
        $firstnameCustomer = $reservation['user']['firstname'];
        $dateCreation = (new DateTime($reservation['dateCreation']))->format('d/m/Y');
        $serviceName = $reservation['service']['name'];

        $email = (new EmailMime())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($emailCustomer)
            ->subject('Votre demande de devis a été annulée')
            ->html('<p>Bonjour '.$lastnameCustomer.' '.$firstnameCustomer.',</p><p>Votre demande de devis du '.$dateCreation.' pour le service '.$serviceName.' a été annulée.</p><p>Si vous avez des questions, n\'hésitez pas à nous contacter.</p><p>Cordialement,</p><p>L\'équipe Caretaker Services</p>');

        $mailer->send($email);

        $response = $client->request('DELETE', 'cs_reservations/'.$reservation['id']);

        return $this->redirectToRoute('quoteRequests');
    }

    #[Route('/quote-requests/show', name: 'quoteRequestDetail')]
    public function quoteRequestDetail(Request $request, MailerInterface $mailer)
    {
        $id = $request->cookies->get('id');
            
        if ($id == null) {
            return $this->redirectToRoute('login', ['redirect'=>'quoteRequestDetail']);
        }

        $reservation = $request->request->get('reservation');
        $reservation = json_decode($reservation, true);

        $form = $this->createFormBuilder()
        ->add("price", NumberType::class, [
            "required"=>true,
            'constraints'=>[
                new NotBlank(),
                new Positive()
            ],
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

            $response = $client->request('PATCH', 'cs_reservations/'.$reservation['id'], [
                'json' => [
                    'price' => $data['price'],
                    'status' => 1
                ]
            ]);

            $customerName = strtoupper($reservation['user']['lastname']).' '.ucfirst($reservation['user']['firstname']);
            $reservId = $reservation['id'];
            $total = $data['price'];

            $html = '
            <style>
            @import url(\'https://fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap\');
            @import url(\'https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap\');
            img { height: 100px; position: absolute; right: 0; top: 0; }
            h1 { font-family: \'Roboto Condensed\', sans-serif; font-size: 2rem; font-weight: 700; margin-bottom: 20px; }
            p { margin: 0px; margin-bottom: 5px; font-family: \'Quicksand\', sans-serif; }
            </style>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="">DEVIS</h1>
                <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/4_dark_mode_little.png" alt="Logo PCS">
            </div>

            <div style="position: absolute; margin-top: 50px;">
                <p><b>À L\'ATTENTION DE</b></p><p>'.$customerName.'</p><p>'.$reservation['user']['telNumber'].'</p><p>'.$this->cropString($reservation['user']['email'], 20).'</p>
            </div>
            <div style="position: absolute; left: 30%; margin-top: 50px;">
                <p><b>ENVOYÉ À</b></p><p>'.$customerName.'</p><p>'.$$reservation['user']['telNumber'].'</p><p>'.$this->cropString($reservation['user']['email'], 20).'</p>
            </div>
            <div style="position: absolute; right: 0; margin-top: 50px;">
                <p><b>DEMANDE N° : </b>'.$reservId.'</p><p><b>DEVIS N° : </b>851</p><p><b>CRÉE LE : </b>'.date('d/m/Y').'</p><p><b>ENVOYÉ LE : </b>'.date('d/m/Y').'</p>
            </div>
            <span style="display: block; width: 95% height: 2%; background-color: black; margin-top: 190px; "></span>
            <p style="position: absolute; font-size: 42px;">Total de la facture</p>
            <p style="position: absolute; font-size: 42px; right: 0; ">'.number_format($total, 2).' €</p>
            <span style="display: block; width: 95% height: 1px; background-color: black; margin-top: 84px;"></span>
            <div style="position: absolute; left: 0; margin-top: 25px;">
                <p><b>QTÉ</b></p>
                <p>1</p>
            </div>
            <div style="position: absolute; left: 20%; margin-top: 25px;">
                <p><b>DÉSIGNATION</b></p>
                <p>'.$reservation['service']['name'].'</p>
            </div>
            <div style="position: absolute; right: 25%; margin-top: 25px; text-align: right;">
                <p><b>PRIX UNIT. H.T.</b></p>
                <p>'.number_format($data['price'], 2).'</p><p></p><p></p><p>Total H.T.</p><p>Taxes</p>
            </div>
            <div style="position: absolute; right: 0; margin-top: 25px; text-align: right;">
                <p><b>MONTANT H.T.</b></p>
                <p>'.number_format($data['price'], 2).'</p><p><p></p><p></p><p>'.number_format($total, 2).'</p><p>15.00</p>
            </div>
            <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/Capture+d\'%C3%A9cran+2024-06-11+212043.png" style="position: absolute; right: 0; width: 250px; top: 60%;">
            <p style="position: absolute; bottom: 0;"><a href="https://www.caretakerservices.fr" style="color: black;">Paris Caretaker Services</a> • 21 Rue Erard, 75012 Paris</p>';
            
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
                    "owner" => "api/cs_users/".$id,
                    "attachedReserv" => "api/cs_reservations/".$reservId
                ],
            ]);

            $email = (new EmailMime())
                ->from('ne-pas-repondre@caretakerservices.fr')
                ->to($reservation['user']['email'])
                ->subject('Votre demande de devis a été acceptée')
                ->html('<p>Votre demande de devis pour le service : '.$reservation['service']['name'].' envoyé le '.$reservation['dateCreated'].' a été validée.</p><p><a href="'.$resultS3['link'].'" style="text-decoration: none;">Votre devis ici</a></p>');

            $mailer->send($email);

            return $this->redirectToRoute('quoteRequests');
        }

        return $this->render('frontend/services/quoteRequestsDetail.html.twig', [
            'request'=>$reservation,
            'form'=>$form
        ]);
    }
}
