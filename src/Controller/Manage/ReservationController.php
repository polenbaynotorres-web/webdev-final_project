<?php

namespace App\Controller\Manage;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manage/reservations', name: 'reservation_')]
#[IsGranted('ROLE_STAFF')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ReservationRepository $reservationRepository
    ): Response {
        $search = $request->query->getString('search');
        $status = $request->query->getString('status');

        if (!in_array($status, ['', 'pending', 'confirmed', 'cancelled', 'completed'], true)) {
            $status = '';
        }

        $reservations = $reservationRepository->findForManagement(
            $search !== '' ? $search : null,
            $status !== '' ? $status : null
        );

        return $this->render('manage/reservation/index.html.twig', [
            'reservations' => $reservations,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route(
        '/{id}',
        name: 'show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        ReservationRepository $reservationRepository,
        Connection $connection
    ): Response {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found.');
        }

        $color = $connection->fetchAssociative(
            'SELECT color_name, image_path
             FROM vehicle_colors
             WHERE id = :colorId AND vehicle_id = :vehicleId',
            [
                'colorId' => $reservation->getColorId(),
                'vehicleId' => $reservation->getVehicle()->getId(),
            ]
        );

        return $this->render('manage/reservation/show.html.twig', [
            'reservation' => $reservation,
            'color' => $color ?: null,
        ]);
    }


    #[Route(
        '/{id}/confirm',
        name: 'confirm',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function confirm(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found.');
        }

        if (!$this->isCsrfTokenValid(
            'confirm_reservation_' . $reservation->getId(),
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('reservation_index');
        }

        if ($reservation->getStatus() !== 'pending') {
            $this->addFlash('warning', 'Only pending reservations can be approved.');
            return $this->redirectToRoute('reservation_show', ['id' => $id]);
        }

        $reservation->setStatus('confirmed');
        $entityManager->flush();
        $this->addFlash('success', 'Reservation approved. Its status is now Confirmed.');
        return $this->redirectToRoute('reservation_show', ['id' => $id]);
    }

    #[Route(
        '/{id}/cancel',
        name: 'cancel',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function cancel(
        int $id,
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $reservationRepository->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation not found.');
        }

        if (!$this->isCsrfTokenValid(
            'cancel_reservation_' . $reservation->getId(),
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('reservation_index');
        }

        if (!in_array($reservation->getStatus(), ['pending', 'confirmed'], true)) {
            $this->addFlash('warning', 'Only pending or confirmed reservations can be rejected or cancelled.');
            return $this->redirectToRoute('reservation_index');
        }

        $reservation->setStatus('cancelled');
        $reservation->setCancelledAt(new \DateTime());
        $color = $entityManager->getConnection()->fetchOne(
            'SELECT id FROM vehicle_colors WHERE id = ? AND vehicle_id = ?',
            [$reservation->getColorId(), $reservation->getVehicle()->getId()]
        );
        if ($color) {
            $entityManager->getConnection()->executeStatement(
                'UPDATE vehicle_colors SET in_stock = 1, updated_at = NOW() WHERE id = ?',
                [$reservation->getColorId()]
            );
        }

        $entityManager->flush();

        $this->addFlash('success', 'Reservation successfully cancelled.');

        return $this->redirectToRoute('reservation_index');
    }
}
