<?php
// Namespace declaration - organizes the controller within the App\Controller namespace
namespace App\Controller;

// Import Configuration entity to create and save user car configurations
use App\Entity\Configuration;
// Import CarRepository to fetch car data from database
use App\Repository\CarRepository;
// Import Doctrine's EntityManager for database operations (save, update, delete)
use Doctrine\ORM\EntityManagerInterface;
// Import Symfony's base controller class for common controller functionality
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request object to access HTTP request data (POST data, JSON, etc.)
use Symfony\Component\HttpFoundation\Request;
// Import Response object to send HTTP responses back to the client
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute for defining URL routes
use Symfony\Component\Routing\Annotation\Route;

// CarConfiguratorController - handles car configuration (customization) functionality
// This allows logged-in users to customize and save their car configurations
class CarConfiguratorController extends AbstractController
{
    // Route for displaying the car configuration page
    // - Path: '/configure/{id}' (e.g., /configure/5)
    // - Name: 'app_car_configurator' (used for generating URLs)
    // - {id} is a dynamic parameter (the car ID)
    #[Route('/configure/{id}', name: 'app_car_configurator')]
    public function configure(int $id, CarRepository $carRepository): Response
    {
        // Find car by ID from the database
        // If found, returns Car entity; if not found, returns null
        $car = $carRepository->find($id);
        
        // Check if car exists
        if (!$car) {
            // Throw 404 Not Found exception - Symfony converts this to error page
            throw $this->createNotFoundException('Car not found');
        }
        
        // Render the configuration page template
        // Pass the car entity to the template for displaying car details
        return $this->render('car_configurator/index.html.twig', [
            'car' => $car,  // Car data available in Twig template
        ]);
    }

    // Route for saving a car configuration (AJAX/POST request)
    // - Path: '/configure/save/{id}' (e.g., /configure/save/5)
    // - Name: 'app_save_configuration' (used for form submissions)
    // - methods: ['POST'] - only accepts HTTP POST requests (for security)
    #[Route('/configure/save/{id}', name: 'app_save_configuration', methods: ['POST'])]
    public function saveConfiguration(
        int $id,                    // Car ID from URL parameter
        CarRepository $carRepository,  // Repository to find the car
        Request $request,           // HTTP request object to get POST data
        EntityManagerInterface $entityManager  // To save configuration to database
    ): Response {
        // Security check: only logged-in users (ROLE_USER or higher) can save configurations
        // If user is not logged in, Symfony redirects to login page
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        // Find the car being configured
        $car = $carRepository->find($id);
        
        // Check if car exists
        if (!$car) {
            // Return JSON error response with 404 status code
            // This is an API response since frontend likely uses JavaScript
            return $this->json(['error' => 'Car not found'], 404);
        }
        
        // Extract configuration data from the POST request body
        // Assumes frontend sends JSON data like:
        // {
        //   "color": {"name": "Red"},
        //   "package": {"name": "Sport Package"},
        //   "interior": {"name": "Leather"},
        //   "totalPrice": 45000
        // }
        $data = json_decode($request->getContent(), true);
        
        // Create a new Configuration entity instance
        $configuration = new Configuration();
        
        // Set the user who created this configuration
        // $this->getUser() returns the currently logged-in user object
        $configuration->setUser($this->getUser());
        
        // Set which car is being configured
        $configuration->setCar($car);
        
        // Set configuration options from the JSON data
        // Access nested data using array keys from the decoded JSON
        $configuration->setColor($data['color']['name']);        // Selected color
        $configuration->setDesignPackage($data['package']['name']);  // Design package/trim
        $configuration->setInterior($data['interior']['name']);      // Interior option
        $configuration->setTotalPrice($data['totalPrice']);          // Final calculated price
        
        // Set initial status of the configuration
        // Possible values: 'Pending', 'Confirmed', 'Completed'
        $configuration->setStatus('Pending');
        
        // Set creation timestamp with current date and time
        // DateTimeImmutable creates an immutable date object (cannot be changed)
        $configuration->setCreatedAt(new \DateTimeImmutable());
        
        // Prepare configuration for database insertion
        $entityManager->persist($configuration);
        
        // Execute the insertion (actually save to database)
        $entityManager->flush();
        
        // Return success response as JSON
        // Frontend JavaScript can use this to show confirmation message
        return $this->json([
            'success' => true,
            'message' => 'Configuration saved successfully!',
            'configuration_id' => $configuration->getId()  // Return ID for reference
        ]);
    }
}