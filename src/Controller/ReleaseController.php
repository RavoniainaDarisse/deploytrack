<?php

namespace App\Controller;


use App\Entity\Application;
use App\Entity\Release;
use App\Repository\ApplicationRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ReleaseController extends AbstractController
{
    #[Route(
        '/applications/{applicationId}/releases',
        name: 'app_release_index',
        methods: ['GET']
    )]
    public function index(
        int $applicationId,
        ApplicationRepository $applicationRepository,
        ReleaseRepository $releaseRepository
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $releases = $releaseRepository->findBy(
            ['application' => $application],
            ['createdAt' => 'DESC']
        );

        return $this->render('release/index.html.twig', [
            'application' => $application,
            'releases' => $releases,
        ]);
    }

    #[Route(
        '/applications/{applicationId}/releases/new',
        name: 'app_release_new',
        methods: ['GET', 'POST']
    )]
    public function new(
        int $applicationId,
        Request $request,
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $release = new Release();
        $release->setApplication($application);

        if ($request->isMethod('POST')) {
            $release->setVersion($request->request->get('version'));
            $release->setDescription($request->request->get('description'));

            $errors = $validator->validate($release);

            if (count($errors) === 0) {
                $entityManager->persist($release);
                $entityManager->flush();

                $this->addFlash('success', 'La release a été créée avec succès.');

                return $this->redirectToRoute('app_release_index', [
                    'applicationId' => $application->getId(),
                ]);
            }

            return $this->render('release/new.html.twig', [
                'application' => $application,
                'release' => $release,
                'errors' => $errors,
            ]);
        }

        return $this->render('release/new.html.twig', [
            'application' => $application,
            'release' => $release,
            'errors' => [],
        ]);
    }

    #[Route(
        '/applications/{applicationId}/releases/{id}',
        name: 'app_release_show',
        methods: ['GET']
    )]
    public function show(
        int $applicationId,
        int $id,
        ApplicationRepository $applicationRepository,
        ReleaseRepository $releaseRepository
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $release = $releaseRepository->findOneBy([
            'id' => $id,
            'application' => $application,
        ]);

        if (!$release) {
            throw $this->createNotFoundException('Release introuvable.');
        }

        return $this->render('release/show.html.twig', [
            'application' => $application,
            'release' => $release,
        ]);
    }

    #[Route(
        '/applications/{applicationId}/releases/{id}/edit',
        name: 'app_release_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(
        int $applicationId,
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        ReleaseRepository $releaseRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $release = $releaseRepository->findOneBy([
            'id' => $id,
            'application' => $application,
        ]);

        if (!$release) {
            throw $this->createNotFoundException('Release introuvable.');
        }

        if ($request->isMethod('POST')) {
            $release->setVersion($request->request->get('version'));
            $release->setDescription($request->request->get('description'));

            $errors = $validator->validate($release);

            if (count($errors) === 0) {
                $entityManager->flush();

                $this->addFlash('success', 'La release a été modifiée avec succès.');

                return $this->redirectToRoute('app_release_show', [
                    'applicationId' => $application->getId(),
                    'id' => $release->getId(),
                ]);
            }

            return $this->render('release/edit.html.twig', [
                'application' => $application,
                'release' => $release,
                'errors' => $errors,
            ]);
        }

        return $this->render('release/edit.html.twig', [
            'application' => $application,
            'release' => $release,
            'errors' => [],
        ]);
    }

    #[Route(
        '/applications/{applicationId}/releases/{id}/delete',
        name: 'app_release_delete',
        methods: ['POST']
    )]
    public function delete(
        int $applicationId,
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        ReleaseRepository $releaseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $release = $releaseRepository->findOneBy([
            'id' => $id,
            'application' => $application,
        ]);

        if (!$release) {
            throw $this->createNotFoundException('Release introuvable.');
        }

        if ($this->isCsrfTokenValid(
            'delete_release_' . $release->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($release);
            $entityManager->flush();

            $this->addFlash('success', 'La release a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_release_index', [
            'applicationId' => $application->getId(),
        ]);
    }
}

