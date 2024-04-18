<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use DateTime;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class apartmentsController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/apartment/{id}', name: 'apartmentsDetail')]
    public function apartmentDetail(int $id, Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseApart = $client->request('GET', 'cs_apartments/'.$id);
        
        if ($responseApart->getStatusCode() == 404) {
            return $this->redirectToRoute('apartmentsList');
        }

        $ap = $responseApart->toArray();

        $defaults = [
            "adultTravelers"=>0,
            "childTravelers"=>0,
            "babyTravelers"=>0,
            "price"=>0
        ];

        $responseReserv = $client->request('GET', 'cs_reservations', [
            'query' => [
                'page' => 1,
                'apartment' => $ap['id']
            ]
        ]);

        $reservs = $responseReserv->toArray();

        $datesRangeReservs = [];

        foreach($reservs['hydra:member'] as $reserv) {
            $formattedStartingDate = substr($reserv["startingDate"], 0, 10);
            $formattedEndingDate = substr($reserv["endingDate"], 0, 10);
            $datesRangeReservs[] = [$formattedStartingDate, $formattedEndingDate];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("dates", TextType::class, [
            "attr"=>[
                "placeholder"=>"Départ - Arrivée",
                'autocomplete'=>"off",
                'readonly'=>'readonly'
            ],
            'constraints'=>[
                new NotBlank(),
            ]
        ])
        ->add("adultTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0
            ]
        ])
        ->add("childTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0
            ]
        ])
        ->add("babyTravelers", IntegerType::class, [
            'constraints'=>[
                new NotBlank(),
                new PositiveOrZero()
            ],
            'attr' => [
                'min' => 0
            ]
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $id = $request->cookies->get('id');
            
            if ($id == null) {
                return $this->redirectToRoute('login');
            }

            $dates = explode(" ", $data['dates']);

            $startDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[1]));
            $endDateTime = DateTime::createFromFormat('d/m/Y', trim($dates[3]));

            $duration = $endDateTime->diff($startDateTime)->format("%a");

            $startDateTime = $startDateTime->format('Y-m-d');
            $endDateTime = $endDateTime->format('Y-m-d');

            $data['startingDate'] = $startDateTime;
            $data['endingDate'] = $endDateTime;
            unset($data['dates']);

            $price = $duration * $ap['price'];
            $data['price'] = $price + (0.03 * $price) + ($data['adultTravelers'] * (0.005 * $price));
            
            $data['user'] = 'api/cs_users/'.$id;
            $data['apartment'] = 'api/cs_apartments/'.$ap['id'];

            $response = $client->request('POST', 'cs_apartments/availables/'.$ap['id'], [
                'json' => [
                    'starting_date' => $startDateTime,
                    'ending_date' => $endDateTime
                ]
            ]);

            if (!(($response->toArray())['available'])) {
                return;
            }

            $response = $client->request('POST', 'cs_reservations', [
                'json' => $data,
            ]);

            return $this->redirectToRoute('apartmentsList');
        }

        return $this->render('frontend/apartments/apartmentDetail.html.twig', [
            'apartment'=>$ap,
            'form'=>$form,
            'datesRangeReservs'=>$datesRangeReservs
        ]);
    }

    #[Route('/apartment', name: 'apartmentsList')]
    public function apartmentList(Request $request)
    { 
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseAparts = $client->request('GET', 'cs_apartments');

        $aps = $responseAparts->toArray();

        return $this->render('frontend/apartments/apartmentList.html.twig', [
            'aps'=>$aps['hydra:member']
        ]);
        
    }
}
