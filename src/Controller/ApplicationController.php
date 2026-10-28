<?php

namespace App\Controller;

use App\Entity\Application;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ApplicationController extends AbstractController
{

    #[Route('/applications', name: 'app_application_index', methods: ['GET'])]
    public function index(
        ApplicationRepository $applicationRepository
    ): Response {
        $applications = $applicationRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render('application/index.html.twig', [
            'applications' => $applications,
        ]);
    }


    #[Route('/applications/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = new Application();

        dump($application);


        if ($request->isMethod('POST')) {
            $application->setName(

                trim($request->request->getString('name'))
            );

            $application->setRepositoryUrl(
                trim($request->request->getString('repositoryUrl')) ?: null
            );

            $application->setDescription(
                trim($request->request->getString('description')) ?: null
            );

            $errors = $validator->validate($application);

            if (count($errors) === 0) {
                $entityManager->persist($application);
                $entityManager->flush();

                $this->addFlash('success', 'Application créée avec succès.');

                return $this->redirectToRoute('app_application_index');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('application/new.html.twig');
    }

    #[Route(
        '/applications/{id}',
        name: 'app_application_show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        ApplicationRepository $applicationRepository
    ): Response {
        $application = $applicationRepository->find($id);

        if (!$application) {
            throw $this->createNotFoundException(
                'Application introuvable.'
            );
        }

        return $this->render('application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route(
        '/applications/{id}/delete',
        name: 'app_application_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function delete(
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $application = $applicationRepository->find($id);

        if (!$application) {
            throw $this->createNotFoundException(
                'Application introuvable.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'delete_application_' . $application->getId(),
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $entityManager->remove($application);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Application supprimée avec succès.'
        );

        return $this->redirectToRoute('app_application_index');
    }

    #[Route(
        '/applications/{id}/edit',
        name: 'app_application_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $applicationRepository->find($id);

        if (!$application) {
            throw $this->createNotFoundException(
                'Application introuvable.'
            );
        }

        if ($request->isMethod('POST')) {
            $application->setName(
                trim($request->request->getString('name'))
            );

            $application->setRepositoryUrl(
                trim($request->request->getString('repositoryUrl')) ?: null
            );

            $application->setDescription(
                trim($request->request->getString('description')) ?: null
            );

            $errors = $validator->validate($application);

            if (count($errors) === 0) {
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Application modifiée avec succès.'
                );

                return $this->redirectToRoute(
                    'app_application_show',
                    ['id' => $application->getId()]
                );
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('application/edit.html.twig', [
            'application' => $application,
        ]);
    }
}
