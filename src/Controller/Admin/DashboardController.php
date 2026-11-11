<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $totalVehicles = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM vehicles'
        );

        $totalReservations = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM reservations'
        );

        $totalCustomers = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM users WHERE role = 'user'"
        );

        $totalMessages = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM messages'
        );

        return $this->render('admin/dashboard/index.html.twig', [
            'totalVehicles' => $totalVehicles,
            'totalReservations' => $totalReservations,
            'totalCustomers' => $totalCustomers,
            'totalMessages' => $totalMessages,
        ]);
    }
}
