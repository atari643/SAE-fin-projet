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

const SERIES_PER_PAGE = 10;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET', 'POST'])]
    public function index(SeriesRepository $repository, Request $request, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }
        if (!isset($_SESSION['seed'])) {
            $_SESSION['seed'] = rand();
        }
        $searchQuery = $request->query->get('search', '');
        $searchGenre = $request->query->get('genre', '');
        $searchYearStart = $request->query->get('yearStart', '');
        $searchYearEnd = $request->query->get('yearEnd', '');
        $searchFollow = $request->query->get('follow', '');
        if (null == $request->query->get('page') and !$request->isMethod('POST')) {
            return $this->redirectToRoute('app_default', [
                'page' => 1,
            ]);
        }
        $series_infos = $repository->seriesInfo($_SESSION['seed']);
        if (null != $searchQuery) {
            $series_infos = $series_infos->where('s.title LIKE :query OR s.plot LIKE :query')->setParameter('query', '%'.$searchQuery.'%')->orderBy('CASE WHEN s.title LIKE :query THEN 1 ELSE 2 END')->setParameter('query', '%'.$searchQuery.'%');
        }

        if (null != $searchGenre) {
            $series_infos = $series_infos->andWhere('genre.id = :genre')->setParameter('genre', $searchGenre);
        }

        if (null != $searchYearStart) {
            $series_infos = $series_infos->andWhere('s.yearStart >= :yearStart')->setParameter('yearStart', $searchYearStart);
        }

        if (null != $searchYearEnd) {
            $series_infos = $series_infos->andWhere('s.yearEnd <= :yearEnd')->setParameter('yearEnd', $searchYearEnd);
        }

        if (null != $this->getUser() && 0 != $searchFollow && null != $searchFollow) {
            $series_infos = $series_infos->andWhere('user.id = :user')->setParameter('user', $this->getUser()->getId());
        }

        if (null != $searchFollow && 0 == $searchFollow) {
            $series_infos = $series_infos->andWhere('user.id IS NULL');
        }
        $genres = $entityManager->getRepository(Genre::class)->findAll();
        $series_infos = $series_infos->getQuery();
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
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            SERIES_PER_PAGE
        );

        if (!isset($_SESSION['hasVisited'])) {
            if (isset($_COOKIE['visited'])) {
                $_SESSION['hasVisited'] = 'yes';
            } else {
                $_SESSION['hasVisited'] = null;
            }
        }

        return $this->render(
            'index/index.php.twig',
            [
                'pagination' => $pagination,
                'genres' => $genres,
                'hasVisited' => $_SESSION['hasVisited'],
            ]
        );
    }// end index()

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
            if ('Delete' == $request->get('action')) {
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
            $request->query->get('pageList') === 'seasons' ? $request->query->getInt('page', 1) : 1,
            SERIES_PER_PAGE
        );
        $paginationSeason->setParam('pageList', 'seasons');
    
        $paginationComments = $paginator->paginate(
            $comments,
            $request->query->get('pageList') === 'comments' ? $request->query->getInt('page', 1) : 1,
            SERIES_PER_PAGE
        );
        $paginationComments->setParam('pageList', 'comments');
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
        ]);
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
            $request->query->get('pageList') === 'episodes' ? $request->query->getInt('page', 1) : 1,
            SERIES_PER_PAGE
        );
        $pagination->setParam('pageList', 'episodes');

        $paginationSeason = $paginator->paginate(
            $seasons,
            $request->query->get('pageList') === 'seasons' ? $request->query->getInt('page', 1) : 1,
            SERIES_PER_PAGE
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
            $request->query->get('pageList') === 'comments' ? $request->query->getInt('page', 1) : 1,
            SERIES_PER_PAGE
        );
        $paginationComments->setParam('pageList', 'comments');
        return $this->render(
            'index/seriesInfo.html.twig', [
                'series' => $series,
                'paginationSeason' => $paginationSeason,
                'pagination' => $pagination,
                'userRating' => $infoRating['userRating'] ? $infoRating['userValue'] : null,
                'userComment' => $infoRating['userRating'] ? $infoRating['userComment'] : null,
                'paginationComments' => $paginationComments,
                'serieScore' => $val,
                'nombreNotes' => $nombreNotes,
            ]
        );
    }

    // end seasonInfo()
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

    // end seasonInfo()
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

    // end seasonInfo()
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

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    // end seasonInfo()
    #[Route('/series/{id}/season/{num}/remove', name: 'app_index_season_info_remove')]
    public function seasonRemove(SeriesRepository $repository, int $id, int $num, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
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
            if ($season->getNumber() == $num) {
                foreach ($season->getEpisodes() as $episode) {
                    $this->getUser()->removeEpisode($episode);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
    }

    // end seasonInfo()
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

    // end seasonInfo()
    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(EntityManagerInterface $entityManager, int $id): ?Response
    {
        $series = $entityManager->find(Series::class, $id);
        header('Content-Type: image/jpeg');
        $response = new Response(
            'Content-Type',
            Response::HTTP_OK,
            ['content-type' => 'image/jpeg']
        );
        $response->setContent(stream_get_contents($series->getPoster()));

        return $response;
    }// end showPoster()
}// end class
