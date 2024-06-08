<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


class homeController extends AbstractController
{
    #[Route("/" ,name: 'home')]
    public function home()
    {
        return $this->render('frontend/home.html.twig');
    }
}