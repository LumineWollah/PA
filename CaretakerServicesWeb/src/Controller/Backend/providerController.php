<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;


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

        // echo "<pre>";
        // print_r($providersList);
        // echo "</pre>";

        return $this->render('backend/providers.html.twig', [
            'providers' => $providersList['hydra:member']
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

    // #[Route('/admin-panel/provider/edit', name: 'providerEdit')]
    // public function providerEdit(Request $request)
    // {
    //     $client = $this->apiHttpClient->getClient($request->getSession()->get('token'));

    //     $id = $request->query->get('id');

    //     $response = $client->request('GET', 'cs_users/'.$id, [

    //     ]);
        
    //     $providerEditing = $response->toArray();

    //     // echo "<pre>";
    //     // print_r($providersList);
    //     // echo "</pre>";

    //     return $this->render('backend/editProvider.html.twig', [
    //         'provider' => $providerEditing
    //     ]);
        
    // }

    #[Route('/admin-panel/provider/edit', name: 'providerEdit')]
    public function providerEdit(Request $request)
    {
        $providerData = $request->request->get('provider');
        echo($providerData);
        $provider = json_decode($providerData, true);
        echo "<pre>";
print_r($provider);
echo "</pre>";
        $form = $this->createFormBuilder()

        ->add("email", EmailType::class, [
            "attr"=>[
                "placeholder"=>$provider["email"],
            ],
            "empty_data"=>$provider["email"],
            "required"=>false,
        ])
        ->add("firstname", TextType::class, [
            "attr"=>[
                "placeholder"=>$provider["firstname"],
            ], 
            "empty_data"=>$provider["firstname"],
            "required"=>false,
        ])
        ->add("lastname", TextType::class, [
            "attr"=>[
                "placeholder"=>$provider["lastname"],
            ],
            "required"=>false,
            "empty_data"=>$provider["lastname"],
        ])
        ->add("telNumber", TextType::class, [
            "attr"=>[
                "placeholder"=>$provider["telNumber"],
            ],
            "required"=>false,
            "empty_data"=>$provider["telNumber"],
        ])
        ->getForm()->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient($request->getSession()->get('token'), 'application/merge-patch+json');
                                
                $id = $request->query->get('id');

                $response = $client->request('PATCH', 'cs_users/'.$id, [
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
}
