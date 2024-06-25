<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use App\Service\AmazonS3Client;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;

class connectionController extends AbstractController
{
    private $apiHttpClient;
    private $amazonS3Client;

    public function __construct(ApiHttpClient $apiHttpClient, AmazonS3Client $amazonS3Client)
    {
        $this->apiHttpClient = $apiHttpClient;
        $this->amazonS3Client = $amazonS3Client;
    }

    private function extractValueByPrefix($data, $prefix) {
        foreach ($data as $item) {
            if (is_array($item) && strpos($item['id'], $prefix) === 0) {
                return $item['text'];
            }
        }
        return null;
    }

    #[Route('/logout', name: 'logoutFunc')]
    public function logout()
    {
        $expireTime = time() + 1;

        $responseCookie = new Response();

        $cookie0 = Cookie::create('token', '', $expireTime, '/', null, true, true);
        $cookie1 = Cookie::create('roles', '', $expireTime, '/', null, true, true);
        $cookie2 = Cookie::create('id', '', $expireTime, '/', null, true, true);
        $cookie3 = Cookie::create('profile_pict', '', $expireTime, '/', null, true, true);
        $cookie4 = Cookie::create('lastname', '', $expireTime, '/', null, true, true);
        $cookie5 = Cookie::create('firstname', '', $expireTime, '/', null, true, true);
        $cookie6 = Cookie::create('email', '', $expireTime, '/', null, true, true);

        $redirectResponse = new RedirectResponse($this->generateUrl('login'));
        $redirectResponse->headers->setCookie($cookie0);
        $redirectResponse->headers->setCookie($cookie1);
        $redirectResponse->headers->setCookie($cookie2);
        $redirectResponse->headers->setCookie($cookie3);
        $redirectResponse->headers->setCookie($cookie4);
        $redirectResponse->headers->setCookie($cookie5);
        $redirectResponse->headers->setCookie($cookie6);

        return $redirectResponse;    
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
            
            $responseBan = $client->request('GET', 'cs_users', [
                'query' => [
                    'page' => 1,
                    'email' => $data['username']
                ]
            ]);

            
            if ($responseBan->toArray()["hydra:member"][0]["isBan"] == true){
                $errorMessage = "Ce compte est banni";
                return $this->render('frontend/login_register/login.html.twig', [
                    'form'=>$form,
                    'errorMessage'=>$errorMessage
                ]);
            }

            $response = json_decode($response->getContent(), true);
            
            unset($response['user']['roles'][array_search('ROLE_USER', $response['user']['roles'])]);

            $cookie0 = Cookie::create('token', $response['token'], 0, '/', null, true, true);
            $cookie1 = Cookie::create('roles', $response['user']['roles'][0], 0, '/', null, true, true);
            $cookie2 = Cookie::create('id', $response['user']['id'], 0, '/', null, true, true);
            $cookie3 = Cookie::create('profile_pict', $response['user']['profile_pict'], 0, '/', null, true, true);
            $cookie4 = Cookie::create('lastname', $response['user']['lastname'], 0, '/', null, true, true);
            $cookie5 = Cookie::create('firstname', $response['user']['firstname'], 0, '/', null, true, true);
            $cookie6 = Cookie::create('email', $response['user']['email'], 0, '/', null, true, true);
            
            if ($request->get('redirect')) {
                if ($request->get('id') != null) {
                    $redirectResponse = new RedirectResponse($this->generateUrl($request->get('redirect'), ['id'=>$request->get('id')]));
                } else {
                    $redirectResponse = new RedirectResponse($this->generateUrl($request->get('redirect')));
                }
            }else {
                if ($response['user']['roles'][0] == "ROLE_LESSOR"){
                    $redirectResponse = new RedirectResponse($this->generateUrl('myApartmentsList'));
                }elseif ($response['user']['roles'][0] == "ROLE_ADMIN"){
                    $redirectResponse = new RedirectResponse($this->generateUrl('apartmentCrud'));
                }elseif ($response['user']['roles'][0] == "ROLE_PROVIDER"){
                    $redirectResponse = new RedirectResponse($this->generateUrl('myServicesList'));
                }elseif ($response['user']['roles'][0] == "ROLE_TRAVELER"){
                    $redirectResponse = new RedirectResponse($this->generateUrl('apartmentsList'));
                }
            }

            $redirectResponse->headers->setCookie($cookie0);
            $redirectResponse->headers->setCookie($cookie1);
            $redirectResponse->headers->setCookie($cookie2);
            $redirectResponse->headers->setCookie($cookie3);
            $redirectResponse->headers->setCookie($cookie4);
            $redirectResponse->headers->setCookie($cookie5);
            $redirectResponse->headers->setCookie($cookie6);

            return $redirectResponse;
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
        ->add("traveler", SubmitType::class, ["label"=>"Voyageur", "attr"=>["class"=>"choiceBtn btn"]])
        ->add("lessorPerso", SubmitType::class, ["label"=>"Bailleur particulier", "attr"=>["class"=>"choiceBtn btn"]])
        ->add("lessorPro", SubmitType::class, ["label"=>"Bailleur professionnel", "attr"=>["class"=>"choiceBtn btn"]])
        ->add("provider", SubmitType::class, ["label"=>"Prestataire", "attr"=>["class"=>"choiceBtn btn"]])
        ->getForm()->handleRequest($request);

        if ($form0->isSubmitted() && $form0->isValid()) {
            if ($form0->get("traveler")->isClicked()) {$request->getSession()->set("status", "ROLE_TRAVELER");}
            elseif ($form0->get("lessorPerso")->isClicked() || $form0->get("lessorPro")->isClicked()) {$request->getSession()->set("status", "ROLE_LESSOR");}
            else {$request->getSession()->set("status", "ROLE_PROVIDER");}
            
            if ($form0->get("traveler")->isClicked() || $form0->get("lessorPerso")->isClicked()) {
                return $this->redirectToRoute("registerPerso");
            } else {
                return $this->redirectToRoute("registerPro"); 
            }         
        }

        return $this->render('frontend/login_register/whichRegister.html.twig', [
            'form'=>$form0
        ]);        
    }

