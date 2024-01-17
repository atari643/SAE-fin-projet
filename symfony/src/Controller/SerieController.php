<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\Series;
use App\Repository\RatingRepository;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SerieController extends MotherController
{

    #[Route('/series/search', name: 'series_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $searchQuery = $request->query->get('search');
        $searchGenre = $request->query->get('genre');
        $searchYearStart = $request->query->get('yearStart');
        $searchYearEnd = $request->query->get('yearEnd');
        $searchFollow = $request->query->get('follow');

        $args = ['page' => 1];

        if (null != $searchQuery) {
            $args['search'] = $searchQuery;
        }
        if (null != $searchGenre) {
            $args['genre'] = $searchGenre;
        }
        if (null != $searchYearStart) {
            $args['yearStart'] = $searchYearStart;
        }
        if (null != $searchYearEnd) {
            $args['yearEnd'] = $searchYearEnd;
        }
        if (null != $searchFollow) {
            $args['follow'] = $searchFollow;
        }

        return $this->redirectToRoute('app_default', $args);
    }

    #[Route('/series/{id}/sort/{stars}', name: 'series_review_filter')]
    public function reviewFilter(Request $request, int $id, int $stars): Response
    {
        $args = ['id' => $id, 'filter' => $stars];

        return $this->redirectToRoute('app_index_series_info', $args);
    }

    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(SeriesRepository $seriesRepository,RatingRepository $ratingRepository, EntityManagerInterface $entityManager, int $id, Request $request, PaginatorInterface $paginator): Response
    {
        $filter = $request->query->get('filter');
        $infoRating = $this->getRatings($entityManager, $id);

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
        $scoreSerie = array(
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            "moy" => 0,
        );

        $moy = 0;
        $nombreNotes = 0;
        $comments = $infoRating['comments'];
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $val = $comment->getValue();
                $scoreSerie[$val] = $scoreSerie[$val] + 1;
                $moy = $moy + $val;

                $scoreSerie['moy'] = substr($moy / $nombreNotes, 0, 3);;
            }

            if ($filter != null) {

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

            $series = $seriesRepository->seriesInfoById($id);
            $seasons = $series->getSeasons();
            $paginationSeason = $paginator->paginate(
                $seasons,
                'seasons' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
                ITEMS_PER_PAGE
            );
            $paginationSeason->setParam('pageList', 'seasons');

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
                'index/seriesInfo.html.twig', [
                'series' => $series,
                'paginationSeason' => $paginationSeason,
                'pagination' => null,
                'userRating' => $userRating ? $userRating->getValue() : null,
                'userComment' => $userRating ? $userRating->getComment() : null,
                'paginationComments' => $paginationComments,
                'serieScore' => $scoreSerie,
                'nombreNotes' => $nombreNotes,
                'seriesView' => $seriesView,
            ]);
        }

        return $this->render(
            'index/seriesInfo.html.twig', [
            'series' => $series,
            'paginationSeason' => null,
            'pagination' => null,
            'userRating' => $userRating ? $userRating->getValue() : null,
            'userComment' => $userRating ? $userRating->getComment() : null,
            'paginationComments' => null,
            'serieScore' => $scoreSerie,
            'nombreNotes' => $nombreNotes,
            'seriesView' => null,
        ]);
    }

    #[Route('/series/{id}/add', name: 'app_index_series_add')]
    public function serieAdd(SeriesRepository $repository, int $id, EntityManagerInterface $entityManager): Response
    {
        $seriesToAdd = $repository->seriesInfoById($id);
        $seasons = $seriesToAdd->getSeasons();
        $user = $this->getUser();
        $user->addSeries($seriesToAdd);
        $entityManager->flush();
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $this->getUser()->addEpisode($episode);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    // end seasonInfo()
    #[Route('/series/{id}/remove', name: 'app_index_series_remove')]
    public function serieRemove(SeriesRepository $seriesRepository, int $id, EntityManagerInterface $entityManager): Response
    {
        $seriesToRemove = $seriesRepository->seriesInfoById($id);
        $seasons = $seriesToRemove->getSeasons();
        $user = $this->getUser();
        $user->removeSeries($seriesToRemove);
        $entityManager->flush();
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $this->getUser()->removeEpisode($episode);
                
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    private function addRatingIntoBase(EntityManagerInterface $entityManager, int $id, Request $request)
    {
        $series = $entityManager->find(Series::class, $id);
        $rating = new Rating();
        $rating->setValue($request->request->get('rating'));
        $rating->setComment($request->get('comment'));
        $rating->setDate(new \DateTime());
        $rating->setSeries($series);
        $rating->setUser($this->getUser());
        $entityManager->persist($rating);
        $entityManager->flush();
    }
}
