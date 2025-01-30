<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FourOFourController extends AbstractController
{
    #[Route('/404', name: 'app_four_o_four')]
    public function index(): Response
    {
        return $this->render('four_o_four/index.html.twig', [
            'controller_name' => 'FourOFourController',
        ]);
    }
}
