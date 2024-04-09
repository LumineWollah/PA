<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
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

        $request->getSession()->remove('company');

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

        $storedCompany = $request->getSession()->get('company');

        if (!$storedCompany) {
            $request->getSession()->set('company', $company);
            $storedCompany = $company;
        }

        $form = $this->createFormBuilder()
        ->add("companyName", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["companyName"],
            ], 
            "empty_data"=>$storedCompany["companyName"],
            "required"=>false,
        ])
        ->add("siretNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["siretNumber"],
            ], 
            "empty_data"=>$storedCompany["siretNumber"],
            "required"=>false,
        ])
        ->add("companyEmail", EmailType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["companyEmail"],
            ],
            "empty_data"=>$storedCompany["companyEmail"],
            "required"=>false,
        ])
        ->add("companyPhone", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["companyPhone"],
            ],
            "required"=>false,
            "empty_data"=>$storedCompany["companyPhone"],
        ])
        ->add("address", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["address"],
            ], 
            "empty_data"=>$storedCompany["address"],
            "required"=>false,
        ])
        ->add("city", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["city"],
            ], 
            "empty_data"=>$storedCompany["city"],
            "required"=>false,
        ])
        ->add("postalCode", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["postalCode"],
            ], 
            "empty_data"=>$storedCompany["postalCode"],
            "required"=>false,
        ])
        ->add("country", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedCompany["country"],
            ], 
            "empty_data"=>$storedCompany["country"],
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_companies/'.$storedCompany['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
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

        $storedCompany = $request->getSession()->get('company');

        if (!$storedCompany) {
            $request->getSession()->set('company', $company);
            $storedCompany = $company;
        }
        
        return $this->render('backend/company/showCompany.html.twig', [
            'company'=>$storedCompany
        ]);
    }
}
