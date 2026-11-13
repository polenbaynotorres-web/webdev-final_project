<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleColor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/manage/vehicles/{vehicleId}/colors', name: 'vehicle_color_')]
final class VehicleColorController extends AbstractController
{
    /**
     * Only Admin and Staff accounts can access vehicle colors.
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
                'Only Admin and Staff accounts can manage vehicle colors.'
            );
        }

        return $user;
    }

    /**
     * Staff can manage colors only for vehicles they created.
     * Admins can manage colors for all vehicles.
     */
    private function denyUnlessOwnerOrAdmin(
        Vehicle $vehicle,
        User $user
    ): void {
        if ($user->getRole() === User::ROLE_ADMIN) {
            return;
        }

        if ($vehicle->getCreatedBy()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException(
                'You can only manage colors for vehicles that you created.'
            );
        }
    }

    #[Route('', name: 'index', requirements: ['vehicleId' => '\d+'], methods: ['GET'])]
    public function index(
        int $vehicleId,
        EntityManagerInterface $em
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);

        if (!$vehicle) {
            throw $this->createNotFoundException('Vehicle not found.');
        }

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        return $this->render('manage/vehicle/colors/index.html.twig', [
            'vehicle' => $vehicle,
            'colors' => $em->getRepository(VehicleColor::class)->findBy(
                ['vehicle' => $vehicle],
                ['colorName' => 'ASC']
            ),
        ]);
    }

    #[Route('/new', name: 'new', requirements: ['vehicleId' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        int $vehicleId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);

        if (!$vehicle) {
            throw $this->createNotFoundException('Vehicle not found.');
        }

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        $color = (new VehicleColor())->setVehicle($vehicle);
        $name = '';
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('color_name'));
            $image = $request->files->get('image');

            if (!$this->isCsrfTokenValid(
                'vehicle_color_new_' . $vehicleId,
                $request->request->getString('_token')
            )) {
                $errors[] = 'Invalid form submission. Please refresh and try again.';
            }

            $this->validateColorName($name, $vehicle, null, $em, $errors);

            if (!$image instanceof UploadedFile) {
                $errors[] = 'Please upload a color image.';
            } elseif (!$this->isAllowedImage($image)) {
                $errors[] = 'Upload a valid JPG, PNG, or WebP image no larger than 5MB.';
            }

            if ($errors === []) {
                $color->setColorName($name);
                $color->setImagePath($this->storeImage($image));
                $color->setInStock($request->request->getBoolean('in_stock'));

                $em->persist($color);
                $em->flush();

                $this->addFlash('success', 'Vehicle color added successfully.');

                return $this->redirectToRoute('vehicle_color_index', [
                    'vehicleId' => $vehicleId,
                ]);
            }
        }

        return $this->render('manage/vehicle/colors/form.html.twig', [
            'vehicle' => $vehicle,
            'color' => $color,
            'name' => $name,
            'errors' => $errors,
            'isEdit' => false,
        ]);
    }

    #[Route('/{colorId}/edit', name: 'edit', requirements: ['vehicleId' => '\d+', 'colorId' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $vehicleId,
        int $colorId,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
        $color = $em->getRepository(VehicleColor::class)->find($colorId);

        if (
            !$vehicle ||
            !$color ||
            $color->getVehicle()?->getId() !== $vehicleId
        ) {
            throw $this->createNotFoundException('Vehicle color not found.');
        }

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        $name = (string) $color->getColorName();
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('color_name'));
            $image = $request->files->get('image');

            if (!$this->isCsrfTokenValid(
                'vehicle_color_edit_' . $colorId,
                $request->request->getString('_token')
            )) {
                $errors[] = 'Invalid form submission. Please refresh and try again.';
            }

            $this->validateColorName($name, $vehicle, $color, $em, $errors);

            if (
                $image !== null &&
                (!$image instanceof UploadedFile || !$this->isAllowedImage($image))
            ) {
                $errors[] = 'Upload a valid JPG, PNG, or WebP image no larger than 5MB.';
            }

            if ($errors === []) {
                $color->setColorName($name);
                $color->setInStock($request->request->getBoolean('in_stock'));

                if ($image instanceof UploadedFile) {
                    $color->setImagePath($this->storeImage($image));
                }

                $color->setUpdatedAt(new \DateTime());

                $em->flush();

                $this->addFlash('success', 'Vehicle color updated successfully.');

                return $this->redirectToRoute('vehicle_color_index', [
                    'vehicleId' => $vehicleId,
                ]);
            }
        }

        return $this->render('manage/vehicle/colors/form.html.twig', [
            'vehicle' => $vehicle,
            'color' => $color,
            'name' => $name,
            'errors' => $errors,
            'isEdit' => true,
        ]);
    }

    #[Route('/{colorId}/delete', name: 'delete', requirements: ['vehicleId' => '\d+', 'colorId' => '\d+'], methods: ['POST'])]
    public function delete(
        int $vehicleId,
        int $colorId,
        Request $request,
        EntityManagerInterface $em,
        Connection $connection
    ): Response {
        $user = $this->denyUnlessVehicleManager();

        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
        $color = $em->getRepository(VehicleColor::class)->find($colorId);

        if (
            !$vehicle ||
            !$color ||
            $color->getVehicle()?->getId() !== $vehicleId
        ) {
            throw $this->createNotFoundException('Vehicle color not found.');
        }

        $this->denyUnlessOwnerOrAdmin($vehicle, $user);

        if (!$this->isCsrfTokenValid(
            'vehicle_color_delete_' . $colorId,
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('vehicle_color_index', [
                'vehicleId' => $vehicleId,
            ]);
        }

        $used = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM reservations WHERE vehicle_id = ? AND color_id = ?',
            [$vehicleId, $colorId]
        );

        if ($used > 0) {
            $this->addFlash(
                'danger',
                'This color is linked to reservation history and cannot be deleted.'
            );

            return $this->redirectToRoute('vehicle_color_index', [
                'vehicleId' => $vehicleId,
            ]);
        }

        $em->remove($color);
        $em->flush();

        $this->addFlash('success', 'Vehicle color deleted successfully.');

        return $this->redirectToRoute('vehicle_color_index', [
            'vehicleId' => $vehicleId,
        ]);
    }

    /**
     * @param string[] $errors
     */
    private function validateColorName(
        string $name,
        Vehicle $vehicle,
        ?VehicleColor $current,
        EntityManagerInterface $em,
        array &$errors
    ): void {
        if ($name === '' || mb_strlen($name) > 60) {
            $errors[] = 'Color name is required and must not exceed 60 characters.';
            return;
        }

        $existing = $em->getRepository(VehicleColor::class)->findOneBy([
            'vehicle' => $vehicle,
            'colorName' => $name,
        ]);

        if ($existing && $existing->getId() !== $current?->getId()) {
            $errors[] = 'That color already exists for this vehicle.';
        }
    }

    private function isAllowedImage(UploadedFile $image): bool
    {
        return $image->isValid()
            && $image->getSize() <= 5 * 1024 * 1024
            && in_array(
                $image->getMimeType(),
                ['image/jpeg', 'image/png', 'image/webp'],
                true
            );
    }

    private function storeImage(UploadedFile $image): string
    {
        $directory = $this->getParameter('kernel.project_dir')
            . '/public/uploads/vehicles/colors';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = $image->guessExtension();

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Unsupported image format.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $image->move($directory, $filename);

        return 'uploads/vehicles/colors/' . $filename;
    }
}
