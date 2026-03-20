<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/utilisateurs', name: 'app_admin_user_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $this->userRepository->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur « ' . $user->getEmail() . ' » créé.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Seul un super administrateur peut modifier un autre super administrateur.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true, 'is_super_admin' => $this->isGranted('ROLE_SUPER_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
            }
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur « ' . $user->getEmail() . ' » mis à jour.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Seul un super administrateur peut supprimer un autre super administrateur.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        $email = $user->getEmail();
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Utilisateur « ' . $email . ' » supprimé.');

        return $this->redirectToRoute('app_admin_user_index');
    }
}
