<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Environment;
use App\Repository\ApplicationRepository;
use App\Repository\EnvironmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/applications/{applicationId}/environments', name: 'app_environment_')]
class EnvironmentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        int $applicationId,
        ApplicationRepository $applicationRepository,
        EnvironmentRepository $environmentRepository
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $environments = $environmentRepository->findBy(
            ['application' => $application],
            ['createdAt' => 'DESC']
        );

        return $this->render('environment/index.html.twig', [
            'application' => $application,
            'environments' => $environments,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        int $applicationId,
        ApplicationRepository $applicationRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        $environment = new Environment();
        $environment->setApplication($application);

        if ($request->isMethod('POST')) {
            $environment
                ->setName($request->request->get('name', ''))
                ->setType($request->request->get('type', ''))
                ->setUrl($request->request->get('url') ?: null);

            $errors = $validator->validate($environment);

            if (count($errors) === 0) {
                $entityManager->persist($environment);
                $entityManager->flush();

                $this->addFlash('success', 'Environnement créé avec succès.');

                return $this->redirectToRoute('app_environment_index', [
                    'applicationId' => $application->getId(),
                ]);
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('environment/new.html.twig', [
            'application' => $application,
            'environment' => $environment,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        int $applicationId,
        int $id,
        ApplicationRepository $applicationRepository,
        EnvironmentRepository $environmentRepository
    ): Response {
        $application = $applicationRepository->find($applicationId);
        $environment = $environmentRepository->find($id);

        if (!$application || !$environment || $environment->getApplication() !== $application) {
            throw $this->createNotFoundException('Environnement introuvable.');
        }

        return $this->render('environment/show.html.twig', [
            'application' => $application,
            'environment' => $environment,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $applicationId,
        int $id,
        ApplicationRepository $applicationRepository,
        EnvironmentRepository $environmentRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $applicationRepository->find($applicationId);
        $environment = $environmentRepository->find($id);

        if (!$application || !$environment || $environment->getApplication() !== $application) {
            throw $this->createNotFoundException('Environnement introuvable.');
        }

        if ($request->isMethod('POST')) {
            $environment
                ->setName($request->request->get('name', ''))
                ->setType($request->request->get('type', ''))
                ->setUrl($request->request->get('url') ?: null);

            $errors = $validator->validate($environment);

            if (count($errors) === 0) {
                $entityManager->flush();

                $this->addFlash('success', 'Environnement modifié avec succès.');

                return $this->redirectToRoute('app_environment_show', [
                    'applicationId' => $application->getId(),
                    'id' => $environment->getId(),
                ]);
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('environment/edit.html.twig', [
            'application' => $application,
            'environment' => $environment,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $applicationId,
        int $id,
        ApplicationRepository $applicationRepository,
        EnvironmentRepository $environmentRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $application = $applicationRepository->find($applicationId);
        $environment = $environmentRepository->find($id);

        if (!$application || !$environment || $environment->getApplication() !== $application) {
            throw $this->createNotFoundException('Environnement introuvable.');
        }

        if ($this->isCsrfTokenValid(
            'delete_environment_' . $environment->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($environment);
            $entityManager->flush();

            $this->addFlash('success', 'Environnement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_environment_index', [
            'applicationId' => $application->getId(),
        ]);
    }
}

