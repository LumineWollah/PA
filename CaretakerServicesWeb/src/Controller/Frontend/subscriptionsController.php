<?php

// namespace App\Controller\Frontend;

// use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// use Stripe\Stripe;
// use App\Service\ApiHttpClient;
// use Dompdf\Dompdf;
// use Stripe\Checkout\Session;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Mailer\MailerInterface;
// use Symfony\Component\Mime\Email;
// use Symfony\Component\Routing\Annotation\Route;

// class subscriptionsController extends AbstractController {

//     private $apiHttpClient;

//     public function __construct(ApiHttpClient $apiHttpClient, string $stripeKeyPrivate)
//     {
//         $this->apiHttpClient = $apiHttpClient;
//         Stripe::setApiKey($stripeKeyPrivate);
//     }

//     #[Route("/subscribe", "subscribe")]
//     public function subscriptions(Request $request): Response
//     {
//         $emailAdr = $request->cookies->get('email');
//         $lastname = $request->cookies->get('lastname');

//         $customer = \Stripe\Customer::create([
//             'email' => $emailAdr,
//             'name' => $lastname,
//         ]);

//         $product = \Stripe\Product::create([
//             'name' => 'Abonnement',
//         ]);

//         $price = \Stripe\Price::create([
//             'product' => $product->id,
//             'unit_amount' => intval(round(9.90 * 100)),
//             'currency' => 'eur',
//             'recurring' => [
//                 'interval' => 'month',
//             ],
//         ]);

//         $successUrl = $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
//         $cancelUrl = $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

//         // Create a checkout session to collect payment method
//         $session = Session::create([
//             'payment_method_types' => ['card'],
//             'line_items' => [[
//                 'price' => $price->id,
//                 'quantity' => 1,
//             ]],
//             'mode' => 'subscription',
//             'success_url' => $successUrl,
//             'cancel_url' => $cancelUrl,
//             'billing_address_collection' => 'required',
//             'customer' => $customer->id,
//         ]);

//         // Store the payment session ID in the session
//         $request->getSession()->set('paymentId', $session->id);

//         // Redirect to the Stripe checkout page
//         return $this->redirect($session->url);
        
//     }

//     #[Route("/subscription-success", "payment_success")]
//     public function paymentSuccess(Request $request): Response
//     {
//         $sessionId = $request->getSession()->get('paymentId');

//         $session = Session::retrieve($sessionId);
//         $customerId = $session->customer;

//         dd($session);

//         // Create a subscription
//         $subscription = \Stripe\Subscription::create([
//             'customer' => $customerId,
//             'items' => [['price' => intval(round(9.90 * 100))]],
//             'default_payment_method' => $session->payment_intent->payment_method,
//         ]);

//         return new Response('Subscription created successfully!', 200);
//     }

//     #[Route("/subscription-cancel", "payment_cancel")]
//     public function paymentCancel(): Response
//     {
//         // Handle the cancellation
//         return new Response('Subscription cancelled.', 200);
//     }
// }

namespace App\Controller\Frontend;

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

class subscriptionsController extends AbstractController {

    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient, string $stripeKeyPrivate)
    {
        $this->apiHttpClient = $apiHttpClient;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    #[Route("/subscribe", "subscribe")]
    public function subscriptions(Request $request): Response
    {
        $emailAdr = $request->cookies->get('email');
        $lastname = $request->cookies->get('lastname');
        $request->getSession()->set('subsId', $request->query->get('subscription'));

        $customer = \Stripe\Customer::create([
            'email' => $emailAdr,
            'name' => $lastname,
        ]);

        $successUrl = $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $cancelUrl = $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Create a checkout session to collect payment method
        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'setup',
            'setup_intent_data' => [
                'metadata' => [
                    'customer_id' => $customer->id,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'billing_address_collection' => 'required',
            'customer' => $customer->id,
        ]);

        // Store the payment session ID in the session
        $request->getSession()->set('paymentId', $session->id);

        // Redirect to the Stripe checkout page
        return $this->redirect($session->url);
    }

    #[Route("/subscription-success", "payment_success")]
    public function paymentSuccess(Request $request): Response
    {
        $sessionId = $request->getSession()->get('paymentId');
        $subsId = $request->getSession()->get('subsId');

        $session = Session::retrieve($sessionId);
        $setupIntent = \Stripe\SetupIntent::retrieve($session->setup_intent);
        $paymentMethodId = $setupIntent->payment_method;
        $customerId = $session->customer;

        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $customerId]);

        \Stripe\Customer::update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);

        $name = ($subsId == 1) ? 'Abonnement Nomade' : 'Abonnement Aventurier';
        $price = ($subsId == 1) ? 9.90 : 19;

        $product = \Stripe\Product::create([
            'name' => $name,
        ]);

        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => intval(round($price * 100)),
            'currency' => 'eur',
            'recurring' => [
                'interval' => 'month',
            ],
        ]);

        $subscription = \Stripe\Subscription::create([
            'customer' => $customerId,
            'items' => [['price' => $price->id]],
            'default_payment_method' => $paymentMethodId,
        ]);

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $now = new \DateTime();
        $today = $now->format('Y-m-d');

        $response = $client->request('PATCH', 'cs_users/'.$request->cookies->get('id'), [
            'json' => [
                'subsId' => $subscription->id,
                'subscription' => intval($subsId),
                'subsDate' => $today,
            ],
        ]);

        return $this->redirectToRoute('subscriptions');
    }

    #[Route("/subscription-cancel", "payment_cancel")]
    public function paymentCancel(): Response
    {
        // Handle the cancellation
        return new Response('Subscription cancelled.', 200);
    }

    #[Route("/unsubscribe", "unsubscribe")]
    public function unsubscribe(Request $request): Response
    {
        $subscriptionId = $request->query->get('subsId');

        $subscription = \Stripe\Subscription::retrieve($subscriptionId);
        $subscription->cancel();

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

        $response = $client->request('PATCH', 'cs_users/'.$request->cookies->get('id'), [
            'json' => [
                'subsId' => null,
                'subscription' => null,
                'subsDate' => null,
            ],
        ]);

        return $this->redirectToRoute('subscriptions');
    }
}
