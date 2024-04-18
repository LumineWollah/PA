<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class categoryController extends AbstractController
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

    #[Route('/admin-panel/category/list', name: 'categoryList')]
    public function categoryList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_categories', [
            'query' => [
                'page' => 1
            ]
        ]);
        
        $categoriesList = $response->toArray();

        return $this->render('backend/category/categories.html.twig', [
            'categories' => $categoriesList['hydra:member']
        ]);
    }
    
    #[Route('/admin-panel/category/delete', name: 'categoryDelete')]
    public function categoryDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_categories/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);

        return $this->redirectToRoute('categoryList');
    }

    #[Route('/admin-panel/category/edit', name: 'categoryEdit')]
    public function categoryEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $categoryData = $request->request->get('category');
        $category = json_decode($categoryData, true);

        $storedCategory = $request->getSession()->get('categoryId');

        if (!$storedCategory) {
            $request->getSession()->set('categoryId', $category['id']);
        }
        
        try {
            $defaults = [
                'name' => $category['name'],
                'color' => $category['color'],
            ];
        } catch (Exception $e) {
            $defaults = [];
        }

        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Name",
            ],
            "required"=>false,
        ])
        ->add("color", TextType::class, [
            "attr"=>[
                "placeholder"=>"Color",
            ], 
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

            $response = $client->request('PATCH', 'cs_categories/'.$storedCategory, [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            $request->getSession()->remove('categoryId');

            return $this->redirectToRoute('categoryList');
        }      
        return $this->render('backend/category/editCategory.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

    #[Route('/admin-panel/category/show', name: 'categoryShow')]
    public function categoryShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $categoryData = $request->request->get('category');
        $category = json_decode($categoryData, true);
        
        return $this->render('backend/category/showCategory.html.twig', [
            'category'=>$category
        ]);
    }
}
