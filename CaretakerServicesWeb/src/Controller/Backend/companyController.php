<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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

    #[Route('/admin-panel/company/list', name: 'companyList')]
    public function companyList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

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
}
