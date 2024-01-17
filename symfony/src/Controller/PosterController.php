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

class PosterController extends MotherController
{
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
