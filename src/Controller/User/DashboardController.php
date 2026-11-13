<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\Reservation;
use App\Entity\VehicleReturn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_USER) {
            throw $this->createAccessDeniedException('Customer access only.');
        }
        $repo = $em->getRepository(Reservation::class);
        $reservations = $repo->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
        $all = $repo->findBy(['user' => $user]);
        $returns = $em->getRepository(VehicleReturn::class)->findBy(
            ['reservation' => $all], ['requestedAt' => 'DESC']
        );
        return $this->render('User/dashboard/index.html.twig', [
            'totalReservations' => count($all),
            'activeRentals' => count(array_filter($all, fn($r) => $r->getStatus() === 'confirmed')),
            'pendingReservations' => count(array_filter($all, fn($r) => $r->getStatus() === 'pending')),
            'pendingReturns' => count(array_filter($returns, fn($r) => in_array($r->getStatus(), ['pending', 'inspected'], true))),
            'completedRentals' => count(array_filter($all, fn($r) => $r->getStatus() === 'completed')),
            'recentReservations' => $reservations,
            'returnRequests' => $returns,
        ]);
    }
}
