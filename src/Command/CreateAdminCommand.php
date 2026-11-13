<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an administrator account using an interactively entered password.'
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = trim((string) $io->ask('Administrator full name'));
        $username = trim((string) $io->ask('Username'));
        $email = mb_strtolower(trim((string) $io->ask('Email address')));
        $password = (string) $io->askHidden('Password (at least 12 characters)');
        $confirmation = (string) $io->askHidden('Confirm password');

        if ($name === '' || mb_strlen($name) > 120) {
            $io->error('Name is required and must not exceed 120 characters.');
            return Command::FAILURE;
        }

        if ($username === '' || mb_strlen($username) > 60) {
            $io->error('Username is required and must not exceed 60 characters.');
            return Command::FAILURE;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $io->error('Enter a valid email address.');
            return Command::FAILURE;
        }

        if (strlen($password) < 12 || $password !== $confirmation) {
            $io->error('The password must be at least 12 characters and both entries must match.');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['username' => $username])) {
            $io->error('That username is already in use.');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error('That email address is already in use.');
            return Command::FAILURE;
        }

        $admin = (new User())
            ->setName($name)
            ->setUsername($username)
            ->setEmail($email)
            ->setRole(User::ROLE_ADMIN);

        $admin->setPasswordHash($this->passwordHasher->hashPassword($admin, $password));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrator account created. Sign in using the username or email you entered.');

        return Command::SUCCESS;
    }
}
