<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class providerController extends AbstractController
{
    private $apiHttpClient;

    public function __construct(ApiHttpClient $apiHttpClient)
    {
        $this->apiHttpClient = $apiHttpClient;
    }

    #[Route('/admin-panel/provider/list', name: 'providerList')]
    public function providerList(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $response = $client->request('GET', 'cs_users', [
            'query' => [
                'page' => 1,
                'roles' => 'ROLE_PROVIDER'
            ]
        ]);
        
        $providersList = $response->toArray();

        $verifiedProviders = array();
        $unverifiedProviders = array();

        foreach ($providersList['hydra:member'] as $provider) {
            $provider['telNumber'] = implode(".", str_split($provider['telNumber'], 2));
            $provider['isVerified'] == 1 ? $verifiedProviders[] = $provider : $unverifiedProviders[] = $provider;
        }

        // echo "<pre>";
        // print_r($providersList);
        // echo "</pre>";

        $request->getSession()->remove('provider');

        // return;

        return $this->render('backend/providers.html.twig', [
            'verifiedProviders' => $verifiedProviders,
            'unverifiedProviders' => $unverifiedProviders
        ]);
    }

    #[Route('/admin-panel/provider/delete', name: 'providerDelete')]
    public function providerDelete(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

        $id = $request->query->get('id');

        $response = $client->request('DELETE', 'cs_users/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        
        return $this->redirectToRoute('providerList');
        
    }

    #[Route('/admin-panel/provider/edit', name: 'providerEdit')]
    public function providerEdit(Request $request)
    {
        $providerData = $request->request->get('provider');
        $provider = json_decode($providerData, true);

        $storedProvider = $request->getSession()->get('provider');

        if (!$storedProvider) {
            $request->getSession()->set('provider', $provider);
            $storedProvider = $provider;
        }

        $form = $this->createFormBuilder()
        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>$storedProvider["email"],
            ],
            "empty_data"=>$storedProvider["email"],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedProvider["firstname"],
            ], 
            "empty_data"=>$storedProvider["firstname"],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedProvider["lastname"],
            ],
            "required"=>false,
            "empty_data"=>$storedProvider["lastname"],
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>$storedProvider["telNumber"],
            ],
            "required"=>false,
            "empty_data"=>$storedProvider["telNumber"],
        ])
        ->getForm()->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

                $response = $client->request('PATCH', 'cs_users/'.$storedProvider['id'], [
                    'json' => $data,
                ]);

                $response = json_decode($response->getContent(), true);
    
                return $this->redirectToRoute('providerList');
            }      
            return $this->render('backend/editProvider.html.twig', [
                'form'=>$form,
                'errorMessage'=>null
            ]);
    }

    #[Route('/admin-panel/provider/show', name: 'providerShow')]
    public function providerShow(Request $request)
    {
        $providerData = $request->request->get('provider');
        $provider = json_decode($providerData, true);

        $storedProvider = $request->getSession()->get('provider');

        if (!$storedProvider) {
            $request->getSession()->set('provider', $provider);
            $storedProvider = $provider;
        }
        
        return $this->render('backend/showProvider.html.twig', [
            'provider'=>$storedProvider
        ]);
    }

    #[Route('/admin-panel/provider/accept', name: 'providerAccept')]
    public function providerAccept(Request $request)
    {
        $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');

        $id = $request->query->get('id');

        $response = $client->request('PATCH', 'cs_users/'.$id, [
            'json' => [
                'isVerified'=>true
            ],
        ]);
        
        return $this->redirectToRoute('providerList');
        
    }

    // #[Route('/admin-panel/provider/refuse', name: 'providerRefuse')]
    // public function providerRefuse(Request $request)
    // {        
    // }

}
