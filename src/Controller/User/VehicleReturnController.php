<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\VehicleReturn;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/returns', name: 'user_return_')]
#[IsGranted('ROLE_USER')]
class VehicleReturnController extends AbstractController
{
    #[Route('/{id}', name: 'request', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function requestReturn(int $id, Request $request, ReservationRepository $reservations, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_USER) {
            throw $this->createAccessDeniedException();
        }
        $reservation = $reservations->findOneBy(['id' => $id, 'user' => $user]);
        if (!$reservation) throw $this->createNotFoundException('Reservation not found.');
        if ($reservation->getStatus() !== 'confirmed') {
            $this->addFlash('warning', 'Only confirmed reservations can be returned.');
            return $this->redirectToRoute('user_reservation_index');
        }
        $existing = $em->getRepository(VehicleReturn::class)->findOneBy(['reservation' => $reservation]);
        if ($existing) {
            $this->addFlash('info', 'A return request already exists for this reservation.');
            return $this->redirectToRoute('user_reservation_index');
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('vehicle_return_'.$id, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid security token.');
            }
            $condition = $request->request->getString('condition');
            $notes = trim($request->request->getString('notes'));
            if (!in_array($condition, ['none', 'damage'], true) || ($condition === 'damage' && $notes === '')) {
                $this->addFlash('danger', 'Please select a valid condition and describe any reported damage.');
                return $this->redirectToRoute('user_return_request', ['id' => $id]);
            }
            $return = (new VehicleReturn())->setReservation($reservation)
                ->setCustomerCondition($condition)->setCustomerNotes($notes ?: null)
                ->setStatus('pending')->setRequestedAt(new \DateTime());
            $photo = $request->files->get('photo');
            if ($photo instanceof UploadedFile) {
                if (!$photo->isValid() || $photo->getSize() > 5 * 1024 * 1024 || !in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $this->addFlash('danger', 'Please upload a JPG, PNG, or WebP image no larger than 5 MB.');
                    return $this->redirectToRoute('user_return_request', ['id' => $id]);
                }
                $filename = bin2hex(random_bytes(16)).'.'.$photo->guessExtension();
                $photo->move($this->getParameter('kernel.project_dir').'/public/uploads/vehicle-returns', $filename);
                $return->setCustomerPhoto('uploads/vehicle-returns/'.$filename);
            }
            $em->persist($return);
            $em->flush();
            $this->addFlash('success', 'Your return request was submitted. An authorized staff member will inspect and confirm the vehicle.');
            return $this->redirectToRoute('user_reservation_index');
        }
        return $this->render('User/return/request.html.twig', ['reservation' => $reservation]);
    }
}
