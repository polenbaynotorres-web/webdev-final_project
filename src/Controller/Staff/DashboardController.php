<?php

namespace App\Controller\Staff;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Entity\VehicleReturn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
final class DashboardController extends AbstractController
{
    #[Route('/staff/dashboard', name: 'staff_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $reservations = $em->getRepository(Reservation::class)
            ->findBy([], ['createdAt' => 'DESC'], 8);

        $returns = $em->getRepository(VehicleReturn::class)
            ->findBy(
                ['status' => ['pending', 'inspected']],
                ['requestedAt' => 'ASC']
            );

        return $this->render('staff/dashboard/index.html.twig', [
            'totalVehicles' => $em->getRepository(Vehicle::class)->count([]),
            'totalReservations' => $em->getRepository(Reservation::class)->count([]),
            'activeRentals' => $em->getRepository(Reservation::class)
                ->count(['status' => 'confirmed']),
            'pendingReservations' => $em->getRepository(Reservation::class)
                ->count(['status' => 'pending']),
            'pendingReturns' => count(array_filter(
                $returns,
                fn ($return) => $return->getStatus() === 'pending'
            )),
            'recentReservations' => $reservations,
            'pendingReturnItems' => $returns,
        ]);
    }
}
