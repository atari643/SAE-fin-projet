<?php

namespace App\Controller;


use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EpisodeController extends MotherController
{
    #[Route('/series/{id}/season/{num}/episode/{idE}/add', name: 'app_index_episode_add')]
    public function episodeAdd(SeriesRepository $seriesRepository, int $id, int $idE, EntityManagerInterface $entityManager): Response
    {
        $seriesToAdd = $seriesRepository->seriesInfoById($id);
        $seasons = $seriesToAdd->getSeasons();
        $user = $this->getUser();
        $user->addSeries($seriesToAdd);
        $entityManager->flush();
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                if ($episode->getId() == $idE) {
                    $this->getUser()->addEpisode($episode);
                    $entityManager->persist($episode);
                    break 2;
                } else {
                    $this->getUser()->addEpisode($episode);
                }
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    #[Route('/series/{id}/season/{num}/episode/{idE}/remove', name: 'app_index_episode_remove')]
    public function episodeRemove(SeriesRepository $seriesRepository, int $id, int $idE, EntityManagerInterface $entityManager): Response
    {
        $seriesToRemove = $seriesRepository->seriesInfoById($id);
        $seasons = $seriesToRemove->getSeasons();
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
            $user->removeSeries($seriesToRemove);
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
