<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class addonController extends AbstractController
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

    #[Route('/admin-panel/addon/list', name: 'addonList')]
    public function addonList(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $response = $client->request('GET', 'cs_addonss', [
            'query' => [
                'page' => 1,
            ]
        ]);

        $addonsList = $response->toArray();

        return $this->render('backend/addon/addons.html.twig', [
            'addons' => $addonsList['hydra:member']
        ]);
    }

    #[Route('/admin-panel/addon/delete', name: 'addonDelete')]
    public function addonDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_addonss'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);

        return $this->redirectToRoute('addonList');
    }

    #[Route('/admin-panel/addon/edit', name: 'addonEdit')]
    public function addonEdit(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $addonData = $request->request->get('addon');
        $addon = json_decode($addonData, true);

        $storedAddon = $request->getSession()->get('addonId');

        if (!$storedAddon) {
            $request->getSession()->set('addonId', $addon['id']);
        }

        try {
            $defaults = [
                'name' => $addon['name']
            ];
        } catch (Exception $e) {
            $defaults = [];
        }
        
        $form = $this->createFormBuilder($defaults)
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ], 
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_addonss'.$storedAddon, [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                $request->getSession()->remove('userId');
                $request->getSession()->remove('addonId');

                return $this->redirectToRoute('addonList');
            }      
            return $this->render('backend/addon/editAddon.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/addon/show', name: 'addonShow')]
    public function addonShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $addonData = $request->request->get('addon');
        $addon = json_decode($addonData, true);
        
        return $this->render('backend/addon/showAddon.html.twig', [
            'addon'=>$addon
        ]);
    }
    
    #[Route('/admin-panel/addon/create', name: 'addonCreate')]
    public function addonCreate(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $form = $this->createFormBuilder()
        ->add("name", TextType::class, [
            "attr"=>[
                "placeholder"=>"Nom",
            ], 
            "required"=>false,
        ])
        ->getForm()->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $client = $this->apiHttpClient->getClient($request->cookies->get('token'), 'application/ld+json');

            $response = $client->request('POST', 'cs_addonss', [
                'json' => $data,
            ]);

            $response = json_decode($response->getContent(), true);

            return $this->redirectToRoute('addonList');
        }      
        return $this->render('backend/addon/createAddon.html.twig', [
            'form'=>$form,
            'errorMessage'=>null
        ]);
    }

}
