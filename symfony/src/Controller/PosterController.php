<?php

namespace App\Controller;

use App\Repository\SeriesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PosterController extends MotherController
{
    #[Route('/poster/{id}', name: 'app_series_poster')]
    public function showPoster(SeriesRepository $seriesRepository, int $id): ?Response
    {
        $series = $seriesRepository->seriesInfoById($id);
        header('Content-Type: image/jpeg');
        $response = new Response(
            'Content-Type',
            Response::HTTP_OK,
            ['content-type' => 'image/jpeg']
        );
        $response->setContent(stream_get_contents($series->getPoster()));

        return $response;
    } // end showPoster()
}// end class
