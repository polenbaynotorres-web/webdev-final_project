<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $name = '';
        $username = '';
        $email = '';
        $errors = [];

        if ($request->isMethod('POST')) {
            $name = trim($request->request->getString('name'));
            $username = trim($request->request->getString('username'));
            $email = mb_strtolower(trim($request->request->getString('email')));
            $password = $request->request->getString('password');
            $confirmPassword = $request->request->getString('confirm_password');
            $csrfToken = $request->request->getString('_token');

            // CSRF validation
            if (!$this->isCsrfTokenValid('register_customer', $csrfToken)) {
                $errors[] = 'Invalid security token. Please refresh and try again.';
            }

            // Name validation
            if ($name === '' || mb_strlen($name) > 120) {
                $errors[] = 'Please enter a valid name (maximum 120 characters).';
            }

            // Username validation
            if ($username === '' || mb_strlen($username) > 60) {
                $errors[] = 'Please enter a username (maximum 60 characters).';
            } elseif ($userRepository->findOneByIdentifier($username) !== null) {
                $errors[] = 'That username is already taken.';
            }

            // Email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $errors[] = 'Please enter a valid email address.';
            } elseif ($userRepository->findOneByIdentifier($email) !== null) {
                $errors[] = 'That email address is already registered.';
            }

            // Password validation
            if (strlen($password) < 8) {
                $errors[] = 'Your password must contain at least 8 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match.';
            }

            // Create customer account
            if ($errors === []) {
                $user = new User();

                $user->setName($name);
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setRole(User::ROLE_USER);

                $user->setPasswordHash(
                    $passwordHasher->hashPassword($user, $password)
                );

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Your customer account has been created. You can now log in.'
                );

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'errors' => $errors,
        ]);
    }
}
