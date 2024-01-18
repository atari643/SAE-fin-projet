<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Faker;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-users',
    description: 'Générer des utilisateurs fictifs dans la base de données',
)]
class GenerateUsersCommand extends Command
{
    private $entityManager;
    private $userPasswordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('numberOfUsers', InputArgument::REQUIRED, "Le nombre d'utilisateurs à générer")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfUsers = $input->getArgument('numberOfUsers');

        $io = new SymfonyStyle($input, $output);

        for ($i = 0; $i < $numberOfUsers; $i++) {
            $user = new User();
            $faker = Faker\Factory::create();
            $name = $faker->name();
            $email = $faker->email();
            $password = $this->generatePassword(6);

            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $password
                )
            );
            $user->setFake(true);
            $this->entityManager->persist($user);

            $io->success($name . " " . $email);
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }

    private function generatePassword($length, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
