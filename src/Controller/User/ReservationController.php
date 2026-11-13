<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\VehicleReturn;
use App\Entity\VehicleColor;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/reservations', name: 'user_reservation_')]
#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    private function requireCustomer(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== 'user') {
            throw $this->createAccessDeniedException(
                'Only customers can access this page.'
            );
        }

        return $user;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->requireCustomer();

        $reservations = $reservationRepository->findForUser($user);
        $returnRequests = [];
        foreach ($reservations as $reservation) {
            $returnRequest = $entityManager->getRepository(VehicleReturn::class)->findOneBy(['reservation' => $reservation]);
            if ($returnRequest) {
                $returnRequests[$reservation->getId()] = $returnRequest;
            }
        }

        return $this->render('User/reservation/index.html.twig', [
            'reservations' => $reservations,
            'returnRequests' => $returnRequests,
        ]);
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
        $user = $this->requireCustomer();

        // Find the reservation using BOTH its ID and its owner.
        $reservation = $reservationRepository->findOneBy([
            'id' => $id,
            'user' => $user,
        ]);

        if (!$reservation) {
            throw $this->createNotFoundException(
                'Reservation not found.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'user_cancel_reservation_' . $reservation->getId(),
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('user_reservation_index');
        }

        if (!in_array($reservation->getStatus(), ['pending', 'confirmed'], true)) {
            $this->addFlash(
                'warning',
                'Only pending or confirmed reservations can be cancelled.'
            );

            return $this->redirectToRoute('user_reservation_index');
        }

        $returnRequest = $entityManager->getRepository(VehicleReturn::class)->findOneBy(['reservation' => $reservation]);
        if ($returnRequest) {
            $this->addFlash('warning', 'A reservation with a submitted vehicle return request cannot be cancelled.');
            return $this->redirectToRoute('user_reservation_index');
        }
        $reservation->setStatus('cancelled');
        $reservation->setCancelledAt(new \DateTime());
        $color = $entityManager->getRepository(VehicleColor::class)->findOneBy(['id' => $reservation->getColorId(), 'vehicle' => $reservation->getVehicle()]);
        if ($color) $color->setInStock(true)->setUpdatedAt(new \DateTime());

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Your reservation has been cancelled successfully.'
        );

        return $this->redirectToRoute('user_reservation_index');
    }
}
