<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
const USERS_PER_PAGE = 10;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        if($page == null){

            return $this->redirect('?page=1');

        }

        $usersRepository = $entityManager
            ->getRepository(User::class);

        $role = $request->get('role');
        $id = $request->get('id');

        if($id != null){
            $user = $usersRepository->find($id);

            switch($role){
                case 0:
                    $user->setAdmin(false);
                    break;
                case 1:
                    $user->setAdmin(true);
                    break;
            }
            $entityManager->flush();
        }

        $users = $entityManager
            ->getRepository(User::class);
        $users_limit = $users->findBy(array(), null, USERS_PER_PAGE, USERS_PER_PAGE*($page-1));


        /* $searchQuery = $request->query->get('search', "");
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
        $pagination = $paginator->paginate(
            $series_infos,
            $request->query->getInt('page', 1),
            SERIES_PER_PAGE
        );
        
        return $this->render(
            'index/index.php.twig', [
            'pagination' => $pagination,
            'genres' => $genres,

            ]); */
        ##########################################
        /* $searchQuery = $request->query->get('search', "");
        $searchGenre = $request->query->get('genre', "");
        $series_infos = $entityManager->createQueryBuilder()
            ->select(
                's.id as id, s.title as title, s.poster, s.plot as plot, 
            COUNT(DISTINCT se.number) as season_count, COUNT(e.number) as episode_count'
            )
            ->from('App:Series', 's')
            ->leftJoin('App:Season', 'se', Join::WITH, 's = se.series')
            ->leftJoin('App:Episode', 'e', Join::WITH, 'se = e.season')
            ->leftJoin("s.genre", "genre", Join::WITH)
        
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
        $genres = $entityManager->getRepository(Genre::class)->findAll();
        $series_infos = $series_infos->getQuery(); */
        ##########################################
        $searchQuery2 = $request->query->get('search', "");


        ###
        $count = $usersRepository->createQueryBuilder('users')
            ->select('count(users.id)')
            ->getQuery()
            ->getSingleScalarResult();
        ###########################################
        $user_specific = $entityManager->createQueryBuilder()
        ->select(
            'u.id as id, u.name as name'
        )
        ->from('App:User', 'u');
        if($searchQuery2!=null) {
            $user_specific = $user_specific->where('u.name LIKE :query')
                ->setParameter('query', '%'.$searchQuery2.'%')
                ->orderBy('CASE WHEN u.name LIKE :query THEN 1 ELSE 2 END')
                ->setParameter('query', '%'.$searchQuery2.'%');
        }
        $user_specific = $user_specific->getQuery();
        ###
        $pagination = $paginator->paginate(
            $users_limit,
            $request->query->getInt('page', 1),
            USERS_PER_PAGE
        );

        return $this->render('user/index.html.twig', [
            'pagination' => $pagination,
            'count' => $count,
        ]);
    }

    #[Route('/user/series', name: 'series_followed', methods: ['GET', 'POST'])]
    public function seriesFollowed(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        
        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('user/series_followed.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/user/series/{username}', name: 'series_followed_search_user', methods: ['GET', 'POST'])]
    public function seriesFollowedByUser(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $users = $entityManager
        ->getRepository(User::class);
        $user = $users->findOneBy(array('name' => $username));
        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('user/series_followed.html.twig', [
            'pagination' => $pagination
        ]);
    }
    
    #[Route('/user/profile', name: 'user_profile', methods: ['GET', 'POST'])]
    public function userProfile(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if($user == null){

            $login = $this->generateUrl('app_login');
            return $this->redirect($login);

        }
        $name = $user->getName();

        return $this->render('user/profile.html.twig', [
            'user' => $name,
        ]);
    }

    #[Route('/user/profile/{username}', name: 'user_profile_search', methods: ['GET', 'POST'])]
    public function userProfileSearch(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $user = $this->getUser();
        if($user == null){

            $login = $this->generateUrl('app_login');
            return $this->redirect($login);
        }
        return $this->render('user/profile.html.twig', [
            'user' => $username,
        ]);
    }


    

}
