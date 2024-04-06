<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email;
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
            $form = $this->createFormBuilder()
            ->add("email", EmailType::class, [
                "attr"=>[
                    "placeholder"=>"Votre email"
                ],
                'constraints'=>[
                    new Email(),
                ]
            ])
            ->add("firstname", TextType::class, [
                "attr"=>[
                    "placeholder"=>"Votre prénom"
                ],
            ])
            ->add("lastname", TextType::class, [
                "attr"=>[
                    "placeholder"=>"Votre nom"
                ],
            ])
            ->add("telNumber", TextType::class, [
                "attr"=>[
                    "placeholder"=>"Votre numéro de téléphone"
                ],
            ])
            ->add("roles", TextType::class, [
                "attr"=>[
                    "placeholder"=>"Votre rôle"
                ],
            ])
            ->getForm()->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()){
                $data = $form->getData();
                
                $client = $this->apiHttpClient->getClient("eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MTI0MzExMTIsImV4cCI6MTcxMjQzNDcxMiwicm9sZXMiOlsiUk9MRV9BRE1JTiIsIlJPTEVfVVNFUiJdLCJ1c2VybmFtZSI6Imxlb3BvbGQuZ291ZGllckBnbWFpbC5jb20ifQ.wOT5-eI8pHZ1tqALGs1pPKp8Hkzx6I3QxPLAd4pN53l-9jGHM-DpjNypBVQyMfMilOak0MafnhUX5De-5q2eyGq4yBiqyi4eJI2TvyemWBUNKPBkgt4QunL2AZZtH7O0x0626sIvFJePuZUOLDbWSEoq0MsNG_Zo1vLov6Eyka_MPrEEgFWy3TZst9WOQpH4Gve-Oy1BWRa7D3CJYSKNAnpnT9IZOWvNX3Q1QkuAST4P-FM4qQuVzH9DkwC9GbKcBEtIpG7eyAKAUekuxlXZaF4aOW3MtV6DsjYmL3JpKK1e7fftpufHNSdVNwKf4cjuJvfgGyCivzPrGFuZkm3BPjoPlCMa2McxLFQ6-7mO7r_gXLlFbhYM86LgnZeZD8NVqQgylKg8AbJf3K23cDWY3ZtyDWntvIefhYlYNY2YgHNR3g14743oyVKGq7aZWezeJ3uH2HyYyC1vczvQCFMsLR-ZjyCPzCLh-bsZBlcUhRaOd4UxjGyv8KJY6onkfpQB_X-jCIQ85cc3bjRqzUscoAvRwKfzrne-mS37iHIF4UOJeVBb0So0vpHbS1Yg9vxk_5Hza0g65uuQd1LqNA4UOO_ux-VGCdqPZvHuw-Zo3IzuaJOqgw8K7FO4qXj0TtJ-1cw7G-ZeA_WxnOi8t8etJ_XM5_ZdlRbg3d0bT7ONVQs", 'application/merge-patch+json');
                
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
