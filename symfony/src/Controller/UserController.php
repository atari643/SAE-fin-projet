<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $usersRepository = $entityManager
            ->getRepository(User::class);
        $users = $usersRepository->findAll();

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

        $count = $usersRepository->createQueryBuilder('users')
            ->select('count(users.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'count' => $count,
        ]);
    }
}
