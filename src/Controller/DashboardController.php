<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ConfigurationRepository $configurationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Get all configurations for the logged-in user
        $configurations = $configurationRepository->findByUser($this->getUser());
        
        return $this->render('dashboard/index.html.twig', [
            'configurations' => $configurations,
        ]);
    }

    #[Route('/dashboard/delete/{id}', name: 'app_delete_configuration', methods: ['POST'])]
    public function deleteConfiguration(Configuration $configuration, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Check if the configuration belongs to the logged-in user
        if ($configuration->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot delete this configuration.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete' . $configuration->getId(), $request->request->get('_token'))) {
            $entityManager->remove($configuration);
            $entityManager->flush();
            $this->addFlash('success', 'Configuration deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid token.');
        }
        
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard/update-status/{id}/{status}', name: 'app_update_configuration_status', methods: ['POST'])]
    public function updateStatus(Configuration $configuration, string $status, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Check if the configuration belongs to the logged-in user
        if ($configuration->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot modify this configuration.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Validate status
        $validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('status' . $configuration->getId(), $request->request->get('_token'))) {
            $configuration->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Status updated to ' . $status . '!');
        } else {
            $this->addFlash('error', 'Invalid token.');
        }
        
        return $this->redirectToRoute('app_dashboard');
    }
}