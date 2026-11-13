<?php

namespace App\Controller\Manage;

use App\Entity\VehicleReturn;
use App\Entity\User;
use App\Entity\VehicleColor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manage/returns', name: 'manage_return_')]
#[IsGranted('ROLE_STAFF')]
class VehicleReturnController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('manage/return/index.html.twig', [
            'returns' => $em->getRepository(VehicleReturn::class)->findBy([], ['requestedAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/inspect', name: 'inspect', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function inspect(int $id, Request $request, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\Response
    {
        $return = $em->getRepository(VehicleReturn::class)->find($id);
        if (!$return) throw $this->createNotFoundException('Return request not found.');
        if ($return->getStatus() === 'confirmed') {
            $this->addFlash('warning', 'This vehicle return has already been confirmed.');
            return $this->redirectToRoute('manage_return_index');
        }
        $currentUser = $this->getUser();
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')
            && $return->getStatus() === 'inspected'
            && $return->getInspectedBy()?->getId() !== $currentUser?->getId()) {
            throw $this->createAccessDeniedException('Staff may update only inspections they have started.');
        }
        if (!$this->isCsrfTokenValid('inspect_return_'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid security token.');
        }
        $condition = $request->request->getString('condition');
        $notes = trim($request->request->getString('notes'));
        if (!in_array($condition, ['none', 'damage'], true) || ($condition === 'damage' && $notes === '')) {
            $this->addFlash('danger', 'Select the inspection result and describe any damage found.');
            return $this->redirectToRoute('manage_return_index');
        }
        $staffUser = $this->getUser();
        if (!$staffUser instanceof User || !in_array($staffUser->getRole(), [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            throw $this->createAccessDeniedException();
        }
        $return->setInspectedCondition($condition)->setInspectionNotes($notes ?: null)
            ->setInspectedBy($staffUser);
        $photo = $request->files->get('photo');
        if ($photo instanceof UploadedFile) {
            if (!$photo->isValid() || $photo->getSize() > 5 * 1024 * 1024 || !in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $this->addFlash('danger', 'Please upload a JPG, PNG, or WebP image no larger than 5 MB.');
                return $this->redirectToRoute('manage_return_index');
            }
            $filename = bin2hex(random_bytes(16)).'.'.$photo->guessExtension();
            $photo->move($this->getParameter('kernel.project_dir').'/public/uploads/vehicle-returns', $filename);
            $return->setInspectionPhoto('uploads/vehicle-returns/'.$filename);
        }
        if ($request->request->getString('decision') === 'confirm') {
            $return->setStatus('confirmed')->setConfirmedAt(new \DateTime());
            $return->getReservation()->setStatus('completed');
            $color = $em->getRepository(VehicleColor::class)->findOneBy([
                'id' => $return->getReservation()->getColorId(),
                'vehicle' => $return->getReservation()->getVehicle(),
            ]);
            if ($color) {
                $color->setInStock(true)->setUpdatedAt(new \DateTime());
            }
        } else {
            $return->setStatus('inspected');
        }
        $em->flush();
        $this->addFlash('success', $return->getStatus() === 'confirmed'
            ? 'Return confirmed. The rental is completed.'
            : 'Inspection saved. Confirm the return when the vehicle is ready to be released.');
        return $this->redirectToRoute('manage_return_index');
    }
}
