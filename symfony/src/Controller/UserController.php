<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

const USERS_PER_PAGE = 10;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $page     = $request->query->get('page');
        $get_args = $request->query->all();
        // not here box
        if (null == $page) {
            $get_string = '?';
            foreach (array_keys($get_args) as $key) {
                $arg         = $get_args[$key];
                $get_string .= $key . '=' . $arg . '&';
            }

            $get_string .= 'page=1';

            return $this->redirect($get_string);
        }

        // not here box
        $usersRepository = $entityManager->getRepository(User::class);
        $role            = $request->get('role');
        $id              = $request->get('id');

        if (null != $id) {
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

        $users       = $entityManager->getRepository(User::class);
        $users_limit = $users->findBy([], null, USERS_PER_PAGE, (USERS_PER_PAGE * ($page - 1)));

        $count = $usersRepository->createQueryBuilder('users')->select('count(users.id)')->getQuery()->getSingleScalarResult();

        $search_query = $request->query->get('search');
        $user_specific = $entityManager->createQueryBuilder()
        ->select(
            'u.id as id, u.name as name, u.registerDate as registerDate, u.admin'
        )
        ->from('App:User', 'u');
        if(!empty($search_query)) {
            $user_specific = $user_specific->where('u.name LIKE :search')
                ->setParameter('search', "$search_query%");
                $user_specific = $user_specific->getQuery();
                $pagination = $paginator->paginate(
                    $user_specific,
                    $request->query->getInt('page', 1),
                    USERS_PER_PAGE
                );
                
                
                return $this->render('user/index.html.twig', [
                    //'form'=>$form->createView(),
                    'pagination' => $pagination,
                    'count' => $count,
                    'username' => $search_query,
                ]);
        }

        dump($request->query->all());
        
        $pagination = $paginator->paginate(
            $users_limit,
            $request->query->getInt('page', 1),
            USERS_PER_PAGE
        );

        return $this->render(
            'user/index.html.twig',
            [
            // 'form'=>$form->createView(),
                'pagination' => $pagination,
                'count'      => $count,
                'username'   => '',
            ]
        );
    }//end index()


    #[Route('/user/series', name: 'series_followed', methods: ['GET', 'POST'])]
    public function seriesFollowed(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();

        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/series_followed.html.twig',
            ['pagination' => $pagination]
        );
    }//end seriesFollowed()


    #[Route('/user/series/{username}', name: 'series_followed_search_user', methods: ['GET', 'POST'])]
    public function seriesFollowedByUser(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $users      = $entityManager->getRepository(User::class);
        $user       = $users->findOneBy(['name' => $username]);
        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/series_followed.html.twig',
            ['pagination' => $pagination]
        );
    }//end seriesFollowedByUser()


    #[Route('/user/profile', name: 'user_profile', methods: ['GET', 'POST'])]
    public function userProfile(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }

        $name = $user->getName();

        return $this->render(
            'user/profile.html.twig',
            ['user' => $name]
        );
    }//end userProfile()


    #[Route('/user/profile/{username}', name: 'user_profile_search', methods: ['GET', 'POST'])]
    public function userProfileSearch(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }

        return $this->render(
            'user/profile.html.twig',
            ['user' => $username]
        );
    }//end userProfileSearch()
}//end class
