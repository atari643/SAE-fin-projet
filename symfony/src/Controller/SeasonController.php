<?php

namespace App\Controller;


use App\Repository\RatingRepository;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeasonController extends MotherController
{
    #[Route('/series/{id}/season/{num}', name: 'app_index_season_info')]
    public function seasonInfo(SeriesRepository $seriesRepository, int $id, int $num, PaginatorInterface $paginator, Request $request, RatingRepository $ratingRepository): Response
    {
        $series = $seriesRepository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        $infoRating = $ratingRepository->getRatingUserConnectAndAllRatingComments($this->getUser(), $id);
        $episodes = $seriesRepository->seriesInfoByIdAndSeason($id, $num)->getSeasons()->get($num - 1)->getEpisodes();
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
        $userRating="";
        if (!empty($infoRating)) {
            foreach ($infoRating as $comment) {
                if($comment->getUser()==$this->getUser()){
                    $userRating=$comment;
                }
                $val = $val + $comment->getValue();
                ++$nombreNotes;
            }
            $val = substr($val / $nombreNotes, 0, 3);
        }
        $paginationComments = $paginator->paginate(
            $infoRating,
            'comments' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );

        $user = $this->getUser();
        $seriesView = $seriesRepository->seriesEpisodeCountView($user);

        $paginationComments->setParam('pageList', 'comments');

        return $this->render(
            'index/seriesInfo.html.twig',
            [
                'series' => $series,
                'paginationSeason' => $paginationSeason,
                'pagination' => $pagination,
                'userRating' => $userRating ? $userRating->getValue() : null,
                'userComment' => $userRating ? $userRating->getComment() : null,
                'paginationComments' => $paginationComments,
                'serieScore' => $val,
                'nombreNotes' => $nombreNotes,
                'seriesView' => $seriesView,
            ]
        );
    }

    #[Route('/series/{id}/season/{num}/add', name: 'app_index_season_info_add')]
    public function seasonAdd(SeriesRepository $seriesRepository, int $id, int $num, EntityManagerInterface $entityManager): Response
    {
        $seriesToAdd = $seriesRepository->find($id);
        $user = $this->getUser();
        $user->addSeries($seriesToAdd);
        $entityManager->flush();
        foreach ($seriesToAdd->getSeasons() as $season) {
            if ($season->getNumber() == $num) {
                foreach ($season->getEpisodes() as $episode) {
                    $this->getUser()->addEpisode($episode);
                    
                }
            }
        }
        $entityManager->flush();

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

#[Route('/series/{id}/season/{num}/remove', name: 'app_index_season_info_remove')]
public function seasonRemove(SeriesRepository $seriesRepository, int $id, int $num, EntityManagerInterface $entityManager): Response
{
    $seriesToRemove = $seriesRepository->find($id);
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
    $count = $seriesRepository->seriesEpisodeCountViewBySeries($user, $seriesToRemove);
    if ($count==[]) {
        $user->removeSeries($seriesToRemove);
    }
   

    $entityManager->flush();

    return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
}
}
