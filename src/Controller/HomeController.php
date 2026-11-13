<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(VehicleRepository $vehicles): Response
    {
        return $this->render('home/index.html.twig', [
            'featuredVehicles' => $vehicles->findBy([], ['id' => 'ASC'], 3),
        ]);
    }
}
