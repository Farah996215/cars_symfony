<?php
// Namespace declaration - organizes the controller within the App\Controller namespace
namespace App\Controller;

// Import Configuration entity to work with user car configurations
use App\Entity\Configuration;
// Import ConfigurationRepository to query configuration data from database
use App\Repository\ConfigurationRepository;
// Import Doctrine's EntityManager for database operations (delete, update)
use Doctrine\ORM\EntityManagerInterface;
// Import Symfony's base controller class for common controller functionality
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Response object to send HTTP responses back to the client
use Symfony\Component\HttpFoundation\Response;
// Import Request object to access HTTP request data (POST data, CSRF tokens, etc.)
use Symfony\Component\HttpFoundation\Request;
// Import Route attribute for defining URL routes
use Symfony\Component\Routing\Annotation\Route;

// DashboardController - handles user dashboard functionality
// This is where users can view, manage, and track their car configurations
class DashboardController extends AbstractController
{
    // Route for displaying user dashboard
    // - Path: '/dashboard'
    // - Name: 'app_dashboard' (used for generating URLs and redirects)
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(ConfigurationRepository $configurationRepository): Response
    {
        // Security check: only logged-in users (ROLE_USER or higher) can access dashboard
        // If user is not logged in, Symfony redirects to login page
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Get all configurations for the currently logged-in user
        // findByUser() is a custom method in ConfigurationRepository
        // Returns an array of Configuration entities belonging to this user
        $configurations = $configurationRepository->findByUser($this->getUser());
        
        // Render dashboard template with user's configurations
        // Template will display all saved car configurations in a list/table
        return $this->render('dashboard/index.html.twig', [
            'configurations' => $configurations,  // Pass configurations to Twig
        ]);
    }

    // Route for deleting a configuration
    // - Path: '/dashboard/delete/{id}' (e.g., /dashboard/delete/42)
    // - Name: 'app_delete_configuration'
    // - methods: ['POST'] - only accepts POST requests (prevents accidental GET deletions)
    #[Route('/dashboard/delete/{id}', name: 'app_delete_configuration', methods: ['POST'])]
    public function deleteConfiguration(
        Configuration $configuration,      // Entity injected automatically by ID from URL
        EntityManagerInterface $entityManager,  // For database operations
        Request $request                    // To access CSRF token
    ): Response {
        // Security check: only logged-in users can delete
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // IMPORTANT: Verify ownership - ensure user can only delete THEIR OWN configurations
        // $this->getUser() returns the currently logged-in user
        // $configuration->getUser() returns the owner of this configuration
        if ($configuration->getUser() !== $this->getUser()) {
            // User trying to delete someone else's configuration
            $this->addFlash('error', 'You cannot delete this configuration.');
            // Redirect back to dashboard
            return $this->redirectToRoute('app_dashboard');
        }
        
        // CSRF (Cross-Site Request Forgery) protection
        // Validate that the deletion request came from our form, not a malicious site
        // Token name format: 'delete' + configuration ID (e.g., 'delete42')
        // Token value comes from hidden form field named '_token'
        if ($this->isCsrfTokenValid('delete' . $configuration->getId(), $request->request->get('_token'))) {
            // Mark configuration for deletion
            $entityManager->remove($configuration);
            // Execute deletion (permanently remove from database)
            $entityManager->flush();
            // Success message - will appear on next page load
            $this->addFlash('success', 'Configuration deleted successfully!');
        } else {
            // CSRF token validation failed - possible security attack
            $this->addFlash('error', 'Invalid token.');
        }
        
        // Redirect back to dashboard regardless of success/failure
        return $this->redirectToRoute('app_dashboard');
    }

    // Route for updating configuration status
    // - Path: '/dashboard/update-status/{id}/{status}' (e.g., /dashboard/update-status/42/Confirmed)
    // - Name: 'app_update_configuration_status'
    // - methods: ['POST'] - only accepts POST requests
    #[Route('/dashboard/update-status/{id}/{status}', name: 'app_update_configuration_status', methods: ['POST'])]
    public function updateStatus(
        Configuration $configuration,      // Configuration entity (injected by ID)
        string $status,                     // New status from URL (e.g., 'Confirmed')
        EntityManagerInterface $entityManager,  // For database updates
        Request $request                    // To access CSRF token
    ): Response {
        // Security check: only logged-in users can update status
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Verify ownership - ensure user can only update THEIR OWN configurations
        if ($configuration->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'You cannot modify this configuration.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Validate that the status is one of the allowed values
        // Prevents injection of invalid or malicious status values
        $validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // CSRF protection for status update
        // Token name format: 'status' + configuration ID (e.g., 'status42')
        if ($this->isCsrfTokenValid('status' . $configuration->getId(), $request->request->get('_token'))) {
            // Update the status property of the configuration
            $configuration->setStatus($status);
            // Save changes to database (UPDATE query)
            $entityManager->flush();
            // Success message with new status
            $this->addFlash('success', 'Status updated to ' . $status . '!');
        } else {
            // Invalid CSRF token
            $this->addFlash('error', 'Invalid token.');
        }
        
        // Redirect back to dashboard
        return $this->redirectToRoute('app_dashboard');
    }
}