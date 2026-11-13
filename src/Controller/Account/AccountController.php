<?php

namespace App\Controller\Account;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Own-account pages shared by customers, staff and administrators.
 */
#[Route('/account', name: 'account_')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    private function layout(): string
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return 'admin/base.html.twig';
        }

        if ($this->isGranted('ROLE_STAFF')) {
            return 'staff/base.html.twig';
        }

        return 'customer/portal.html.twig';
    }

    private function currentUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->currentUser();
        $name = (string) $user->getName();
        $email = (string) $user->getEmail();
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $email = mb_strtolower(trim($request->request->getString('email')));

            if (!$this->isCsrfTokenValid(
                'account_profile',
                $request->request->getString('_token')
            )) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = 'Please enter your name (maximum 120 characters).';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                $existing = $userRepository->findOneBy(['email' => $email]);

                if ($existing && $existing->getId() !== $user->getId()) {
                    $errors[] = 'That email address is already registered.';
                }
            }

            if ($errors === []) {
                $user->setName($name);
                $user->setEmail($email);

                $entityManager->flush();

                $this->addFlash('success', 'Your profile has been updated.');

                return $this->redirectToRoute('account_profile');
            }
        }

        return $this->render('account/profile.html.twig', [
            'layout' => $this->layout(),
            'account' => $user,
            'name' => $name,
            'email' => $email,
            'errors' => $errors,
        ]);
    }

    #[Route('/password', name: 'password', methods: ['GET', 'POST'])]
    public function password(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->currentUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $current = $request->request->getString('current_password');
            $new = $request->request->getString('new_password');
            $confirm = $request->request->getString('confirm_password');

            if (!$this->isCsrfTokenValid(
                'account_password',
                $request->request->getString('_token')
            )) {
                $errors[] = 'Invalid security token. Please try again.';
            }

            if (!$passwordHasher->isPasswordValid($user, $current)) {
                $errors[] = 'Your current password is incorrect.';
            }

            if (strlen($new) < 8) {
                $errors[] = 'Your new password must contain at least 8 characters.';
            }

            if ($new !== $confirm) {
                $errors[] = 'Password confirmation does not match.';
            }

            if ($errors === [] && $new === $current) {
                $errors[] = 'Your new password must be different from the current one.';
            }

            if ($errors === []) {
                $user->setPasswordHash(
                    $passwordHasher->hashPassword($user, $new)
                );

                $entityManager->flush();

                $this->addFlash('success', 'Your password has been changed.');

                return $this->redirectToRoute('account_profile');
            }
        }

        return $this->render('account/password.html.twig', [
            'layout' => $this->layout(),
            'errors' => $errors,
        ]);
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->currentUser();

        // Only customer accounts may delete themselves.
        if ($user->getRole() !== User::ROLE_USER) {
            throw $this->createAccessDeniedException(
                'Only customer accounts can use this option.'
            );
        }

        $currentPassword = $request->request->getString('current_password');

        if (!$this->isCsrfTokenValid(
            'account_delete',
            $request->request->getString('_token')
        )) {
            $this->addFlash('danger', 'Invalid security token.');

            return $this->redirectToRoute('account_profile');
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('danger', 'Your current password is incorrect.');

            return $this->redirectToRoute('account_profile');
        }

        // Soft deletion preserves the account and its related records.
        $user->setStatus(User::STATUS_DELETED);

        $entityManager->flush();

        // Invalidate the current session after the account is deleted.
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        return $this->redirectToRoute('app_login');
    }
}
