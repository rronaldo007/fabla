<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResearcAreasController extends AbstractController
{
    #[Route('/researc/areas', name: 'app_researc_areas')]
    public function index(): Response
    {
        return $this->render('researc_areas/index.html.twig', [
            'controller_name' => 'ResearcAreasController',
        ]);
    }
}
