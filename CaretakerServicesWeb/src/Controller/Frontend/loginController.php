<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class loginController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request)
    {
        $form = $this->createFormBuilder()
        ->add("username", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Votre email"
            ],
            'constraints'=>[
                new NotBlank(),
                new Email(),
            ]
        ])
        ->add("password", PasswordType::class, [
            "attr"=>[
                "placeholder"=>"Votre mot de passe"
            ],
            'constraints'=>[
                new NotBlank(),
                new Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*.?&])[A-Za-z\d@$!%*.?&]{8,}$/',
                    'message' => "Votre mot de passe doit contenir 8 caractères minimum, au moins 1 lettre majuscule, 1 lettre minuscule, 1 chiffre et 1 caractère spécial"
                ]),
            ]
        ])
        ->add("remember_me", CheckboxType::class, [
            "required"=>false
        ])
        ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            unset($data['remember_me']);
            
            $client = $this->apiHttpClient->getClientWithoutBearer();
            
            $response = $client->request('POST', 'login', [
                'json' => $data,
            ]);
            
            $statusCode = $response->getStatusCode();

            if ($statusCode == 401){
                $errorMessage = "Identifiant ou Mot de passe invalide.";

                return $this->render('frontend/login.html.twig', [
                    'form'=>$form,
                    'errorMessage'=>$errorMessage
                ]);
            }

            $response = json_decode($response->getContent(), true);

            $request->getSession()->set('token', $response['token']);

            return $this->redirectToRoute('providerList');
        }      

        return $this->render('frontend/login.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/test', name: 'test')]
    public function test(Request $request)
    {
        $storedToken = $request->getSession()->get('token');

        echo "<pre>";
        print_r($storedToken);
        echo "</pre>";

        return $this->render('frontend/test.html.twig');
    }
}
