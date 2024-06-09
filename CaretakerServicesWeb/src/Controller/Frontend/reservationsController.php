<?php

namespace App\Controller\Frontend;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use App\Service\ApiHttpClient;
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
    private $stripeKeyPrivate;

    public function __construct(ApiHttpClient $apiHttpClient, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        Stripe::setApiKey($stripeKeyPrivate);

    }

    #[Route('/reservation/{id}/pay', 'reservPay')]
    public function reservPay(Request $request){
        $data = $request->getSession()->get('reservData');
        $apName = $request->getSession()->get('apName');

        $success = $this->generateUrl('reservPaySucc', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $failure = $this->generateUrl('reservPayFail');

        $emailAdr = $request->cookies->get('email');

        $customer = \Stripe\Customer::create([
            'email' => $emailAdr,
            'name' => $request->cookies->get('lastname'), 
        ]);
    
        $product = \Stripe\Product::create([
            'name' => $apName,
        ]);

        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => intval(round($data['price'] * 100)),
            'currency' => 'eur',
        ]);

        // \Stripe\InvoiceItem::create([
        //     'customer' => $customer->id,
        //     'price' => $price->id,
        // ]);

        // $invoice = \Stripe\Invoice::create([
        //     'customer' => $customer->id,
        //     'auto_advance' => true,
        // ]);

        // $invoice->finalizeInvoice();
        
        // // $invoice = \Stripe\Invoice::retrieve($invoice->id);
        // $pdfUrl = $invoice->invoice_pdf;

        // $tempPdfPath = sys_get_temp_dir() . '/facture.pdf';
        // file_put_contents($tempPdfPath, file_get_contents($pdfUrl));

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

    #[Route('/reservation/pay/success', 'reservPaySucc')]
    public function reservPaySucc(Request $request, MailerInterface $mailer){
        $data = $request->getSession()->get('reservData');
        $client = $this->apiHttpClient->getClientWithoutBearer();
        
        $sessionId = $request->getSession()->get('paymentId');
        $session = Session::retrieve($sessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $data['payementId'] = $paymentIntent->id;

        $response = $client->request('POST', 'cs_reservations', [
            'json' => $data,
        ]);

        $emailAdr = $request->cookies->get('email');

        $email = (new Email())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($emailAdr)
            ->subject('Votre réservation')
            ->html('<p>Votre réservation pour le #### a bien été validée</p>');

        $mailer->send($email);
        
        return $this->redirectToRoute('apartmentsList');
    }

    #[Route('/reservation/pay/failure', 'reservPayFail')]
    public function reservPayFail(){
        #...
    }

}