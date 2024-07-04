<?php

namespace App\Controller;

use Error;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class CsStripeController extends AbstractController 
{
    private string $stripeKeyPublic;

    public function __construct(string $stripeKeyPrivate, string $stripeKeyPublic)
    {
        $this->stripeKeyPublic = $stripeKeyPublic;
        Stripe::setApiKey($stripeKeyPrivate);
    }

    #[Route('/create-payment-intent', name: 'create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(): JsonResponse
    {
        try {
            $jsonStr = file_get_contents('php://input');
            $jsonObj = json_decode($jsonStr);

            $customer = \Stripe\Customer::create([
                'email' => $jsonObj->user->email,
                'name' => $jsonObj->user->name, 
            ]);

            $ephemeralKey = \Stripe\EphemeralKey::create(
                ['customer' => $customer->id],
                ['stripe_version' => '2022-08-01']);

            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $jsonObj->appart->price * 100,
                'currency' => 'eur',
                'customer' => $customer->id,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'paymentIntent' => $paymentIntent->client_secret,
                'ephemeralKey' => $ephemeralKey->secret,
                'customer' => $customer->id,
                'publishableKey' => $this->stripeKeyPublic,
            ];

            return $this->json($output);
        } catch (Error $e) {
            http_response_code(500);
            return $this->json(['error' => $e->getMessage()]);
        }
    }
}