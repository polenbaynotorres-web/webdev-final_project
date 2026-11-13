<?php

namespace App\Controller\User;

use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleColor;
use App\Repository\VehicleRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class VehicleController extends AbstractController
{
    #[Route('/fleet/{slug}', name: 'vehicle_details', methods: ['GET'])]
    public function details(string $slug, VehicleRepository $vehicles, EntityManagerInterface $em): Response
    {
        $vehicle = $vehicles->findOneBy(['slug' => $slug]);
        if (!$vehicle) throw $this->createNotFoundException('Vehicle not found.');
        return $this->render('fleet/details.html.twig', [
            'vehicle' => $vehicle,
            'colors' => $em->getRepository(VehicleColor::class)->findBy(['vehicle' => $vehicle]),
        ]);
    }

    #[Route('/fleet/{slug}/book', name: 'user_vehicle_book', methods: ['GET', 'POST'])]
    public function book(string $slug, Request $request, VehicleRepository $vehicles, EntityManagerInterface $em, Connection $db): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_USER) {
            if (!$user) return $this->redirectToRoute('app_login');
            throw $this->createAccessDeniedException();
        }
        $vehicle = $vehicles->findOneBy(['slug' => $slug]);
        if (!$vehicle) throw $this->createNotFoundException('Vehicle not found.');
        $colors = $em->getRepository(VehicleColor::class)->findBy(['vehicle' => $vehicle, 'inStock' => true]);
        $locations = $em->getRepository(Location::class)->findBy([], ['name' => 'ASC']);
        $errors = [];
        $values = ['color_id' => '', 'phone' => '', 'pickup_date' => '', 'pickup_time' => '', 'return_date' => '', 'return_time' => '', 'location_id' => '', 'payment_method' => 'cash'];
        if ($request->isMethod('POST')) {
            foreach ($values as $key => $default) $values[$key] = trim($request->request->getString($key, $default));
            if (!$this->isCsrfTokenValid('book_vehicle_'.$vehicle->getId(), $request->request->getString('_token'))) $errors[] = 'Invalid form security token. Refresh and try again.';
            $color = $em->getRepository(VehicleColor::class)->findOneBy(['id' => (int)$values['color_id'], 'vehicle' => $vehicle, 'inStock' => true]);
            $location = $em->getRepository(Location::class)->find((int)$values['location_id']);
            if (!$color) $errors[] = 'Choose an available vehicle color.';
            if (!$location) $errors[] = 'Choose a valid pickup location.';
            if (!preg_match('/^[0-9+()\- ]{7,30}$/', $values['phone'])) $errors[] = 'Enter a valid contact number.';
            foreach (['pickup_date', 'pickup_time', 'return_date', 'return_time'] as $dateField) {
                if ($values[$dateField] === '') $errors[] = 'Complete all pickup and return date/time fields.';
            }
            try {
                $pickup = new \DateTime($values['pickup_date'].' '.$values['pickup_time']);
                $return = new \DateTime($values['return_date'].' '.$values['return_time']);
                if ($pickup <= new \DateTime()) $errors[] = 'Pickup date and time must be in the future.';
                if ($return <= $pickup) $errors[] = 'Return must be later than pickup.';
            } catch (\Throwable) { $errors[] = 'Enter valid pickup and return dates and times.'; }
            if (!in_array($values['payment_method'], ['cash', 'gcash', 'card'], true)) $errors[] = 'Choose a valid payment method.';
            if ($errors === []) {
                $conflict = (int)$db->fetchOne("SELECT COUNT(*) FROM reservations WHERE color_id = ? AND vehicle_id = ? AND status IN ('confirmed','pending') AND pickup_date < ? AND return_date >= ?", [$color->getId(), $vehicle->getId(), $return->format('Y-m-d'), $pickup->format('Y-m-d')]);
                if ($conflict > 0) $errors[] = 'This vehicle/color is already reserved for part of your selected dates.';
            }
            if ($errors === []) {
                $reservation = (new Reservation())->setUser($user)->setVehicle($vehicle)->setColorId($color->getId())->setLocation($location)->setPhone($values['phone'])->setPickupDate(new \DateTime($pickup->format('Y-m-d')))->setPickupTime(new \DateTime($pickup->format('H:i:s')))->setReturnDate(new \DateTime($return->format('Y-m-d')))->setReturnTime(new \DateTime($return->format('H:i:s')))->setPaymentMethod($values['payment_method'])->setStatus('pending');
                $color->setInStock(false)->setUpdatedAt(new \DateTime());
                $em->persist($reservation); $em->flush();
                $this->addFlash('success', 'Your reservation request has been submitted and is pending Admin or Staff approval.');
                return $this->redirectToRoute('user_reservation_index');
            }
        }
        return $this->render('fleet/book.html.twig', compact('vehicle', 'colors', 'locations', 'errors', 'values'));
    }
}
