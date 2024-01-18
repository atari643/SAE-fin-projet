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
    public function seriesInfo(SeriesRepository $seriesRepository, RatingRepository $ratingRepository, EntityManagerInterface $entityManager, int $id, int $num, Request $request, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');

        // region Follow/Unfollow Series
        $user = $this->getUser();
        $infoRating = $ratingRepository->getRatingUserConnectAndAllRatingComments($this->getUser(), $id);
        if (null != $request->request->get('add')) {
            if ('true' == $request->request->get('add')) {
                $series = $entityManager
                    ->getRepository(Series::class);
                $seriesToAdd = $series->find($request->request->get('id'));

                $user->addSeries($seriesToAdd);
                $entityManager->persist($seriesToAdd);
                $entityManager->flush();
            } else {
                $series = $entityManager
                    ->getRepository(Series::class);
                $seriesToRemove = $series->find($request->request->get('id'));
                $user->removeSeries($seriesToRemove);
                $entityManager->flush();
            }
        }
        $userRating = null;
        foreach ($infoRating as $comment) {
            if ($comment->getUser() == $this->getUser()) {
                $userRating = $comment;
            }
        }
        // endregion

        if ($request->get('rating') && null != $this->getUser()) {
            if ('Delete' == $request->get('action') && null != $userRating) {
                $entityManager->remove($userRating);
                $entityManager->flush();

                return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            } elseif ('Edit' == $request->get('action')) {
                if ($userRating->getValue() == $request->get('value') && $userRating->getComment() == $request->get('comment')) {
                    return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
                }
                $entityManager->remove($userRating);
                $entityManager->flush();

                $this->addRatingIntoBase($entityManager, $id, $request);
            } elseif ('Send' == $request->get('action') && null == $userRating) {
                $this->addRatingIntoBase($entityManager, $id, $request);
            }

            return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            // end if
        }// end if

        // Different score (5 stars, 4...)
        $scoreSerie = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            'moy' => 0,
        ];

        $moy = 0;
        $nombreNotes = 0;
        $comments = $infoRating;
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $val = $comment->getValue();
                $nombreNotes = sizeof($comments);
                $scoreSerie[$val] = $scoreSerie[$val] + 1;
                $moy = $moy + $val;

                $scoreSerie['moy'] = substr($moy / $nombreNotes, 0, 3);
            }

            if (null != $filter) {
                $commentsChoisis = array_filter($comments, function ($comment) use ($filter) {
                    return $comment->getValue() == $filter;
                });

                $autresComments = array_filter($comments, function ($comment) use ($filter) {
                    return $comment->getValue() != $filter;
                });

                $comments = array_merge($commentsChoisis, $autresComments);
            } else {
                usort($comments, function ($a, $b) {
                    return $b->getDate() <=> $a->getDate();
                });
            }
        }

        $series = $seriesRepository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        $paginationSeason = $paginator->paginate(
            $seasons,
            'seasons' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $paginationSeason->setParam('pageList', 'seasons');

        $episodes = $seriesRepository->seriesInfoByIdAndSeason($id, $num)->getSeasons()->get($num - 1)->getEpisodes();
        $pagination = $paginator->paginate(
            $episodes,
            'episodes' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $pagination->setParam('pageList', 'episodes');

        $paginationComments = $paginator->paginate(
            $infoRating,
            'comments' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $paginationComments->setParam('pageList', 'comments');

        $user = $this->getUser();
        $seriesView = null;
        if (null != $user) {
            $user = $user->getId();
            $seriesView = $seriesRepository->seriesEpisodeCountView($user);
        }

        return $this->render(
            'index/seriesInfo.html.twig',
            [
            'series' => $series,
            'paginationSeason' => $paginationSeason,
            'pagination' => $pagination,
            'userRating' => $userRating ? $userRating->getValue() : null,
            'userComment' => $userRating ? $userRating->getComment() : null,
            'paginationComments' => $paginationComments,
            'serieScore' => $scoreSerie,
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
        if ([] == $count) {
            $user->removeSeries($seriesToRemove);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }
}
