<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Series;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EpisodeController extends MotherController
{
    #[Route('/series/{id}/season/{num}/episode/{idE}/add', name: 'app_index_episode_add')]
    public function episodeAdd(SeriesRepository $repository, int $id, int $num, int $idE, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $series = $entityManager
        ->getRepository(Series::class);
        $seriesToAdd = $series->findBy(['id' => $id]);
        $seasons = $seriesToAdd[0]->getSeasons();
        $user = $this->getUser();
        $user->addSeries($seriesToAdd[0]);
        $entityManager->flush();
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getId() == $idE) {
                    $this->getUser()->addEpisode($episode);
                    $entityManager->persist($episode);
                    $entityManager->flush();
                    break 2;
                } else {
                    $this->getUser()->addEpisode($episode);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    #[Route('/series/{id}/season/{num}/episode/{idE}/remove', name: 'app_index_episode_remove')]
    public function episodeRemove(SeriesRepository $repository, int $id, int $num, int $idE, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $series = $entityManager
        ->getRepository(Series::class);
        $seriesToRemove = $series->findBy(['id' => $id]);
        $seasons = $seriesToRemove[0]->getSeasons();
        $user = $this->getUser();
        $count = 0;
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($this->getUser()->getEpisode()->contains($episode)) {
                    ++$count;
                }
            }
        }
        if (1 == $count) {
            $user->removeSeries($seriesToRemove[0]);
            $entityManager->flush();
        }
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getId() == $idE) {
                    $this->getUser()->removeEpisode($episode);
                    $entityManager->flush();
                    break 2;
                }
            }
        }

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }
}
