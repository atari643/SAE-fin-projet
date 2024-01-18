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
use Faker;
use App\Entity\Rating;
use App\Entity\Series;
use App\Entity\User;

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
            ->addArgument('average', InputArgument::OPTIONAL, "La moyenne pour la fonction de Gauss")
            ->addArgument('deviation', InputArgument::OPTIONAL, "L'écart type pour la fonction de Gauss")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfReviews = $input->getArgument('numberOfReviews');
        $average = $input->getArgument('average');
        $deviation = $input->getArgument('deviation');

        if ($average == null) {
            $average = 3;
        }

        if ($deviation == null) {
            $deviation = 2;
        }

        $io = new SymfonyStyle($input, $output);

        for ($i = 0; $i < $numberOfReviews; $i++) {
            $userRows = $this->entityManager->createQuery('SELECT COUNT(u.id) FROM App:User u')->getSingleScalarResult();
            $offset = max(0, rand(0, $userRows - $userRows - 1));
            $query = $this->entityManager->createQuery('SELECT DISTINCT u FROM App:User u')->setMaxResults($userRows)->setFirstResult($offset);
            $usersResult = $query->getResult();

            $found = false;
            $y = 0;
            while (!$found && $y < $userRows) {
                $userRows = $this->entityManager->createQuery('SELECT COUNT(u.id) FROM App:User u')->getSingleScalarResult();
                $offset = max(0, rand(0, $userRows - 1 - 1));
                $query = $this->entityManager->createQuery('SELECT DISTINCT u FROM App:User u')->setMaxResults(1)->setFirstResult($offset);
                $usersResult = $query->getResult();

                $user = $usersResult[0];
                if ($user->isFake()) {
                    $seriesRows = $this->entityManager->createQuery('SELECT COUNT(s.id) FROM App:Series s')->getSingleScalarResult();
                    $seriesOffset = max(0, rand(0, $seriesRows - 1 - 1));
                    $seriesQuery = $this->entityManager->createQuery('SELECT DISTINCT s FROM App:Series s')->setMaxResults(1)->setFirstResult($seriesOffset);
                    $seriesResult = $seriesQuery->getResult();

                    $series = $seriesResult[0];

                    $this->createRating($series, $user);

                    $io->success($user->getName() . " - " . $series->getTitle());

                    $found = true;
                }

                $y++;
            }

            if (!$found) {
                $io->error("Il n'y a aucun faux compte dans la base de données !");
                return Command::FAILURE;
            }
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }

    private function createRating(Series $series, User $user)
    {

        $faker = Faker\Factory::create();
        $rating = new Rating();
        $rating->setValue(rand(0, 5));
        $rating->setComment($faker->text());
        $rating->setDate(new \DateTime());
        $rating->setSeries($series);
        $rating->setUser($user);
        $this->entityManager->persist($rating);
    }

    private function gaussianRandom($mean, $stdDev, $min, $max)
    {
        do {
            $rand1 = (float)rand() / (float)getrandmax();
            $rand2 = (float)rand() / (float)getrandmax();
            $gaussianNumber = sqrt(-2 * log($rand1)) * cos(2 * M_PI * $rand2);
            $scaledGaussian = $mean + ($stdDev * $gaussianNumber);
        } while ($scaledGaussian < $min || $scaledGaussian > $max);

        return $scaledGaussian;
    }
}
