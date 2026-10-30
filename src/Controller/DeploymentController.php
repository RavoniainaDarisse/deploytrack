<?php

namespace App\Controller;


use App\Entity\Deployment;
use App\Entity\Application;
use App\Repository\ApplicationRepository;
use App\Repository\DeploymentRepository;
use App\Repository\EnvironmentRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/applications/{applicationId}/deployments')]
final class DeploymentController extends AbstractController
{
    private const STATUSES = [
        'pending',
        'running',
        'success',
        'failed',
    ];

    private function getApplication(
        int $applicationId,
        ApplicationRepository $applicationRepository
    ): Application {
        $application = $applicationRepository->find($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Application introuvable.');
        }

        return $application;
    }


    #[Route('', name: 'app_deployment_index', methods: ['GET'])]
    public function index(
        int $applicationId,
        ApplicationRepository $applicationRepository,
        DeploymentRepository $deploymentRepository
    ): Response {
        $application = $this->getApplication(
            $applicationId,
            $applicationRepository
        );

        $deployments = $deploymentRepository->createQueryBuilder('d')
            ->join('d.targetEnvironment', 'e')
            ->where('e.application = :application')
            ->setParameter('application', $application)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('deployment/index.html.twig', [
            'application' => $application,
            'deployments' => $deployments,
        ]);
    }


    #[Route('/new', name: 'app_deployment_new', methods: ['GET', 'POST'])]
    public function new(
        int $applicationId,
        Request $request,
        ApplicationRepository $applicationRepository,
        ReleaseRepository $releaseRepository,
        EnvironmentRepository $environmentRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $this->getApplication(
            $applicationId,
            $applicationRepository
        );

        $deployment = new Deployment();

        $errors = [];
        $relationError = null;

        if ($request->isMethod('POST')) {
            $releaseId = $request->request->get('releaseVersion');
            $environmentId = $request->request->get('targetEnvironment');

            $release = $releaseRepository->find($releaseId);
            $environment = $environmentRepository->find($environmentId);

            if (
                !$release ||
                !$environment ||
                $release->getApplication()->getId() !== $application->getId() ||
                $environment->getApplication()->getId() !== $application->getId()
            ) {
                $relationError = 'La release et l’environnement doivent appartenir à cette application.';
            } else {
                $deployment
                    ->setReleaseVersion($release)
                    ->setTargetEnvironment($environment)
                    ->setStatus('pending');

                $logs = $request->request->get('logs');
                $deployment->setLogs(
                    is_string($logs) && $logs !== '' ? $logs : null
                );

                $errors = $validator->validate($deployment);

                if (count($errors) === 0) {
                    $entityManager->persist($deployment);
                    $entityManager->flush();

                    $this->addFlash(
                        'success',
                        'Le déploiement a été créé avec succès.'
                    );

                    return $this->redirectToRoute('app_deployment_index', [
                        'applicationId' => $application->getId(),
                    ]);
                }
            }
        }

        return $this->render('deployment/new.html.twig', [
            'application' => $application,
            'deployment' => $deployment,
            'releases' => $releaseRepository->findBy(
                ['application' => $application],
                ['createdAt' => 'DESC']
            ),
            'environments' => $environmentRepository->findBy(
                ['application' => $application],
                ['name' => 'ASC']
            ),
            'errors' => $errors,
            'relationError' => $relationError,
        ]);
    }

    #[Route('/{id}', name: 'app_deployment_show', methods: ['GET'])]
    public function show(
        int $applicationId,
        int $id,
        ApplicationRepository $applicationRepository,
        DeploymentRepository $deploymentRepository
    ): Response {
        $application = $this->getApplication(
            $applicationId,
            $applicationRepository
        );

        $deployment = $deploymentRepository->find($id);

        if (
            !$deployment ||
            $deployment->getTargetEnvironment()->getApplication()->getId()
            !== $application->getId()
        ) {
            throw $this->createNotFoundException('Déploiement introuvable.');
        }

        return $this->render('deployment/show.html.twig', [
            'application' => $application,
            'deployment' => $deployment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_deployment_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $applicationId,
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        DeploymentRepository $deploymentRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $application = $this->getApplication(
            $applicationId,
            $applicationRepository
        );

        $deployment = $deploymentRepository->find($id);

        if (
            !$deployment ||
            $deployment->getTargetEnvironment()->getApplication()->getId()
            !== $application->getId()
        ) {
            throw $this->createNotFoundException('Déploiement introuvable.');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $status = $request->request->get('status');
            $logs = $request->request->get('logs');

            if (!in_array($status, self::STATUSES, true)) {
                $errors[] = 'Le statut sélectionné est invalide.';
            } else {
                $this->applyStatus($deployment, $status);

                $deployment->setLogs(
                    is_string($logs) && $logs !== '' ? $logs : null
                );

                $validationErrors = $validator->validate($deployment);

                if (count($validationErrors) === 0) {
                    $entityManager->flush();

                    $this->addFlash(
                        'success',
                        'Le déploiement a été mis à jour.'
                    );

                    return $this->redirectToRoute('app_deployment_show', [
                        'applicationId' => $application->getId(),
                        'id' => $deployment->getId(),
                    ]);
                }

                $errors = $validationErrors;
            }
        }

        return $this->render('deployment/edit.html.twig', [
            'application' => $application,
            'deployment' => $deployment,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_deployment_delete', methods: ['POST'])]
    public function delete(
        int $applicationId,
        int $id,
        Request $request,
        ApplicationRepository $applicationRepository,
        DeploymentRepository $deploymentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $application = $this->getApplication(
            $applicationId,
            $applicationRepository
        );

        $deployment = $deploymentRepository->find($id);

        if (
            !$deployment ||
            $deployment->getTargetEnvironment()->getApplication()->getId()
            !== $application->getId()
        ) {
            throw $this->createNotFoundException('Déploiement introuvable.');
        }

        if ($this->isCsrfTokenValid(
            'delete_deployment_' . $deployment->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($deployment);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Le déploiement a été supprimé.'
            );
        } else {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_deployment_index', [
            'applicationId' => $application->getId(),
        ]);
    }

    private function applyStatus(
        Deployment $deployment,
        string $newStatus
    ): void {
        $now = new \DateTimeImmutable();

        if ($newStatus === 'pending') {
            $deployment
                ->setStartedAt(null)
                ->setFinishedAt(null);
        }

        if ($newStatus === 'running') {
            if ($deployment->getStartedAt() === null) {
                $deployment->setStartedAt($now);
            }

            $deployment->setFinishedAt(null);
        }

        if (in_array($newStatus, ['success', 'failed'], true)) {
            if ($deployment->getStartedAt() === null) {
                $deployment->setStartedAt($now);
            }

            $deployment->setFinishedAt($now);
        }

        $deployment->setStatus($newStatus);
    }
}
