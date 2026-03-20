<?php

namespace App\Controller\Admin;

use App\Entity\CarouselSlide;
use App\Form\CarouselSlideType;
use App\Repository\CarouselSlideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/carousel', name: 'app_admin_carousel_')]
class CarouselController extends AbstractController
{
    public function __construct(
        private readonly CarouselSlideRepository $carouselSlideRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $slides = $this->carouselSlideRepository->findAllOrderByPosition();

        return $this->render('admin/carousel/index.html.twig', [
            'slides' => $slides,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $slide = new CarouselSlide();
        $maxPosition = $this->carouselSlideRepository->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->getQuery()
            ->getSingleScalarResult();
        $slide->setPosition((int) $maxPosition + 1);
        $form = $this->createForm(CarouselSlideType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($slide);
            $this->entityManager->flush();
            $this->addFlash('success', 'La slide « ' . $slide->getTitle() . ' » a été ajoutée.');

            return $this->redirectToRoute('app_admin_carousel_index');
        }

        return $this->render('admin/carousel/new.html.twig', [
            'slide' => $slide,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, CarouselSlide $slide): Response
    {
        $form = $this->createForm(CarouselSlideType::class, $slide);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'La slide « ' . $slide->getTitle() . ' » a été mise à jour.');

            return $this->redirectToRoute('app_admin_carousel_index');
        }

        return $this->render('admin/carousel/edit.html.twig', [
            'slide' => $slide,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, CarouselSlide $slide): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $slide->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_carousel_index');
        }

        $title = $slide->getTitle();
        $this->entityManager->remove($slide);
        $this->entityManager->flush();
        $this->addFlash('success', 'La slide « ' . $title . ' » a été supprimée.');

        return $this->redirectToRoute('app_admin_carousel_index');
    }
}
