<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/dashboard/staff', name: 'admin_staff_')]
#[IsGranted('ROLE_ADMIN')]
final class StaffController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $search = trim($request->query->getString('search'));

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_STAFF)
            ->orderBy('u.id', 'DESC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(u.name) LIKE :search
                    OR LOWER(u.username) LIKE :search
                    OR LOWER(u.email) LIKE :search'
                )
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $this->render('admin/staff/index.html.twig', [
            'staffMembers' => $queryBuilder->getQuery()->getResult(),
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $staff = new User();
        $staff->setRole(User::ROLE_STAFF);
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $username = trim($request->request->getString('username'));
            $email = strtolower(trim($request->request->getString('email')));
            $password = $request->request->getString('password');
            $token = $request->request->get('_token');

            if (!is_string($token) || !$this->isCsrfTokenValid('staff_create', $token)) {
                $errors[] = 'Invalid form submission.';
            }

            if ($name === '') {
                $errors[] = 'Full name is required.';
            }

            if ($username === '') {
                $errors[] = 'Username is required.';
            } elseif ($userRepository->findOneBy(['username' => $username])) {
                $errors[] = 'Username is already taken.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            } elseif ($userRepository->findOneBy(['email' => $email])) {
                $errors[] = 'Email address is already registered.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must contain at least 8 characters.';
            }

            $staff->setName($name);
            $staff->setUsername($username);
            $staff->setEmail($email);

            if ($errors === []) {
                $staff->setPasswordHash(
                    $passwordHasher->hashPassword($staff, $password)
                );

                $entityManager->persist($staff);
                $entityManager->flush();

                $this->addFlash('success', 'Staff account created successfully.');

                return $this->redirectToRoute('admin_staff_index');
            }
        }

        return $this->render('admin/staff/new.html.twig', [
            'staff' => $staff,
            'errors' => $errors,
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
        $staff = $userRepository->find($id);

        if (!$staff || $staff->getRole() !== User::ROLE_STAFF) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $username = trim($request->request->getString('username'));
            $email = strtolower(trim($request->request->getString('email')));
            $password = $request->request->getString('password');
            $token = $request->request->get('_token');

            if (!is_string($token) || !$this->isCsrfTokenValid('staff_edit_' . $id, $token)) {
                $errors[] = 'Invalid form submission.';
            }

            if ($name === '') {
                $errors[] = 'Full name is required.';
            }

            $existingUsername = $userRepository->findOneBy(['username' => $username]);

            if ($username === '') {
                $errors[] = 'Username is required.';
            } elseif ($existingUsername && $existingUsername->getId() !== $id) {
                $errors[] = 'Username is already taken.';
            }

            $existingEmail = $userRepository->findOneBy(['email' => $email]);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            } elseif ($existingEmail && $existingEmail->getId() !== $id) {
                $errors[] = 'Email address is already registered.';
            }

            if ($password !== '' && strlen($password) < 8) {
                $errors[] = 'New password must contain at least 8 characters.';
            }

            if ($errors === []) {
                $staff->setName($name);
                $staff->setUsername($username);
                $staff->setEmail($email);
                $staff->setRole(User::ROLE_STAFF);

                if ($password !== '') {
                    $staff->setPasswordHash(
                        $passwordHasher->hashPassword($staff, $password)
                    );
                }

                $entityManager->flush();

                $this->addFlash('success', 'Staff account updated successfully.');

                return $this->redirectToRoute('admin_staff_index');
            }

            $staff->setName($name);
            $staff->setUsername($username);
            $staff->setEmail($email);
        }

        return $this->render('admin/staff/edit.html.twig', [
            'staff' => $staff,
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
        $staff = $userRepository->find($id);

        if (!$staff || $staff->getRole() !== User::ROLE_STAFF) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        if (!$this->isCsrfTokenValid(
            'staff_status_' . $id,
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid account status request.');

            return $this->redirectToRoute('admin_staff_index');
        }

        $staff->setStatus(
            $staff->isActive()
                ? User::STATUS_DISABLED
                : User::STATUS_ACTIVE
        );

        $entityManager->flush();

        $this->addFlash(
            'success',
            $staff->isActive()
                ? 'Staff account enabled.'
                : 'Staff account disabled. They can no longer log in.'
        );

        return $this->redirectToRoute('admin_staff_index');
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Connection $connection
    ): Response {
        $staff = $userRepository->find($id);

        if (!$staff || $staff->getRole() !== User::ROLE_STAFF) {
            throw $this->createNotFoundException('Staff account not found.');
        }

        $token = $request->request->get('_token');

        if (!is_string($token) || !$this->isCsrfTokenValid('staff_delete_' . $id, $token)) {
            $this->addFlash('danger', 'Invalid deletion request.');

            return $this->redirectToRoute('admin_staff_index');
        }

        $relatedRecords = (int) $connection->fetchOne(
            'SELECT
                (SELECT COUNT(*) FROM reservations WHERE user_id = ?) +
                (SELECT COUNT(*) FROM messages WHERE user_id = ?) +
                (SELECT COUNT(*) FROM subscribers WHERE user_id = ?)',
            [$id, $id, $id]
        );

        if ($relatedRecords > 0) {
            $this->addFlash(
                'danger',
                'This Staff account has related records and cannot be deleted.'
            );

            return $this->redirectToRoute('admin_staff_index');
        }

        $entityManager->remove($staff);
        $entityManager->flush();

        $this->addFlash('success', 'Staff account deleted successfully.');

        return $this->redirectToRoute('admin_staff_index');
    }
}
