<?php

namespace App\Controller;

use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $numberPerPage = 10;
        $page = $request->query->get('page');
        $series = $entityManager
            ->getRepository(Series::class);
        $series_limit = $series->findBy(array(), null, $numberPerPage, $numberPerPage*($page-1));
        $count = $series->createQueryBuilder('series')
        ->select('count(series.id)')
        ->getQuery()
        ->getSingleScalarResult();

        $numberOfPages = $count/$numberPerPage;
        if($count % $numberPerPage != 0){
            $numberOfPages += 1;
        }

        return $this->render('index/index.html.twig', [
            'series' => $series_limit,
            'numberOfPages' => $numberOfPages,
            'page' => $page,
        ]);
    }

    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(EntityManagerInterface $entityManager, int $id) : Response
    {
        $series_req = $entityManager
            ->getRepository(Series::class);
        $series_limit = $series_req->findBy(["id" => $id], null);
        
        foreach($series_limit as $serie){

            $response = new Response(
                'Content-Type',
                Response::HTTP_OK,
                ['content-type' => 'image/jpeg']
            );

            $response->setContent(stream_get_contents($serie->getPoster()));
            return $response;

        }
    }
}
