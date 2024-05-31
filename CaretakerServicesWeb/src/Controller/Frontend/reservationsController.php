<?php

namespace App\Controller\Frontend;

use Stripe\Stripe;
use App\Service\ApiHttpClient;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        
        $success = $this->generateUrl('apartmentList');
        // $reserv = $request->getSession()->get('reservId');

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'EUR',
                    'product_data' => [
                        'name' => 'Réservation appart',
                    ],
                    'unit_amount' => 1000,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url' => $success,
            'billing_address_collection' => 'required',
            //'customer'
        ]);

        return $this->redirect($session->url);
    }

    #[Route('/reservation/{id}/pay/{payId}/success', 'reservPaySucc')]
    public function reservPaySucc(int $id, int $payId){
        
    }

    // #[Route('/reservation/{id}/pay/failure', 'reservPayFail')]

}