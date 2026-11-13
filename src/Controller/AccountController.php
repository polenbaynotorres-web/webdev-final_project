<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AccountController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile', methods: ['GET','POST'])]
    #[Route('/staff/profile', name: 'staff_profile', methods: ['GET','POST'])]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !in_array($user->getRole(), [User::ROLE_USER, User::ROLE_STAFF], true)) throw $this->createAccessDeniedException();
        $staff = $user->getRole() === User::ROLE_STAFF;
        $route = $staff ? 'staff_profile' : 'user_profile';
        $errors = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('account_profile_'.$user->getId(), $request->request->getString('_token'))) $errors[] = 'Invalid form security token.';
            $name = trim($request->request->getString('name'));
            $email = strtolower(trim($request->request->getString('email')));
            $password = $request->request->getString('new_password');
            $current = $request->request->getString('current_password');
            if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Enter a valid name (maximum 120 characters).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) $errors[] = 'That email address is already in use.';
            if ($password !== '') {
                if (!$hasher->isPasswordValid($user, $current)) $errors[] = 'Your current password is incorrect.';
                if (strlen($password) < 8) $errors[] = 'New password must be at least 8 characters.';
            }
            if ($errors === []) {
                $user->setName($name)->setEmail($email);
                if ($password !== '') $user->setPasswordHash($hasher->hashPassword($user, $password));
                $em->flush();
                $this->addFlash('success', 'Your account details have been updated.');
                return $this->redirectToRoute($route);
            }
        }
        return $this->render($staff ? 'staff/profile/index.html.twig' : 'User/profile/index.html.twig', ['account' => $user, 'errors' => $errors]);
    }
}
