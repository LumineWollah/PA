<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Email;


class homeController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route("/" , name: 'home')]
    public function home()
    {
        $client = $this->apiHttpClient->getClientWithoutBearer();
        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
            ]
        ]);
        $response = $response->toArray();

        return $this->render('frontend/home.html.twig',[
            'userCount'=>$response["hydra:totalItems"],
        ]);
    }

    #[Route("/waiting-room" , name: 'waitingRoom')]
    public function waitingRoom(Request $request)
    {
        $lastname = $request->cookies->get('lastname');
        $firstname = $request->cookies->get('firstname');
        return $this->render('frontend/waitingRoom.html.twig', [
            'lastname' => $lastname,
            'firstname' => $firstname
        ]);
    }

    #[Route("/contact" , name: 'contact')]
    public function contact(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));
        
        $id = $request->cookies->get('id');
        $default = [];

        if ($id != null) {
            $default['clientEmail'] = $request->cookies->get('email');
        }

        $form = $this->createFormBuilder($default)
        ->add("name", TextType::class, [
            'constraints'=>[
                new NotBlank(),
                new Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Le nom du ticket doit contenir au moins 2 caractères',
                    'maxMessage' => 'Le nom du ticket doit contenir au maximum 50 caractères'
                ])
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("clientEmail", EmailType::class, [
            'constraints'=>[
                new NotBlank(),
                new Email(),
            ],
            'attr' => ['class' => 'form-control'],
        ])
        ->add("subject", ChoiceType::class, [
            "choices" => [
                "Locations" => "Locations",
                "Prestations" => "Prestations",
                "Site Web" => "Site Web",
            ],
            "expanded" => False,
            "multiple" => False,
        ])
        ->add("description", TextareaType::class, [
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

            $data['author'] = '/api/cs_users/'.$id;
            $data['priority'] = "Basse";
            

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_tickets', [
                'json' => $data,
            ]);
            $this->addFlash('success', 'Your message has been sent successfully.');
        }

        return $this->render('frontend/contact.html.twig', [
            'form'=>$form
        ]);
    }
}