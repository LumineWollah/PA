<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class companyController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    private function checkUserRole(Request $request): bool
    {
        $role = $request->cookies->get('roles');
        return $role !== null && $role == 'ROLE_ADMIN';
    }
    
    public const SIRET_LENGTH = 14;

    private function isValid(?string $siret): bool
    {
        $siret = trim((string) $siret);
        if (!is_numeric($siret) || \strlen($siret) !== self::SIRET_LENGTH) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < self::SIRET_LENGTH; ++$i) {
            if ($i % 2 === 0) {
                $tmp = ((int) $siret[$i]) * 2;
                $tmp = $tmp > 9 ? $tmp - 9 : $tmp;
            } else {
                $tmp = $siret[$i];
            }
            $sum += $tmp;
        }

        return !($sum % 10 !== 0);
    }

    #[Route('/admin-panel/company/list', name: 'companyList')]
    public function companyList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}
        
        $request->getSession()->remove('companyId');

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_companies', [
            'query' => [
                'page' => 1,
            ]
        ]);
        
        $companiesList = $response->toArray();

        return $this->render('backend/company/companies.html.twig', [
            'companies' => $companiesList['hydra:member'],
        ]);
    }
    
    #[Route('/admin-panel/company/delete', name: 'companyDelete')]
    public function companyDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_companies/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('companyList');
    }

    #[Route('/admin-panel/company/edit', name: 'companyEdit')]
    public function companyEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $companyData = $request->request->get('company');
        $company = json_decode($companyData, true);

        $storedCompany = $request->getSession()->get('companyId');

        if (!$storedCompany) {
            $request->getSession()->set('companyId', $company['id']);
        }

        try {
            $defaults = [
                'companyName' => $company['companyName'],
                'siretNumber' => $company['siretNumber'],
                'companyEmail' => $company['companyEmail'],
                'companyPhone' => $company['companyPhone'],
                'address' => $company['address'],
                'city' => $company['city'],
                'postalCode' => $company['postalCode'],
                'country' => $company['country'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("companyName", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom de l'entreprise",
            ], 
            "required"=>false,
        ])
        ->add("siretNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro Siret",
            ], 
            "required"=>false,
        ])
        ->add("companyEmail", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Email de l'entreprise",
            ],
            "required"=>false,
        ])
        ->add("companyPhone", TextType::class, [
            "attr"=>[
                "placeholder"=>"Téléphone de l'entreprise",
            ],
            "required"=>false,
        ])
        ->add("address", TextType::class, [
            "attr"=>[
                "placeholder"=>"Adresse de l'entreprise",
            ], 
            "required"=>false,
        ])
        ->add("city", TextType::class, [
            "attr"=>[
                "placeholder"=>"Ville de l'entreprise",
            ], 
            "required"=>false,
        ])
        ->add("postalCode", TextType::class, [
            "attr"=>[
                "placeholder"=>"Code postal de l'entreprise",
            ], 
            "required"=>false,
        ])
        ->add("country", TextType::class, [
            "attr"=>[
                "placeholder"=>"Pays de l'entreprise",
            ], 
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_companies/'.$storedCompany, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('companyId');

                return $this->redirectToRoute('companyList');
            }      
            return $this->render('backend/company/editCompany.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/company/show', name: 'companyShow')]
    public function companyShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $companyData = $request->request->get('company');
        $company = json_decode($companyData, true);
        
        return $this->render('backend/company/showCompany.html.twig', [
            'company'=>$company
        ]);
    }
         
    #[Route('/admin-panel/company/create', name: 'companyCreate')]
    public function companyCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $form = $this->createFormBuilder()
        ->add("siretNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>"Numéro de Siret",
            ],
        ])
        ->add("companyName", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom d'entreprise",
            ], 
            "constraints"=>[
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Le nom d\'entreprise doit contenir au plus {{ limit }} caractères',
                ]),
            ],
        ])
        ->add("companyEmail", EmailType::class, [
            "attr"=>[
                "placeholder"=>"Email d'entreprise",
            ], 
        ])
        ->add("companyPhone", TextType::class, [
            "attr"=>[
                "placeholder"=>"Téléphone d'entreprise",
            ], 
            "constraints"=>[
                new Length([
                    'max' => 10,
                    'min' => 10,
                    'exactMessage' => 'Le numéro de téléphone doit contenir {{ limit }} chiffres',
                ]),
                new Regex([
                    'pattern' => '/^[0-9]+$/',
                    'message' => 'Le numéro de téléphone doit contenir uniquement des chiffres',
                ]),
            ],
        ])
        ->add("address", TextType::class, [
            "attr"=>[
                "placeholder"=>"Adresse",
            ], 
            "constraints"=>[
                new NotBlank([
                    'message' => 'L\'adresse est obligatoire',
                ]),
            ],
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');
            
            $response = $client->request('GET', 'cs_companies', [
                'query' => [
                    'page' => 1,
                    'siretNumber' => $data['siretNumber']
                    ]
                ]);
            
            if ($response->toArray()["hydra:totalItems"] > 0){
                // dd($response->toArray(), $data);
                $errorMessages[] = "Numéro de SIRET déjà utilisé. Veuillez en utiliser un autre.";

                return $this->render('backend/company/createCompany.html.twig', [
                    'form'=>$form,
                    'errorMessages'=>$errorMessages
                ]);
            }
            if (!$this->isValid($data['siretNumber'])) {
                $errorMessages[] = "Numéro de SIRET invalide. Veuillez en utiliser un autre.";
            }   

            $response = $client->request('POST', 'cs_companies', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('companyList');
        }      
        return $this->render('backend/company/createCompany.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }
}


