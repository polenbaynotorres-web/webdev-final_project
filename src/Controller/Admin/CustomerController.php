<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/customers', name: 'admin_customer_')]
#[IsGranted('ROLE_ADMIN')]
final class CustomerController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $search = $request->query->getString('search');

        return $this->render('admin/customer/index.html.twig', [
            'customers' => $userRepository->searchByRole(User::ROLE_USER, $search),
            'search' => $search,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $customer = $this->findCustomer($id, $userRepository);
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $username = trim($request->request->getString('username'));
            $email = mb_strtolower(trim($request->request->getString('email')));
            $password = $request->request->getString('password');

            if (!$this->isCsrfTokenValid(
                'customer_edit_' . $id,
                $request->request->getString('_token')
            )) {
                $errors[] = 'Invalid form submission.';
            }

            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = 'Full name is required (maximum 120 characters).';
            }

            if ($username === '' || mb_strlen($username) > 60) {
                $errors[] = 'Username is required (maximum 60 characters).';
            } else {
                $existing = $userRepository->findOneBy(['username' => $username]);

                if ($existing && $existing->getId() !== $id) {
                    $errors[] = 'Username is already taken.';
                }
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $errors[] = 'Enter a valid email address.';
            } else {
                $existing = $userRepository->findOneBy(['email' => $email]);

                if ($existing && $existing->getId() !== $id) {
                    $errors[] = 'Email address is already registered.';
                }
            }

            if ($password !== '' && strlen($password) < 8) {
                $errors[] = 'New password must contain at least 8 characters.';
            }

            if ($errors === []) {
                $customer->setName($name);
                $customer->setUsername($username);
                $customer->setEmail($email);

                if ($password !== '') {
                    $customer->setPasswordHash(
                        $passwordHasher->hashPassword($customer, $password)
                    );
                }

                $entityManager->flush();

                $this->addFlash('success', 'Customer account updated successfully.');

                return $this->redirectToRoute('admin_customer_index');
            }

            $entityManager->refresh($customer);
            $customer->setName($name);
            $customer->setUsername($username);
            $customer->setEmail($email);
        }

        return $this->render('admin/customer/edit.html.twig', [
            'customer' => $customer,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/status', name: 'status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $customer = $this->findCustomer($id, $userRepository);

        if (!$this->isCsrfTokenValid(
            'customer_status_' . $id,
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid request.');

            return $this->redirectToRoute('admin_customer_index');
        }

        $customer->setStatus(
            $customer->isActive()
                ? User::STATUS_DISABLED
                : User::STATUS_ACTIVE
        );

        $entityManager->flush();

        $this->addFlash(
            'success',
            $customer->isActive()
                ? 'Customer account enabled.'
                : 'Customer account disabled. They can no longer log in.'
        );

        return $this->redirectToRoute('admin_customer_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $customer = $this->findCustomer($id, $userRepository);

        if (!$this->isCsrfTokenValid(
            'customer_delete_' . $id,
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('admin_customer_index');
        }

        // Soft deletion preserves reservations and related records.
        $customer->setStatus(User::STATUS_DELETED);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Customer account deleted. Reservation history and related records have been preserved.'
        );

        return $this->redirectToRoute('admin_customer_index');
    }

    private function findCustomer(int $id, UserRepository $userRepository): User
    {
        $customer = $userRepository->find($id);

        if (
            !$customer ||
            $customer->getRole() !== User::ROLE_USER ||
            $customer->isDeleted()
        ) {
            throw $this->createNotFoundException('Customer account not found.');
        }

        return $customer;
    }
}
