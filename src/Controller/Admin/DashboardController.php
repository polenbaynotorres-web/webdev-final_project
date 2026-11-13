<?php

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $stats = [
            'totalVehicles' => (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM vehicles'
            ),

            'totalReservations' => (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM reservations'
            ),

            'activeReservations' => (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'"
            ),

            'pendingReservations' => (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM reservations WHERE status = 'pending'"
            ),

            'totalCustomers' => (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'"
            ),

            'totalStaff' => (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'active'"
            ),

            'totalMessages' => (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM messages'
            ),

            'totalLocations' => (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM locations'
            ),

            'pendingReturns' => (int) $connection->fetchOne(
                "SELECT COUNT(*) FROM vehicle_returns WHERE status IN ('pending', 'inspected')"
            ),
        ];

        $recentReservations = $connection->fetchAllAssociative(
            'SELECT
                r.id,
                r.pickup_date,
                r.return_date,
                r.status,
                u.name AS customer_name,
                v.name AS vehicle_name
             FROM reservations r
             LEFT JOIN users u ON u.id = r.user_id
             LEFT JOIN vehicles v ON v.id = r.vehicle_id
             ORDER BY r.id DESC
             LIMIT 6'
        );

        $recentMessages = $connection->fetchAllAssociative(
            'SELECT id, name, subject, created_at
             FROM messages
             ORDER BY id DESC
             LIMIT 5'
        );

        return $this->render(
            'admin/dashboard/index.html.twig',
            $stats + [
                'recentReservations' => $recentReservations,
                'recentMessages' => $recentMessages,
            ]
        );
    }
}
