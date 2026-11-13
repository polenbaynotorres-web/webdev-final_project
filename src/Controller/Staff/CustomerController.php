<?php

namespace App\Controller\Staff;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/customers', name: 'staff_customer_')]
#[IsGranted('ROLE_STAFF')]
final class CustomerController extends AbstractController
{
    private function checkStaff(): void
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_STAFF) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $this->checkStaff();

        $search = $request->query->getString('search');

        return $this->render('staff/customer/index.html.twig', [
            'customers' => $userRepository->searchByRole(User::ROLE_USER, $search),
            'search' => $search,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkStaff();

        $customer = $userRepository->find($id);

        if (
            !$customer ||
            $customer->getRole() !== User::ROLE_USER ||
            $customer->isDeleted()
        ) {
            throw $this->createNotFoundException('Customer account not found.');
        }

        if (!$this->isCsrfTokenValid(
            'staff_customer_delete_' . $id,
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('staff_customer_index');
        }

        // Soft deletion preserves reservations and related records.
        $customer->setStatus(User::STATUS_DELETED);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Customer account deleted. Related records have been preserved.'
        );

        return $this->redirectToRoute('staff_customer_index');
    }
}