<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Series;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeasonController extends MotherController
{
    private function getRatings(EntityManagerInterface $entityManager, int $id)
    {
        // Recupérer l'avis de l'utilisateur actif
        $userRating = $entityManager->getRepository(Rating::class)->findOneBy([
            'user' => $this->getUser(),
            'series' => $id,
        ]);

        $comments = $entityManager->getRepository(Rating::class)->findBy([
            'series' => $id,
        ]);

        return [
            'userRating' => $userRating ? $userRating : null,
            'userValue' => $userRating ? $userRating->getValue() : null,
            'userComment' => $userRating ? $userRating->getComment() : null,
            'comments' => $comments,
        ];
    }

    #[Route('/series/{id}/season/{num}', name: 'app_index_season_info')]
    public function seasonInfo(SeriesRepository $repository, int $id, int $num, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $series = $repository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        $infoRating = $this->getRatings($entityManager, $id);
        $episodes = $repository->seriesInfoByIdAndSeason($id, $num)->getSeasons()->get($num - 1)->getEpisodes();
        $pagination = $paginator->paginate(
            $episodes,
            'episodes' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $pagination->setParam('pageList', 'episodes');

        $paginationSeason = $paginator->paginate(
            $seasons,
            'seasons' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $paginationSeason->setParam('pageList', 'seasons');
        $val = 0;
        $nombreNotes = 0;
        $comments = $infoRating['comments'];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $val = $val + $comment->getValue();
                ++$nombreNotes;
            }
            $val = substr($val / $nombreNotes, 0, 3);
        }
        $paginationComments = $paginator->paginate(
            $comments,
            'comments' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );

        $user = $this->getUser();
        $seriesView = $repository->seriesEpisodeCountView($user);

        $paginationComments->setParam('pageList', 'comments');

        return $this->render(
            'index/seriesInfo.html.twig',
            [
                'series' => $series,
                'paginationSeason' => $paginationSeason,
                'pagination' => $pagination,
                'userRating' => $infoRating['userRating'] ? $infoRating['userValue'] : null,
                'userComment' => $infoRating['userRating'] ? $infoRating['userComment'] : null,
                'paginationComments' => $paginationComments,
                'serieScore' => $val,
                'nombreNotes' => $nombreNotes,
                'seriesView' => $seriesView,
            ]
        );
    }

    #[Route('/series/{id}/season/{num}/add', name: 'app_index_season_info_add')]
    public function seasonAdd(SeriesRepository $repository, int $id, int $num, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $series = $entityManager
        ->getRepository(Series::class);
        $seriesToAdd = $series->findBy(['id' => $id]);
        $seasons = $seriesToAdd[0]->getSeasons();
        $user = $this->getUser();
        $user->addSeries($seriesToAdd[0]);
        $entityManager->flush();
        foreach ($seasons as $season) {
            if ($season->getNumber() == $num) {
                foreach ($season->getEpisodes() as $episode) {
                    $this->getUser()->addEpisode($episode);
                    $entityManager->flush();
                }
            }
        }

    foreach ($seriesToAdd->getSeasons() as $season) {
        if ($season->getNumber() == $num) {
            foreach ($season->getEpisodes() as $episode) {
                $user->addEpisode($episode);
            }
        }
    }

    $entityManager->flush();

    return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
}

#[Route('/series/{id}/season/{num}/remove', name: 'app_index_season_info_remove')]
public function seasonRemove(SeriesRepository $repository, int $id, int $num, EntityManagerInterface $entityManager): Response
{
    $seriesToRemove = $repository->find($id);
    $user = $this->getUser();

    foreach ($seriesToRemove->getSeasons() as $season) {
        if ($season->getNumber() == $num) {
            foreach ($season->getEpisodes() as $episode) {
                if ($user->getEpisode()->contains($episode)) {
                    $user->removeEpisode($episode);
                }
            }
        }
    }
    $entityManager->flush();
    $count = $repository->seriesEpisodeCountViewBySeries($user, $seriesToRemove);
    if ($count==[]) {
        $user->removeSeries($seriesToRemove);
    }
   

    $entityManager->flush();

    return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
}
}
