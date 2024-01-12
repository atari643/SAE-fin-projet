<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
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
    public function index(EntityManagerInterface $entityManager, Request $request,PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        if($page == null){
            return $this->redirect('?page=1');
        }
        $series_infos = $entityManager->createQueryBuilder()
            ->select('s.id as id, s.title as title, s.poster, s.plot as plot, COUNT(DISTINCT se.number) as season_count, COUNT(e.number) as episode_count')
            ->from('App:Series', 's')
            ->leftJoin('App:Season', 'se', Join::WITH, 's = se.series')
            ->leftJoin('App:Episode', 'e', Join::WITH, 'se = e.season')
            ->groupBy('s.id')
            ->getQuery();
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            SERIES_PER_PAGE
        );
        return $this->render('index/index.php.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(EntityManagerInterface $entityManager, int $id): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->find($id);
        $seasons = $entityManager->getRepository(Season::class)->findBy(
            ['series' => $series],
            ['number' => 'ASC']
        );
        return $this->render('index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'episodes' => null
        ]);
    }
    #[Route('/series/{id}/{num}', name: 'app_index_season_info')]
    public function seasonInfo(EntityManagerInterface $entityManager, int $id, int $num): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->find($id);
        $seasons = $entityManager->getRepository(Season::class)->findBy(
            ['series' => $series],
            ['number' => 'ASC']
            
        );
        $episodes = $entityManager->getRepository(Episode::class)->findBy(
            ['season' => $seasons[$num-1]],
            ['number' => 'ASC']
        );
        
        return $this->render('index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'episodes' => $episodes
        ]);
    }

    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(EntityManagerInterface $entityManager, int $id) : ?Response
    {
        $series = $entityManager
            ->find(Series::class, $id);
        header('Content-Type: image/jpeg');
        $response = new Response(
            'Content-Type', Response::HTTP_OK, ['content-type' => 'image/jpeg']);
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }

}
