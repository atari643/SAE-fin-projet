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

}
