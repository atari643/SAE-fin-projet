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

class SerieController extends MotherController
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

    
    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(SeriesRepository $repository, EntityManagerInterface $entityManager, int $id, Request $request, PaginatorInterface $paginator): Response
    {
        $infoRating = $this->getRatings($entityManager, $id);

        // region Follow/Unfollow Series
        $user = $this->getUser();
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

        // endregion

        if ($request->get('rating') && null != $this->getUser()) {
            if ('Delete' == $request->get('action') && null != $infoRating['userRating']) {
                $entityManager->remove($infoRating['userRating']);
                $entityManager->flush();

                return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            } elseif ('Edit' == $request->get('action')) {
                if ($infoRating['userValue'] == $request->get('value') && $infoRating['userComment'] == $request->get('comment')) {
                    return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
                }
                $entityManager->remove($infoRating['userRating']);
                $entityManager->flush();

                $entityManager->remove($infoRating['userRating']);
                $entityManager->flush();
                $this->addRatingIntoBase($entityManager, $id, $request);
            } elseif ('Send' == $request->get('action') && null == $infoRating['userRating']) {
                $this->addRatingIntoBase($entityManager, $id, $request);
            }

            return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            // end if
        }// end if

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

        $series = $repository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        $paginationSeason = $paginator->paginate(
            $seasons,
            'seasons' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $paginationSeason->setParam('pageList', 'seasons');

        $paginationComments = $paginator->paginate(
            $comments,
            'comments' === $request->query->get('pageList') ? $request->query->getInt('page', 1) : 1,
            ITEMS_PER_PAGE
        );
        $paginationComments->setParam('pageList', 'comments');

        $user = $this->getUser();
        $seriesView = null;
        if (null != $user) {
            $user = $user->getId();
            $seriesView = $repository->seriesEpisodeCountView($user);
        }

        return $this->render(
            'index/seriesInfo.html.twig', [
            'series' => $series,
            'paginationSeason' => $paginationSeason,
            'pagination' => null,
            'userRating' => $infoRating['userRating'] ? $infoRating['userValue'] : null,
            'userComment' => $infoRating['userRating'] ? $infoRating['userComment'] : null,
            'paginationComments' => $paginationComments,
            'serieScore' => $val,
            'nombreNotes' => $nombreNotes,
            'seriesView' => $seriesView,
        ]);
    }
    #[Route('/series/{id}/add', name: 'app_index_series_add')]
    public function serieAdd(SeriesRepository $repository, int $id, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
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
                $this->getUser()->addEpisode($episode);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    // end seasonInfo()
    #[Route('/series/{id}/remove', name: 'app_index_series_remove')]
    public function serieRemove(SeriesRepository $repository, int $id, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $series = $entityManager
        ->getRepository(Series::class);
        $seriesToRemove = $series->findBy(['id' => $id]);
        $seasons = $seriesToRemove[0]->getSeasons();
        $user = $this->getUser();
        $user->removeSeries($seriesToRemove[0]);
        $entityManager->flush();
        foreach ($seasons as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $this->getUser()->removeEpisode($episode);
                $entityManager->flush();
            }
        }

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
