<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class connectionController extends AbstractController
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

                return $this->render('frontend/login_register/login.html.twig', [
                    'form'=>$form,
                    'errorMessage'=>$errorMessage
                ]);
            }

            $response = json_decode($response->getContent(), true);
            
            unset($response['roles'][array_search('ROLE_USER', $response['roles'])]);

            $responseCookie = new Response();

            $cookie = Cookie::create('token', $response['token'], 0, '/', null, true, true);
            $responseCookie->headers->setCookie($cookie);
            $cookie = Cookie::create('roles', $response['roles'][0], 0, '/', null, true, true);
            $responseCookie->headers->setCookie($cookie);
            
            $responseCookie->send();

            return $this->redirectToRoute('providerList');
        }      

        return $this->render('frontend/login_register/login.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request)
    {
        $form0 = $this->createFormBuilder()
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Prénom"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(min:3),
            ]
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(min:3)
            ]
        ])
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Email"
            ],
            'constraints'=>[
                new NotBlank(),
                new Email()
            ]
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Téléphone"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(exactly:10)
            ]
        ])
        ->add("password", PasswordType::class, [
            "attr"=>[
                "placeholder"=>"Mot de passe"
            ],
            'constraints'=>[
                new NotBlank(),
                new Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*.?&])[A-Za-z\d@$!%*.?&]{8,}$/',
                    'message' => "Votre mot de passe doit contenir 8 caractères minimum, au moins 1 lettre majuscule, 1 lettre minuscule, 1 chiffre et 1 caractère spécial"
                ]),
            ]
        ])
        ->add("confirmation", PasswordType::class, [
            "attr"=>[
                "placeholder"=>"Confirmation"
            ],
            'constraints'=>[
                new NotBlank(),
                new Regex([
                    'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*.?&])[A-Za-z\d@$!%*.?&]{8,}$/',
                    'message' => "Votre mot de passe doit contenir 8 caractères minimum, au moins 1 lettre majuscule, 1 lettre minuscule, 1 chiffre et 1 caractère spécial"
                ]),
            ]
        ])
        ->add("choiceRadio", ChoiceType::class, [
            "choices"=> [
                'Particulier' => 'traveler',
                'Bailleur Particulier' => 'lessorPart',
                'Bailleur Professionel' => 'lessorPro',
                'Prestataire' => 'provider',
            ],
            'multiple' => false
        ])
        ->getForm()->handleRequest($request);

        $form1 = $this->createFormBuilder()
        ->add("companyName", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom de l'entreprise"
            ],
            'constraints'=>[
                new NotBlank(),
            ]
        ])
        ->add("companyEmail", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Email de l'entreprise"
            ],
            'constraints'=>[
                new NotBlank(),
                new Email()
            ]
        ])
        ->add("siretNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro de Siret"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(exactly:14)
            ]
        ])
        ->add("companyPhone", TextType::class, [
            "attr"=>[
                "placeholder"=>"Téléphone de l'entreprise"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(exactly:10)
            ]
        ])
        ->add("address", TextType::class, [
            "attr"=>[
                "placeholder"=>"Adresse de l'entreprise"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(min:10)
            ]
        ])
        ->add("city", TextType::class, [
            "attr"=>[
                "placeholder"=>"Ville"
            ],
            'constraints'=>[
                new NotBlank(),
            ]
        ])
        ->add("postalCode", TextType::class, [
            "attr"=>[
                "placeholder"=>"Code Postal"
            ],
            'constraints'=>[
                new NotBlank(),
                new Length(exactly:5)
            ]
        ])
        ->add("country", TextType::class, [
            "attr"=>[
                "placeholder"=>"Pays"
            ],
            'constraints'=>[
                new NotBlank()            ]
        ])
        ->getForm()->handleRequest($request);

        if ($form0->isSubmitted() && $form0->isValid()){

            $data = $form0->getData();
            $errorMessages = [];

            if ($data['password'] != $data['confirmation']){
                $errorMessages[] = "Vos mots de passe ne sont pas identiques";
            }
            unset($data['confirmation']);

            $client = $this->apiHttpClient->getClientWithoutBearer();
            
            $response = $client->request('GET', 'cs_users', [
                'query' => [
                    'page' => 1,
                    'email' => $data['email']
                    ]
                ]);
            
            if ($response->toArray()["hydra:totalItems"] > 0){
                $errorMessages[] = "Adresse mail déjà utilisée. Essayez en une autre.";

                return $this->render('frontend/login_register/register.html.twig', [
                    'formIsValid'=>false,
                    'form'=>$form0,
                    'form1'=>$form1,
                    'errorMessages'=>$errorMessages
                ]);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 13]);

            if ($data['choiceRadio'] == 'traveler' || $data['choiceRadio'] == 'lessorPart') {

                $data['roles'] = ($data['choiceRadio'] == 'traveler') ? ['ROLE_TRAVELER'] : ['ROLE_LESSOR'];

                $response = $client->request('POST', 'cs_users', [
                    'json' => $data,
                ]);

                return $this->redirectToRoute('login');
            }

            $data['roles'] = ($data['choiceRadio'] == 'provider') ? ['ROLE_PROVIDER'] : ['ROLE_LESSOR'];

            $request->getSession()->set('formIsValid', true);
            $request->getSession()->set('dataForm', $data);
            return $this->redirectToRoute('register');
        }
        
        if ($form1->isSubmitted() && $form1->isValid()){
            
            $request->getSession()->remove('formIsValid');
            
            $data = $form1->getData();
            $errorMessages = [];

            $client = $this->apiHttpClient->getClientWithoutBearer();

            $response = $client->request('POST', 'cs_companies', [
                'json' => $data,
            ]);
            
            $client = $this->apiHttpClient->getClientWithoutBearer();

            $data = $request->getSession()->get('dataForm');
            $data['company'] = $response->toArray()['@id'];

            $response = $client->request('POST', 'cs_users', [
                'json' => $data,
            ]);

            return $this->redirectToRoute('login');
        }

        $formIsValid = $request->getSession()->get('formIsValid');

        return $this->render('frontend/login_register/register.html.twig', [
            'formIsValid'=>$formIsValid,
            'form'=>$form0,
            'form1'=>$form1,
            'errorMessages'=>null
        ]);
    }
}
