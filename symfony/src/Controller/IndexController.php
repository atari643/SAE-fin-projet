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
use Doctrine\ORM\Query\Expr\Join;
const SERIES_PER_PAGE = 10;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        session_start();
        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            $this->addFlash('success', 'Welcome back '.$user->getUsername());
            unset($_SESSION['user']);
        }
        $page = $request->query->get('page');
        if($page == null){
            return $this->redirect('?page=1');
        }

        $session = new Session();
        if($session->has('series')){
            $series = $session->get('series');
        }
        else{
            $series = $entityManager
                ->getRepository(Series::class)
                ->findAll();
            $session->set('series', $series);
        }
        $series100 = array_slice($series, 0, 100);
        $series_limit = array_slice($series, ($page-1)*SERIES_PER_PAGE, SERIES_PER_PAGE);
        $count = count($series);
        $numberOfPages = $count/SERIES_PER_PAGE;
        if($count % SERIES_PER_PAGE != 0){
            $numberOfPages += 1;
        }

        $series_infos = [];

        for($i=0; $i < sizeof($series_limit); $i++){
            $current_series = $series_limit[$i];
            $infos = [];
            $infos['id'] = $current_series->getId();
            $infos['title'] = $current_series->getTitle();
            $infos['plot'] = $current_series->getPlot();
            $infos['rating'] = $current_series->getImdb();
            $infos['youtubeTrailer'] = $current_series->getYoutubeTrailer();

            $qb = $entityManager->createQueryBuilder();
            $result = $qb
                ->select(['count(DISTINCT(season.number)) as s_count',
                        'count(episode.number) as e_count'])
                ->from('App:Series','series')
                ->innerJoin('App:Season', 'season',
                    Join::WITH, 'series = season.series')
                ->innerJoin('App:Episode', 'episode',
                    Join::WITH, 'season = episode.season')
                ->where('series.id = '.$infos['id'])
                ->getQuery()
                ->getSingleResult();

            $infos['episode_count'] = $result['e_count'];
            $infos['season_count'] = $result['s_count'];
            $series_infos[] = $infos;
            
        }
        //if justConnected 
        return $this->render('index/index.php.twig', [
            'seriesTotal'=>$series100,
            'series' => $series_infos,
            'numberOfPages' => $numberOfPages,
            'page' => $page,
            'justConnected' => true,
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
