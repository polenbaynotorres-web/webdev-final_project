<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/dashboard/locations', name: 'admin_location_')]
#[IsGranted('ROLE_ADMIN')]
final class LocationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        LocationRepository $locationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $name = '';
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $errors = $this->validate($name, null, $request, 'location_new', $locationRepository);

            if ($errors === []) {
                $location = (new Location())
                    ->setName($name)
                    ->setSlug($this->slugFor($name));
                $entityManager->persist($location);
                $entityManager->flush();

                $this->addFlash('success', 'Location added successfully.');

                return $this->redirectToRoute('admin_location_index');
            }
        }

        return $this->render('admin/location/index.html.twig', [
            'locations' => $locationRepository->findBy([], ['name' => 'ASC']),
            'name' => $name,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        LocationRepository $locationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $location = $locationRepository->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Location not found.');
        }

        $name = (string) $location->getName();
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $errors = $this->validate($name, $location, $request, 'location_edit_' . $id, $locationRepository);

            if ($errors === []) {
                $location->setName($name)->setSlug($this->slugFor($name));
                $entityManager->flush();

                $this->addFlash('success', 'Location updated successfully.');

                return $this->redirectToRoute('admin_location_index');
            }
        }

        return $this->render('admin/location/edit.html.twig', [
            'location' => $location,
            'name' => $name,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        LocationRepository $locationRepository,
        EntityManagerInterface $entityManager,
        Connection $connection
    ): Response {
        $location = $locationRepository->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Location not found.');
        }

        if (!$this->isCsrfTokenValid('location_delete_' . $id, $request->request->getString('_token'))) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('admin_location_index');
        }

        $used = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM reservations WHERE location_id = ?',
            [$id]
        );

        if ($used > 0) {
            $this->addFlash('danger', 'This location is used by existing reservations and cannot be deleted.');

            return $this->redirectToRoute('admin_location_index');
        }

        $entityManager->remove($location);
        $entityManager->flush();

        $this->addFlash('success', 'Location deleted successfully.');

        return $this->redirectToRoute('admin_location_index');
    }

    /**
     * @return string[]
     */
    private function validate(
        string $name,
        ?Location $current,
        Request $request,
        string $tokenId,
        LocationRepository $locationRepository
    ): array {
        $errors = [];

        if (!$this->isCsrfTokenValid($tokenId, $request->request->getString('_token'))) {
            $errors[] = 'Invalid form submission.';
        }

        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'Location name is required (maximum 120 characters).';

            return $errors;
        }

        $slug = $this->slugFor($name);

        if ($slug === '') {
            $errors[] = 'Location name must contain letters or numbers.';

            return $errors;
        }

        foreach ([['name' => $name], ['slug' => $slug]] as $criteria) {
            $existing = $locationRepository->findOneBy($criteria);

            if ($existing && $existing->getId() !== $current?->getId()) {
                $errors[] = 'A location with this name already exists.';
                break;
            }
        }

        return $errors;
    }

    private function slugFor(string $name): string
    {
        return mb_substr((new AsciiSlugger())->slug($name)->lower()->toString(), 0, 60);
    }
}