    #[Route('/register/perso', name: 'registerPerso')]
    public function registerPerso(Request $request)
    {
        $formForPerso = $this->createFormBuilder([])
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
        ->add("profilePict", FileType::class, [
            'constraints' => [
                new File([
                    'maxSize' => '10m',
                    'mimeTypes' => [
                        'image/png', 
                        'image/jpeg', 
                    ],
                    'mimeTypesMessage' => 'Please upload a valid jpeg or png document',
                ])
            ],
        ])
        ->getForm()->handleRequest($request);

        if ($formForPerso->isSubmitted() && $formForPerso->isValid()) {

            $data = $formForPerso->getData();
            
            $results = $this->amazonS3Client->insertObject($data['profilePict']);
            $data['profilePict'] = $results['link'];

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
                return $this->render('frontend/login_register/registerPerso.html.twig', [
                    'formPerso'=>$formForPerso,
                    'errorMessages'=>$errorMessages
                ]);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 13]);
            $data['roles'] = [$request->getSession()->get("status")];
            
            $jsonData = [
                "email"=>$data["email"],
                "firstname"=>$data["firstname"],
                "lastname"=>$data["lastname"],
                "password"=>$data["password"],
                "roles"=>$data["roles"],
                "telNumber"=>$data["telNumber"],
            ];

            $response = $client->request('POST', 'cs_users', [
                'json' => $jsonData
            ]);

            return $this->redirectToRoute('login');
        }

        return $this->render('frontend/login_register/registerPerso.html.twig', [
            'formPerso'=>$formForPerso,
            'errorMessages'=>null
        ]);
    }

    #[Route('/register/pro', name: 'registerPro')]
    public function registerPro(Request $request)
    {
        $formForPro = $this->createFormBuilder([])
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
        ->add("profilePict", FileType::class, [
            'constraints' => [
                new File([
                    'maxSize' => '10m',
                    'mimeTypes' => [
                        'image/png', 
                        'image/jpeg', 
                    ],
                    'mimeTypesMessage' => 'Please upload a valid jpeg or png document',
                ])
            ],
        ])
        ->add("address", HiddenType::class, [
            "constraints"=>[
                new NotBlank(),
            ],
        ])
        ->getForm()->handleRequest($request);

        if ($formForPro->isSubmitted() && $formForPro->isValid()) {

            $data = $formForPro->getData();
            
            $results = $this->amazonS3Client->insertObject($data['profilePict']);
            $data['profilePict'] = $results['link'];
            
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
                return $this->render('frontend/login_register/registerPro.html.twig', [
                    'formPro'=>$formForPro,
                    'errorMessages'=>$errorMessages
                ]);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 13]);
            $data['roles'] = [$request->getSession()->get("status")];

            $data['address'] = json_decode($data['address'], true);
            $data["country"] = $this->extractValueByPrefix($data["address"]['context'], 'country');
            $data["city"] = $this->extractValueByPrefix($data["address"]['context'], 'place');
            $data["postalCode"] = $this->extractValueByPrefix($data["address"]['context'], 'postcode');
            $data["centerGps"] = $data['address']['center'];                
            $data["address"] = $data['address']['place_name'];                

            $response = $client->request('POST', 'cs_companies', [
                'json' => [
                    "siretNumber"=>$data["siretNumber"],
                    "companyName"=>$data["companyName"],
                    "companyEmail"=>$data["companyEmail"],
                    "companyPhone"=>$data["companyPhone"],
                    "address"=>$data["address"],
                    "city"=>$data["city"],
                    "postalCode"=>$data["postalCode"],
                    "country"=>$data["country"],
                    "centerGps"=>$data["centerGps"]
                ]
            ]);
            
            $data['company'] = $response->toArray()['@id'];

            $jsonData = [
                "email"=>$data["email"],
                "firstname"=>$data["firstname"],
                "lastname"=>$data["lastname"],
                "password"=>$data["password"],
                "roles"=>$data["roles"],
                "telNumber"=>$data["telNumber"],
                "company"=>$data["company"],
                "professional"=>true
            ];

            $response = $client->request('POST', 'cs_users', [
                'json' => $jsonData
            ]);

            return $this->redirectToRoute('login');
        }

        return $this->render('frontend/login_register/registerPro.html.twig', [
            'formPro'=>$formForPro,
            'errorMessages'=>null
        ]);
    }
}
