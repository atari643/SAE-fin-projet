<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:generate-reviews',
    description: 'Générer des critiques fictives dans la base de données',
)]
class GenerateReviewsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('numberOfReviews', InputArgument::REQUIRED, "Le nombre de critiques à générer")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfReviews = $input->getArgument('numberOfReviews');

        $io = new SymfonyStyle($input, $output);
        
        for($i = 0; $i < $numberOfReviews; $i++){

            $userRows = $this->entityManager->createQuery('SELECT COUNT(u.id) FROM App:User u')->getSingleScalarResult();
            $offset = max(0, rand(0, $userRows - 1 - 1));
            $query = $this->entityManager->createQuery('SELECT DISTINCT u FROM App:User u')->setMaxResults(1)->setFirstResult($offset);
            $result = $query->getResult();
            $user = $result[0];

            if($user->isFake()){



            }

            /*$this->entityManager->persist($user);
            $this->entityManager->flush();*/

            $io->success($name);

        }

        return Command::SUCCESS;
    }
}
