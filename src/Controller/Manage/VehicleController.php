<?php

namespace App\Controller\Manage;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manage/vehicles', name: 'vehicle_')]
final class VehicleController extends AbstractController
{
    /**
     * Only Admin and Staff accounts can manage vehicles.
     */
    private function denyUnlessVehicleManager(): User
    {
        $user = $this->getUser();

        if (
            !$user instanceof User ||
            !in_array($user->getRole(), [
                User::ROLE_ADMIN,
                User::ROLE_STAFF,
            ], true)
        ) {
            throw $this->createAccessDeniedException(
                'Only Admin and Staff accounts can manage vehicles.'
            );
        }

        return $user;
    }

    /**
     * Staff may edit or delete only vehicles they created.
     * Admins may manage every vehicle.
     */
    private function denyUnlessOwnerOrAdmin(
        Vehicle $vehicle,
        User $user
    ): void {
        if ($user->getRole() === User::ROLE_ADMIN) {
            return;
        }

        if (
            $vehicle->getCreatedBy()?->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'You can only manage vehicles that you created.'
            );
        }
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepository
    ): Response {
        $this->denyUnlessVehicleManager();

        return $this->render('manage/vehicle/index.html.twig', [
            'vehicles' => $vehicleRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $vehicle = new Vehicle();

        $form = $this->createForm(VehicleType::class, $vehicle, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('vehicleImage')->getData();

            if ($imageFile instanceof UploadedFile) {
                $vehicle->setImagePath(
                    $this->uploadVehicleImage($imageFile)
                );

                // Record the staff/admin account that created this vehicle.
                $vehicle->setCreatedBy($user);

                $entityManager->persist($vehicle);
                $entityManager->flush();

                $this->addFlash('success', 'Vehicle created successfully.');

                return $this->redirectToRoute('vehicle_index');
            }
        }

        return $this->render('manage/vehicle/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        Vehicle $vehicle,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        $form = $this->createForm(VehicleType::class, $vehicle, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('vehicleImage')->getData();

            if ($imageFile instanceof UploadedFile) {
                $vehicle->setImagePath(
                    $this->uploadVehicleImage($imageFile)
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vehicle updated successfully.');

            return $this->redirectToRoute('vehicle_index');
        }

        return $this->render('manage/vehicle/edit.html.twig', [
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route(
        '/{id}/delete',
        name: 'delete',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function delete(
        Vehicle $vehicle,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        if (!$this->isCsrfTokenValid(
            'delete_vehicle_' . $vehicle->getId(),
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $reservationCount = $entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM reservations WHERE vehicle_id = ?',
            [$vehicle->getId()]
        );

        if ((int) $reservationCount > 0) {
            $this->addFlash(
                'danger',
                'This vehicle cannot be deleted because it has existing reservations.'
            );

            return $this->redirectToRoute('vehicle_index');
        }

        $entityManager->remove($vehicle);
        $entityManager->flush();

        $this->addFlash('success', 'Vehicle deleted successfully.');

        return $this->redirectToRoute('vehicle_index');
    }

    private function uploadVehicleImage(UploadedFile $imageFile): string
    {
        $uploadDirectory = $this->getParameter('kernel.project_dir')
            . '/public/uploads/vehicles';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true);
        }

        $extension = $imageFile->guessExtension() ?? 'jpg';
        $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

        $imageFile->move($uploadDirectory, $newFilename);

        return 'uploads/vehicles/' . $newFilename;
    }
}
