<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Repository\VehicleColorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FleetController extends AbstractController
{
    #[Route('/fleet', name: 'fleet_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepository,
        VehicleColorRepository $vehicleColorRepository
    ): Response {

        // Retrieve all vehicles.
        $vehicles = $vehicleRepository->findBy(
            [],
            ['id' => 'ASC']
        );

        // Retrieve all vehicle colors.
        $allColors = $vehicleColorRepository->findBy(
            [],
            ['colorName' => 'ASC']
        );

        // Group available colors by vehicle ID.
        $colorsByVehicle = [];

        foreach ($allColors as $color) {

            if (!$color->isInStock()) {
                continue;
            }

            $vehicleId = $color->getVehicle()?->getId();

            if ($vehicleId !== null) {
                $colorsByVehicle[$vehicleId][] = $color;
            }
        }

        return $this->render('fleet/index.html.twig', [
            'vehicles' => $vehicles,
            'colorsByVehicle' => $colorsByVehicle,
        ]);
    }
}
