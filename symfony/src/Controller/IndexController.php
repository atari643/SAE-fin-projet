<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends MotherController
{
    #[Route('/', name: 'app_default', methods: ['GET', 'POST'])]
    public function index(SeriesRepository $seriesRepository, Request $request, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        if (null == $request->query->get('page') and !$request->isMethod('POST')) {
            return $this->redirectToRoute('app_default', [
                'page' => 1,
            ]);
        }

        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }
        if (!isset($_SESSION['seed'])) {
            $_SESSION['seed'] = rand();
        }

        $searchQuery = $request->query->get('search', '');
        $searchGenre = $request->query->get('genre', '');
        $searchYearStart = $request->query->get('yearStart', '');
        $searchYearEnd = $request->query->get('yearEnd', '');
        $searchFollow = $request->query->get('follow', '');
        $searchRating = $request->query->get('rating', '');

        
        $series_infos = $seriesRepository->seriesInfo();
        if(null != $searchRating){
            $series_infos = $series_infos->having('ROUND(AVG(rating.value)) = :rating')->setParameter('rating', $searchRating);
        }
        if (null != $searchQuery) {
            $series_infos = $series_infos->where('s.title LIKE :query OR s.plot LIKE :query')->setParameter('query', '%'.$searchQuery.'%')->orderBy('CASE WHEN s.title LIKE :query THEN 1 ELSE 2 END')->setParameter('query', '%'.$searchQuery.'%');
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
        $genres = $entityManager->getRepository(Genre::class)->findAll();
        $series_infos = $series_infos->orderBy('RAND(:seed)')->setParameter('seed', $_SESSION['seed'])->getQuery()->getResult();
        // region Follow/Unfollow Series
        $user = $this->getUser();
        if (null != $request->request->get('add')) {
            $seriesToModify = $seriesRepository->seriesInfoById($request->request->get('id'));
            if ('true' == $request->request->get('add')) {
                $user->addSeries($seriesToModify);
                $entityManager->persist($seriesToModify);
                
            } else {
                
                $user->removeSeries($seriesToModify);
                $entityManager->persist($seriesToModify);
            }
        }
        $entityManager->flush();
        // endregion
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            ITEMS_PER_PAGE
        );

        if (!isset($_SESSION['hasVisited'])) {
            if (isset($_COOKIE['visited'])) {
                $_SESSION['hasVisited'] = 'yes';
            } else {
                $_SESSION['hasVisited'] = null;
            }
        }

        return $this->render(
            'index/index.php.twig',
            [
                'pagination' => $pagination,
                'genres' => $genres,
                'hasVisited' => $_SESSION['hasVisited'],
            ]
        );
    }// end index()
}
