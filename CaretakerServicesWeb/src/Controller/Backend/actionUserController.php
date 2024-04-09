<?php

namespace App\Controller\Backend;

use App\Security\CustomAccessManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ApiHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class actionUserController extends AbstractController
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

    #[Route('/admin-panel/user/delete', name: 'userDelete')]
    public function userDelete(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $client = $this->apiHttpClient->getClient($request->cookies->get('token'));

        $id = $request->query->get('id');
        $origin = $request->query->get('origin');

        $response = $client->request('DELETE', 'cs_users/'.$id, [
            'query' => [
                'id' => $id
            ]
        ]);
        return $this->redirectToRoute($origin);
    }


    #[Route('/admin-panel/user/show', name: 'userShow')]
    public function userShow(Request $request)
    {
        if (!$this->checkUserRole($request)) {return $this->redirectToRoute('login');}

        $userData = $request->request->get('user');
        $user = json_decode($userData, true);

        $storedUser = $request->getSession()->get('user');

        if (!$storedUser) {
            $request->getSession()->set('user', $user);
            $storedUser = $user;
        }
        
        return $this->render('backend/user/showUser.html.twig', [
            'user'=>$storedUser
        ]);
    }

}
