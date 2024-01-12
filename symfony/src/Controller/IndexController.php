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
        $searchQuery     = $request->query->get('search', '');
        $searchGenre     = $request->query->get('genre', '');
        $searchYearStart = $request->query->get('yearStart', '');
        $searchYearEnd   = $request->query->get('yearEnd', '');
        $searchFollow    = $request->query->get('follow', '');
        $series_infos    = $repository->seriesInfo();
        if (null != $searchQuery) {
            $series_infos = $series_infos->where('s.title LIKE :query OR s.plot LIKE :query')->setParameter('query', '%' . $searchQuery . '%')->orderBy('CASE WHEN s.title LIKE :query THEN 1 ELSE 2 END')->setParameter('query', '%' . $searchQuery . '%');
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

        $genres       = $entityManager->getRepository(Genre::class)->findAll();
        $series_infos = $series_infos->getQuery();

        // region Follow/Unfollow Series
        if (null != $request->get('idToAdd') && null == $request->get('remove')) {
            $user = $this->getUser();

            $series      = $entityManager->getRepository(Series::class);
            $seriesToAdd = $series->findBy(['id' => $request->get('idToAdd')]);

            $user->addSeries($seriesToAdd[0]);
            $entityManager->persist($seriesToAdd[0]);
            $entityManager->flush();
        }

        if (null != $request->get('idToRemove') && null != $request->get('remove')) {
            $user = $this->getUser();

            $i              = 0;
            $end            = false;
            $seriesToRemove = null;
            while (!$end && $i < $user->getSeries()->count()) {
                if ($user->getSeries()[$i]->getId() == ((int) $request->get('idToRemove'))) {
                    $seriesToRemove = $user->getSeries()[$i];
                    $end            = true;
                }

                ++$i;
            }

            $user->removeSeries($seriesToRemove);
            $entityManager->flush();
        }

        // endregion
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            SERIES_PER_PAGE
        );

        return $this->render(
            'index/index.php.twig',
            [
                'pagination' => $pagination,
                'genres'     => $genres,
            ]
        );
    }//end index()


    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(SeriesRepository $repository, int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $userRating = $entityManager->getRepository(Rating::class)->findOneBy(
            [
                'user'   => $this->getUser(),
                'series' => $id,
            ]
        );

        if ('' != $request->get('rating') && null != $this->getUser()) {
            if ('Supprimer' == $request->get('action')) {
                $entityManager->remove($userRating);
                $entityManager->flush();

                return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            } else {
                if ('Modifier' == $request->get('action')) {
                    if ($userRating->getValue() == $request->get('value') && $userRating->getComment() == $request->get('comment')) {
                        return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
                    }

                    $entityManager->remove($userRating);
                    $entityManager->flush();
                }

                $series = $entityManager->find(Series::class, $id);

                $rating = new Rating();

                $rating->setValue($request->request->get('rating'));
                $rating->setComment($request->get('comment'));
                $rating->setDate(new \DateTime());

                $rating->setSeries($series);

                $rating->setUser($this->getUser());

                $entityManager->persist($rating);
                $entityManager->flush();

                return $this->redirectToRoute('app_index_series_info', ['id' => $id]);
            }//end if
        }//end if

        // Récupérez tous les commentaires pour la série
        $comments = $entityManager->getRepository(Rating::class)->findBy(
            ['series' => $id]
        );

        $series  = $repository->seriesInfoById($id);
        $seasons = $series->getSeasons();

        return $this->render(
            'index/seriesInfo.html.twig',
            [
                'series'      => $series,
                'seasons'     => $seasons,
                'episodes'    => null,
                'userRating'  => $userRating ? $userRating->getValue() : null,
                'userComment' => $userRating ? $userRating->getComment() : null,
                'comments'    => $comments,
            ]
        );
    }//end seriesInfo()


    #[Route('/series/{id}/{num}', name: 'app_index_season_info')]
    public function seasonInfo(SeriesRepository $repository, int $id, int $num): Response
    {
        $series   = $repository->seriesInfoById($id);
        $seasons  = $series->getSeasons();
        $episodes = $repository->seriesInfoByIdAndSeason($id, $num)->getSeasons()->get($num - 1)->getEpisodes();

        return $this->render(
            'index/seriesInfo.html.twig',
            [
                'series'   => $series,
                'seasons'  => $seasons,
                'episodes' => $episodes,
            ]
        );
    }//end seasonInfo()


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
    }//end showPoster()
}//end class
