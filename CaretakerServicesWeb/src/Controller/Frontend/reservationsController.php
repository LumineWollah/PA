<?php

namespace App\Controller\Frontend;

use App\Service\AmazonS3Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use App\Service\ApiHttpClient;
use Dompdf\Dompdf;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class reservationsController extends AbstractController
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

    #[Route('/reservation/{id}/pay', 'reservPay')]
    public function reservPay(Request $request){
        $data = $request->getSession()->get('reservData');
        $objName = $request->getSession()->get('objName');

        $success = $this->generateUrl('reservPaySucc', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $failure = $this->generateUrl('reservPayFail');

        $emailAdr = $request->cookies->get('email');

        $customer = \Stripe\Customer::create([
            'email' => $emailAdr,
            'name' => $request->cookies->get('lastname'), 
        ]);
    
        $product = \Stripe\Product::create([
            'name' => $objName,
        ]);

        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => intval(round($data['price'] * 100)),
            'currency' => 'eur',
        ]);

        $lineItems = [[
            'price' => $price->id,
            'quantity' => 1,
        ]];
            
        foreach($data['servicesCompletes'] as $service){
            $product = \Stripe\Product::create([
                'name' => $service['name'],
            ]);

            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => intval(round($service['price'] * 100)),
                'currency' => 'eur',
            ]);

            $lineItems[] = [
                'price' => $price->id,
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $success,
            'billing_address_collection' => 'required',
            'customer' => $customer->id,
        ]);

        $request->getSession()->set('paymentId', $session->id);
        return $this->redirect($session->url);
    }

    #[Route('/reservation/pay/success', 'reservPaySucc')]
    public function reservPaySucc(Request $request, MailerInterface $mailer){
        $data = $request->getSession()->get('reservData');
        $client = $this->apiHttpClient->getClientWithoutBearer();
        
        $sessionId = $request->getSession()->get('paymentId');
        $session = Session::retrieve($sessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $data['payementId'] = $paymentIntent->id;

        $userId = explode('/', $data['user'])[2];
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

        if (isset($data['servicesCompletes'])){
            for ($i=0; $i < count($data['servicesCompletes']); $i++) { 
                $qteArray .= "<p>1</p>";
                $servName .= "<p>".$data['servicesCompletes'][$i]['name']."</p>";
                $servPrice .= "<p>".number_format($data['servicesCompletes'][$i]['price'], 2)."</p>";
                $total += $data['servicesCompletes'][$i]['price'];
            }
        }

        unset($data['servicesCompletes']);

        $response = $client->request('POST', 'cs_reservations', [
            'json' => $data,
        ]);

        $reservId = $response->toArray()['id'];

        $emailAdr = $request->cookies->get('email');

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
                "owner" => "api/cs_users/".$userId,
                "attachedReserv" => "api/cs_reservations/".$reservId
            ],
        ]);

        $email = (new Email())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($emailAdr)
            ->subject('Votre réservation')
            ->html('<p>Votre réservation pour le #### a bien été validée</p><p><a href="'.$resultS3['link'].'" style="text-decoration: none;">Votre facture ici</a></p>');

        $mailer->send($email);
        
        return $this->redirectToRoute('myProfile');
    }

    #[Route('/reservation/pay/failure', 'reservPayFail')]
    public function reservPayFail(){
        #...
    }

    #[Route('/request/{id}/pay', 'requestPay')]
    public function requestPay(Request $request){
        $reserv = $request->request->get('reservation');
        $reserv = json_decode($reserv, true);
        $request->getSession()->set('reservData', $reserv);

        $success = $this->generateUrl('requestPaySucc', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $failure = $this->generateUrl('reservPayFail');

        $emailAdr = $request->cookies->get('email');

        $customer = \Stripe\Customer::create([
            'email' => $emailAdr,
            'name' => $request->cookies->get('lastname'), 
        ]);
    
        $product = \Stripe\Product::create([
            'name' => $reserv['service']['name'],
        ]);

        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => intval(round($reserv['price'] * 100)),
            'currency' => 'eur',
        ]);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price->id,
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $success,
            'billing_address_collection' => 'required',
            'customer' => $customer->id,
        ]);

        $request->getSession()->set('paymentId', $session->id);
        return $this->redirect($session->url);
    }

    #[Route('/request/pay/success', 'requestPaySucc')]
    public function requestPaySucc(Request $request, MailerInterface $mailer){
        $data = $request->getSession()->get('reservData');
        $client = $this->apiHttpClient->getClientWithoutBearer();
        
        $sessionId = $request->getSession()->get('paymentId');
        $session = Session::retrieve($sessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $data['payementId'] = $paymentIntent->id;

        $userId = $data['user']['id'];

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $response = $client->request('PATCH', 'cs_reservations/'.$data['id'], [
            'json' => [
                'status' => null,
                'isRequest' => false,
                'active' => true,
                'payementId' => $data['payementId']
            ],
        ]);

        $reservId = $response->toArray()['id'];

        $response = $client->request('DELETE', 'cs_documents/'.$data['documents'][0]['id']);

        $emailAdr = $request->cookies->get('email');

        $customerName = strtoupper($data['user']['lastname']).' '.ucfirst($data['user']['firstname']);

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
            <h1 style="">FACTURE</h1>
            <img src="https://caretakerservices.s3.eu-west-2.amazonaws.com/4_dark_mode_little.png" alt="Logo PCS">
        </div>

        <div style="position: absolute; margin-top: 50px;">
            <p><b>FACTURÉ À</b></p><p>'.$customerName.'</p><p>'.$data['user']['telNumber'].'</p><p>'.$this->cropString($data['user']['email'], 20).'</p>
        </div>
        <div style="position: absolute; left: 30%; margin-top: 50px;">
            <p><b>ENVOYÉ À</b></p><p>'.$customerName.'</p><p>'.$data['user']['telNumber'].'</p><p>'.$this->cropString($data['user']['email'], 20).'</p>
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
            <p>1</p>
        </div>
        <div style="position: absolute; left: 20%; margin-top: 25px;">
            <p><b>DÉSIGNATION</b></p>
            <p>'.$data['service']['name'].'</p>
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

        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseDoc = $client->request('POST', 'cs_documents', [
            'json' => [
                "name" => $file_name,
                "type" => "Facture",
                "url" => $resultS3['link'],
                "owner" => "api/cs_users/".$userId,
                "attachedReserv" => "api/cs_reservations/".$reservId
            ],
        ]);

        $email = (new Email())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($emailAdr)
            ->subject('Votre réservation')
            ->html('<p>Votre réservation pour le #### a bien été validée</p><p><a href="'.$resultS3['link'].'" style="text-decoration: none;">Votre facture ici</a></p>');

        $mailer->send($email);
        
        return $this->redirectToRoute('myProfile');
    }

    #[Route('/reservation/detail', 'myReservationDetail')]
    public function myReservationDetail(Request $request){
        
        $reservationData = $request->request->get('reservation');
        $reservation = json_decode($reservationData, true);
    
        return $this->render('frontend/user/reservDetail.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/documents', 'myReservationDocuments')]
    public function myReservationDocuments(Request $request, int $id){
        $client = $this->apiHttpClient->getClientWithoutBearer();
        $response = $client->request('GET', 'cs_reservations/'.$id);
        $reservation = $response->toArray();

        return $this->render('frontend/reservations/documents.html.twig', [
            'reservation' => $reservation,
        ]);
    }

}