<?php

namespace App\Controller\Staff;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/staff/dashboard', name: 'staff_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('staff/dashboard/index.html.twig');
    }
}
