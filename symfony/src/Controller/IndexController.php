<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Entity\Genre;
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
    #[Route('/', name: 'app_default', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request,PaginatorInterface $paginator): Response
    {
        $searchQuery = $request->query->get('search', "");
        $searchGenre = $request->query->get('genre', "");
        $searchYearStart = $request->query->get('yearStart', "");
        $searchYearEnd = $request->query->get('yearEnd', "");
        $searchFollow = $request->query->get('follow', "");
        $series_infos = $entityManager->createQueryBuilder()
            ->select(
                's.id as id, s.title as title, s.poster, s.plot as plot, 
            COUNT(DISTINCT se.number) as season_count, COUNT(e.number) as episode_count'
            )
            ->from('App:Series', 's')
            ->leftJoin('App:Season', 'se', Join::WITH, 's = se.series')
            ->leftJoin('App:Episode', 'e', Join::WITH, 'se = e.season')
            ->leftJoin("s.genre", "genre", Join::WITH)
            ->leftJoin("s.user", "user", Join::WITH )
            ->groupBy('s.id');
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

        #region Follow/Unfollow Series
        $user = $this->getUser();
        if($request->request->get("add") != null){

            if($request->request->get("add") == "true"){

                $series = $entityManager
                ->getRepository(Series::class);
                $seriesToAdd = $series->find($request->request->get("id"));
    
                $user->addSeries($seriesToAdd[0]);
                $entityManager->persist($seriesToAdd[0]);
                $entityManager->flush();
    
            } else {
    
                $series = $entityManager
                ->getRepository(Series::class);
                $seriesToRemove = $series->find($request->request->get("id"));
                $user->removeSeries($seriesToRemove);
                $entityManager->flush();
    
            }

        }
        #endregion

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

    #[Route('/series/{id}', name: 'app_index_series_info', methods: ['GET', 'POST'])]
    public function seriesInfo(EntityManagerInterface $entityManager, Request $request, int $id): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->find($id);
        $seasons = $entityManager->getRepository(Season::class)->findBy(
            ['series' => $series],
            ['number' => 'ASC']
        );

        #region Follow/Unfollow Series
        dump($request->get("idToAdd"));
        dump($request->get("idToRemove"));
        dump($request->get("remove"));

        if($request->get("idToAdd") != null && $request->get("remove") == "1"){
            $user = $this->getUser();

            $series = $entityManager
            ->getRepository(Series::class);
            $seriesToAdd = $series->findBy(['id' => $request->get("idToAdd")]);

            $user->addSeries($seriesToAdd[0]);
            $entityManager->persist($seriesToAdd[0]);
            $entityManager->flush();
        }

        if($request->get("idToRemove") != null && $request->get("remove") == "1"){
            $user = $this->getUser();

            $i = 0;
            $end = false;
            $seriesToRemove = null;
            while(!$end && $i < $user->getSeries()->count()){
                if($user->getSeries()[$i]->getId() == ((int) $request->get("idToRemove"))){
                    $seriesToRemove = $user->getSeries()[$i];
                    $end = true;
                }
                $i += 1;
            }

            $user->removeSeries($seriesToRemove);
            $entityManager->flush();
        }
        #endregion

        return $this->render(
            'index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons,
            'episodes' => null
            ]
        );
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
