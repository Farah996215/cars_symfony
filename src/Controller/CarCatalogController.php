<?php
// Namespace declaration - organizes the controller within the App\Controller namespace
namespace App\Controller;

// Import CarRepository to fetch car data from database
use App\Repository\CarRepository;
// Import Symfony's base controller class for common controller functionality
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Response object to send HTTP responses back to the client (browser)
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute for defining URL routes and their names
use Symfony\Component\Routing\Annotation\Route;

// CarCatalogController - handles public-facing car catalog pages
// This controller is accessible to all users (no admin restrictions)
class CarCatalogController extends AbstractController
{
    // Route configuration:
    // - Path: '/cars' (URL pattern like https://yourdomain.com/cars)
    // - Name: 'app_car_catalog' (used for generating URLs with path() or redirectToRoute())
    #[Route('/cars', name: 'app_car_catalog')]
    public function index(CarRepository $carRepository): Response
    {
        // Fetch all cars from database using a custom repository method
        // findAllOrderedByBrand() retrieves cars sorted alphabetically by brand name
        // This method must be defined in CarRepository class
        $cars = $carRepository->findAllOrderedByBrand();
        
        // Debug statement: outputs the number of cars found
        // dump() is a Symfony debugging function (similar to var_dump but enhanced)
        // This will appear in the Symfony debug toolbar or console
        // Use for development only - remove in production!
        dump('Number of cars found: ' . count($cars));
        
        // Render the Twig template 'car_catalog/index.html.twig'
        // Pass the cars array to the template as a variable named 'cars'
        // The template will loop through this array and display each car
        return $this->render('car_catalog/index.html.twig', [
            'cars' => $cars,  // Make $cars variable available in Twig template
        ]);
    }
}