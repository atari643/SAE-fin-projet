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
        $count = $usersRepository->createQueryBuilder('users')
            ->select('count(users.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
        $name = $user->getName();

        return $this->render('user/profile.html.twig', [
            'user' => $name,
        ]);
    }

    #[Route('/user/profile/{username}', name: 'user_profile_search', methods: ['GET', 'POST'])]
    public function userProfileSearch(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $username,
        ]);
    }


    

}
