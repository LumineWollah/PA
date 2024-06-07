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
    public function reservPay(Request $request, MailerInterface $mailer){
        $data = $request->getSession()->get('reservData');
        $apName = $request->getSession()->get('apName');

        $success = $this->generateUrl('reservPaySucc', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $failure = $this->generateUrl('reservPayFail');

        $email = $request->cookies->get('email');
        var_dump($email);
        $customer = \Stripe\Customer::create([
            'email' => $email,
            'name' => $request->cookies->get('lastname'), 
        ]);
    
        $product = \Stripe\Product::create([
            'name' => $apName,
        ]);
    
        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => $data['price'] * 100,
            'currency' => 'eur',
        ]);

        \Stripe\InvoiceItem::create([
            'customer' => $customer->id,
            'price' => $price->id,
        ]);

        $invoice = \Stripe\Invoice::create([
            'customer' => $customer->id,
            'auto_advance' => true,
        ]);

        $invoice->finalizeInvoice();
        
        $invoice = \Stripe\Invoice::retrieve($invoice->id);
        $pdfUrl = $invoice->invoice_pdf;

        $email = (new Email())
            ->from('ne-pas-repondre@caretakerservices.fr')
            ->to($email)
            ->subject('Votre facture pour la réservation')
            ->html('<p>Veuillez trouver votre facture en pièce jointe.</p>')
            ->attachFromPath($pdfUrl, 'facture.pdf');

        $mailer->send($email);

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
    public function reservPaySucc(Request $request){
        $data = $request->getSession()->get('reservData');
        $client = $this->apiHttpClient->getClientWithoutBearer();
        
        $sessionId = $request->getSession()->get('paymentId');
        $session = Session::retrieve($sessionId);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $data['payementId'] = $paymentIntent->id;

        $response = $client->request('POST', 'cs_reservations', [
            'json' => $data,
        ]);
        
        return $this->redirectToRoute('apartmentsList');
    }

    #[Route('/reservation/pay/failure', 'reservPayFail')]
    public function reservPayFail(){
        #...
    }

    #[Route('/reservation/{id}/refund', 'reservRefund')]
    public function reservRefund(int $id){
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseReserv = $client->request('GET', 'cs_reservations/'.$id);
        $reserv = $responseReserv->toArray();
        
        $refund = \Stripe\Refund::create([
            'charge' => $reserv['paymentId'],
            'amount' => $reserv['price'] * 100,
        ]);
    
        if ($refund->status == 'succeeded') {
            $client->request('PATCH', 'cs_reservations/'.$id, [
                'active' => false
            ]);
        }
    }

}