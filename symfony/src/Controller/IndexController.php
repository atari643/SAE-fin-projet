<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Genre;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Knp\Component\Pager\PaginatorInterface;
use DoctrineExtensions\Query\Mysql;
use Doctrine\ORM\Query\Expr\Join;
const SERIES_PER_PAGE = 10;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(SeriesRepository $repository, Request $request,PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        $searchQuery = $request->query->get('search', "");
        $searchGenre = $request->query->get('genre', "");
        $searchYearStart = $request->query->get('yearStart', "");
        $searchYearEnd = $request->query->get('yearEnd', "");
        $searchFollow = $request->query->get('follow', "");
        $series_infos = $repository->seriesInfo();
        if($searchQuery!=null) {
            $series_infos = $series_infos->where('s.title LIKE :query OR s.plot LIKE :query')
                ->setParameter('query', '%'.$searchQuery.'%')
                ->orderBy('CASE WHEN s.title LIKE :query THEN 1 ELSE 2 END')
                ->setParameter('query', '%'.$searchQuery.'%');
        }
        if($searchGenre!=null) {
            $series_infos = $series_infos->andWhere('genre.id = :genre')
                ->setParameter('genre', $searchGenre);
        }
        if($searchYearStart!=null) {
            $series_infos = $series_infos->andWhere('s.yearStart >= :yearStart')
                ->setParameter('yearStart', $searchYearStart);
        }
        if($searchYearEnd!=null) {
            $series_infos = $series_infos->andWhere('s.yearEnd <= :yearEnd')
                ->setParameter('yearEnd', $searchYearEnd);
        }
        if($this->getUser()!=null && $searchFollow!=0 && $searchFollow!=null) {
            $series_infos = $series_infos->andWhere('user.id = :user')
                ->setParameter('user', $this->getUser()->getId());
        }
        if($searchFollow!=null && $searchFollow==0) {
            $series_infos = $series_infos->andWhere('user.id IS NULL');
        }
        $genres = $entityManager->getRepository(Genre::class)->findAll();
        $series_infos = $series_infos->getQuery();
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            SERIES_PER_PAGE
        );
        
        return $this->render(
            'index/index.php.twig', [
            'pagination' => $pagination,
            'genres' => $genres,

            ]);
    }

    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(SeriesRepository $repository, int $id): Response
    {
        $series = $repository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        return $this->render(
            'index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'episodes' => null
            ]
        );
    }
    #[Route('/series/{id}/{num}', name: 'app_index_season_info')]
    public function seasonInfo(SeriesRepository $repository, int $id, int $num): Response
    {
        $series = $repository->seriesInfoById($id);
        $seasons = $series->getSeasons();
        $episodes = $repository->seriesInfoByIdAndSeason($id, $num)->getSeasons()->get($num-1)->getEpisodes();
        return $this->render(
            'index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'episodes' => $episodes
            ]
        );
    }
    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(EntityManagerInterface $entityManager, int $id) : ?Response
    {
        $series = $entityManager
            ->find(Series::class, $id);
        header('Content-Type: image/jpeg');
        $response = new Response(
            'Content-Type', Response::HTTP_OK, ['content-type' => 'image/jpeg']
        );
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }

}
