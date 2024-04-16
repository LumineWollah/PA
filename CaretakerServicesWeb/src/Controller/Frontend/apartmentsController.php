<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
            echo "PAS TROUVÉ";
        }

        $ap = $responseApart->toArray();

        $form = $this->createFormBuilder()
        ->add("startingDate", TextType::class, [
            "attr"=>[
                "placeholder"=>"Votre email"
            ],
            'constraints'=>[
                new NotBlank()
            ]
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            print_r($data);
            $dates = explode(" - ", $data['startingDate']);
            echo trim($dates[0]);
            echo trim($dates[1]);
            return;
        }

        return $this->render('frontend/apartments/apartmentDetail.html.twig', [
            'apartment'=>$ap,
            'form'=>$form
        ]);
    }

    #[Route('/apartment', name: 'apartmentsList')]
    public function apartmentList(Request $request)
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();

        $responseAparts = $client->request('GET', 'cs_apartments');
        
        if ($responseAparts->getStatusCode() == 404) {
            echo "PAS TROUVÉ";
        }

        $aps = $responseAparts->toArray();

        // echo "<pre>";
        // print_r($aps);
        // echo "</pre>";
        // return;

        return $this->render('frontend/apartments/apartmentList.html.twig', [
            'aps'=>$aps['hydra:member']
        ]);
        
    }
}
