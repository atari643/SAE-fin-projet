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
use App\Entity\User;

const SERIES_PER_PAGE = 10;

class IndexController extends AbstractController
{

    protected function getUser(): ?User
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        return $token->getUser();
    }

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

        $numberOfPages = intdiv($count, SERIES_PER_PAGE);
        if($count % SERIES_PER_PAGE != 0){
            $numberOfPages += 1;
        }


        //if user is logged in : do below and uncomment. If not, do below and remove the comment
        //$tokenInterface = $this->container->has('security.token_storage');//->getToken();
        //$securityContext = $this->container->get('security.authorization_checker');
        //if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
        /** @var User $user */
        $user = $this->getUser();
        /*if ($user->isAdmin()){
            return $this->render('index/index.html.twig', [
                'series' => $series_limit,
                'numberOfPages' => $numberOfPages,
                'page' => $page,
                'admin' => 1,
            ]);
        }
        else{
            return $this->render('index/index.html.twig', [
                'series' => $series_limit,
                'numberOfPages' => $numberOfPages,
                'page' => $page,
                'admin' => 0,
            ]);
        }*/
        
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
            ['series' => $series],
            ['number' => 'ASC']
        );

        $follow = false;
        $user = $this->getUser();

        $i = 0;
        $end = false;
        $seriesToFind = null;
        while(!$end && $i < $user->getSeries()->count()){
            if($user->getSeries()[$i]->getId() == $id){
                $seriesToFind = $user->getSeries()[$i];
                $end = true;
            }
            $i++;
        }

        if($seriesToFind != null){
            $follow = true;
        }

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
        $response = new Response(
            'Content-Type',
            Response::HTTP_OK,
            ['content-type' => 'image/jpeg']
        );
        $response->setContent(stream_get_contents($series->getPoster()));
        return $response;
    }

}
