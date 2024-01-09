<?php

namespace App\Controller;

use App\Entity\Season;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

const SERIES_PER_PAGE = 10;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $page = $request->query->get('page');
        if($page == null){
            return $this->redirect('?page=1');
        }

        $series = $entityManager
            ->getRepository(Series::class);
        $series_limit = $series->findBy(array(), null, SERIES_PER_PAGE, SERIES_PER_PAGE*($page-1));
        $count = $series->createQueryBuilder('series')
        ->select('count(series.id)')
        ->getQuery()
        ->getSingleScalarResult();

        $numberOfPages = $count/SERIES_PER_PAGE;
        if($count % SERIES_PER_PAGE != 0){
            $numberOfPages += 1;
        }

        return $this->render('index/index.php.twig', [
            'series' => $series_limit,
            'numberOfPages' => $numberOfPages,
            'page' => $page,
        ]);
    }

    #[Route('/series/{id}', name: 'app_index_series_info')]
    public function seriesInfo(EntityManagerInterface $entityManager, int $id): Response
    {
        $series = $entityManager
            ->getRepository(Series::class)
            ->find($id);
        $seasons = $entityManager->getRepository(Season::class)->findBy(
            ['series' => $series]
        );
        return $this->render('index/seriesInfo.html.twig', [
            'series' => $series,
            'seasons' => $seasons
        ]);
    }

    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(EntityManagerInterface $entityManager, int $id) : ?Response
    {
        $series = $entityManager
            ->find(Series::class, $id);
        header('Content-Type: image/jpeg');
        $response = new Response(Response::HTTP_OK);
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }

}
