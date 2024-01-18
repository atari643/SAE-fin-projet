<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\User;
use App\Form\EditUserAdminType;
use App\Form\EditUserType;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

const USERS_PER_PAGE = 10;

class UserController extends MotherController
{
    #[Route('/users', name: 'app_users', methods: ['GET', 'POST'])]
    public function index(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $page = $request->query->get('page');
        $get_args = $request->query->all();

        if (null == $page) {
            $get_string = '?';
            foreach (array_keys($get_args) as $key) {
                $arg = $get_args[$key];
                $get_string .= $key.'='.$arg.'&';
            }

            $get_string .= 'page=1';

            return $this->redirect($get_string);
        }

        // not here box
        $usersRepository = $entityManager->getRepository(User::class);
        $role = $request->get('role');
        $id = $request->get('id');

        if (null != $id) {
            $user = $usersRepository->find($id);
            switch ($role) {
                case 0:
                    $user->setAdmin(false);
                    break;

                case 1:
                    $user->setAdmin(true);
                    break;
            }

            $entityManager->flush();
        }

        if (null != $this->getUser()) {
            $userAdminOrNot = $this->getUser()->isAdmin();
        } else {
            return $this->redirect($this->generateUrl('app_login')); // si accessible alors : $userAdminOrNot=false;
        }
        $users = $entityManager->getRepository(User::class)->findAll();

        $count = $usersRepository->createQueryBuilder('users')->select('count(users.id)')->getQuery()->getSingleScalarResult();

        $search_query = $request->query->get('search');
        $user_specific = $entityManager->createQueryBuilder()
        ->select(
            'u.id as id, u.name as name, u.registerDate as registerDate, u.admin, u.fake, u.email'
        )
        ->from('App:User', 'u');
        if (!empty($search_query)) {
            $user_specific = $user_specific->where('u.name LIKE :search')
                ->setParameter('search', "$search_query%");
            $user_specific = $user_specific->getQuery();
            $pagination = $paginator->paginate(
                $user_specific,
                $request->query->getInt('page', 1),
                USERS_PER_PAGE
            );
            if ($userAdminOrNot) {
                return $this->render('user/index.html.twig', [
                    'pagination' => $pagination,
                    'count' => $count,
                    'username' => $search_query,
                ]);
            } else {
                return $this->render('user/index_user.html.twig', [
                    'pagination' => $pagination,
                    'count' => $count,
                    'username' => $search_query,
                ]);
            }
        }
        $pagination = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            USERS_PER_PAGE
        );
        if ($userAdminOrNot) {
            return $this->render('user/index.html.twig', [
                'pagination' => $pagination,
                'count' => $count,
                'username' => '',
            ]);
        } else {
            return $this->render('user/index_user.html.twig', [
            'pagination' => $pagination,
            'count' => $count,
            'username' => '',
            ]);
        }
    }

    #[Route('/user/series', name: 'series_followed', methods: ['GET', 'POST'])]
    public function seriesFollowed(SeriesRepository $repository, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser()->getId();
        $series = $repository->seriesEpisodesCount($user);
        $pagination = $paginator->paginate(
            $series,
            $request->query->getInt('page', 1),
            10
        );

        $seriesView = $repository->seriesEpisodeCountView($user);

        return $this->render(
            'user/series_followed.html.twig',
            ['pagination' => $pagination,
             'seriesView' => $seriesView]
        );
    }// end seriesFollowed()

    #[Route('/user/series/{username}', name: 'series_followed_search_user', methods: ['GET', 'POST'])]
    public function seriesFollowedByUser(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $users = $entityManager->getRepository(User::class);
        $user = $users->findOneBy(['name' => $username]);
        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/series_followed.html.twig',
            ['pagination' => $pagination]
        );
    }// end seriesFollowedByUser()

    #[Route('/user/profile', name: 'user_profile', methods: ['GET', 'POST'])]
    public function userProfile(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        // check if connected
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }

        $name = $user->getName();
        $pagination = $paginator->paginate(
            $user->getSeries(),
            $request->query->getInt('page', 1),
            10 // 100 series poster per user
        );
        $infoRating = $this->getUserRatingsById($entityManager, $user->getId());
        $pagination2 = $paginator->paginate(
            $infoRating,
            $request->query->getInt('page', 1),
            10 // 5 by page
        );

        $comments = $infoRating['comments'];
        usort($comments, function ($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });

        return $this->render('user/profile.html.twig', [
            'user' => $name,
            'pagination' => $pagination,
            'pagination2' => $pagination2,
            'comments' => $comments,
            ]);
    }// end userProfile()

    #[Route('/user/profile/{username}', name: 'user_profile_search', methods: ['GET', 'POST'])]
    public function userProfileSearch(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        // check if connected
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }
        // paginating series followed
        $userOfUsername = $entityManager->getRepository(User::class)->findOneBy(['name' => $username]);
        $pagination = $paginator->paginate(
            $userOfUsername->getSeries(),
            'series_followed' === $request->query->getInt('category') ? $request->query->getInt('page', 1) : 1,
            10
        );
        $pagination->setParam('category', 'series_followed');
        // paginating critics
        $id = $userOfUsername->getId();
        $infoRating = $this->getUserRatingsById($entityManager, $id);
        $pagination2 = $paginator->paginate(
            $infoRating,
            'series_critics' === $request->query->get('category') ? $request->query->getInt('page', 1) : 1,
            10
        );
        $pagination2->setParam('category', 'series_critics');

        return $this->render('user/profile.html.twig', [
            'user' => $username,
            'pagination' => $pagination,
            'pagination2' => $pagination2,
            'comments' => $infoRating['comments'],
        ]);
    }

    private function getUserRatingsById(EntityManagerInterface $entityManager, int $id)
    {
        $comments = $entityManager->getRepository(Rating::class)->findBy([
            'user' => $id,
        ]);

        return [
            'comments' => $comments,
        ];
    }

    #[Route('/user/ratings', name: 'series_reviews', methods: ['GET', 'POST'])]
    public function seriesReviews(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }
        $id = $user->getId();
        $name = $user->getName();
        $infoRating = $this->getUserRatingsById($entityManager, $id);
        $pagination = $paginator->paginate(
            $infoRating,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/ratings.html.twig',
            [
            'pagination' => $pagination,
            'comments' => $infoRating['comments'],
            'user' => $name,
            ]
        );
    }

    #[Route('/user/ratings/{username}', name: 'series_reviews_by_user', methods: ['GET', 'POST'])]
    public function seriesReviewsByUser(EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator, string $username): Response
    {
        $users = $entityManager
        ->getRepository(User::class);
        $user = $users->findOneBy(['name' => $username]);
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }
        $id = $user->getId();
        $infoRating = $this->getUserRatingsById($entityManager, $id /* $username */);
        $pagination = $paginator->paginate(
            $infoRating,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render(
            'user/ratings.html.twig',
            [
            'pagination' => $pagination,
            'comments' => $infoRating['comments'],
            'user' => $username,
            ]
        );
    }

    #[Route('/user/edit/', name: 'user_editor_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (null == $user) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }
        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $user instanceof User) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'errorN' => $form['name']->getErrors(true),
            'errorP' => $form['plainPassword']->getErrors(true),
            'errorOldP' => $form['password']->getErrors(true),
        ]);
    }

    #[Route('/user/edit/{username}/', name: 'user_editor_edit_Admin', methods: ['GET', 'POST'])]
    public function editProfileUsername(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, string $username): Response
    {
        $userIsAdmin = $this->getUser();
        if (null == $userIsAdmin or (!$this->isGranted('IS_IMPERSONATOR'))) {
            $login = $this->generateUrl('app_login');

            return $this->redirect($login);
        }
        $users = $entityManager->getRepository(User::class);
        $user = $users->findOneBy(['name' => $username]);
        $form = $this->createForm(EditUserAdminType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $user instanceof User) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'errorN' => $form['name']->getErrors(true),
            'errorP' => $form['plainPassword']->getErrors(true),
            'errorOldP' => '',
        ]);
    }
}
