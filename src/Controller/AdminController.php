<?php
// Namespace declaration - organizes the controller within the App\Controller namespace
namespace App\Controller;

// Import Car entity to manage car data
use App\Entity\Car;
// Import Configuration entity to manage user car configurations
use App\Entity\Configuration;
// Import User entity to manage user accounts
use App\Entity\User;
// Import CarType form for car creation/editing forms
use App\Form\CarType;
// Import CarRepository for database queries on cars
use App\Repository\CarRepository;
// Import ConfigurationRepository for database queries on configurations
use App\Repository\ConfigurationRepository;
// Import UserRepository for database queries on users
use App\Repository\UserRepository;
// Import Doctrine's EntityManager for database operations
use Doctrine\ORM\EntityManagerInterface;
// Import Symfony's base controller class
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request object to access HTTP request data (POST, GET, etc.)
use Symfony\Component\HttpFoundation\Request;
// Import Response object to send HTTP responses back to client
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute for defining URL routes
use Symfony\Component\Routing\Annotation\Route;

// Route prefix - all routes in this controller will start with '/admin'
#[Route('/admin')]
class AdminController extends AbstractController
{
    // Route for admin dashboard - accessed via GET /admin/
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        CarRepository $carRepository,
        ConfigurationRepository $configurationRepository
    ): Response {
        // Security check - only users with ROLE_ADMIN can access this method
        // Throws AccessDeniedException if user doesn't have admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Create statistics array for dashboard display
        $stats = [
            // Count total registered users
            'total_users' => count($userRepository->findAll()),
            // Count total available cars
            'total_cars' => count($carRepository->findAll()),
            // Count total configurations (orders)
            'total_configurations' => count($configurationRepository->findAll()),
            // Count pending configurations (not yet confirmed)
            'pending_configurations' => count($configurationRepository->findBy(['status' => 'Pending'])),
            // Count confirmed configurations
            'confirmed_configurations' => count($configurationRepository->findBy(['status' => 'Confirmed'])),
            // Count completed configurations
            'completed_configurations' => count($configurationRepository->findBy(['status' => 'Completed'])),
        ];
        
        // Get last 10 configurations, ordered by creation date (newest first)
        $recentConfigurations = $configurationRepository->findBy([], ['createdAt' => 'DESC'], 10);
        // Get last 10 registered users, ordered by ID (newest first)
        $recentUsers = $userRepository->findBy([], ['id' => 'DESC'], 10);
        
        // Render dashboard template with statistics and recent data
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentConfigurations' => $recentConfigurations,
            'recentUsers' => $recentUsers,
        ]);
    }
    
    // Route for users management page - GET /admin/users
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Fetch all users from database
        $users = $userRepository->findAll();
        
        // Render users list template
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
    
    // Route to toggle admin privileges for a user - GET /admin/user/{id}/toggle-admin
    #[Route('/user/{id}/toggle-admin', name: 'app_admin_toggle_admin')]
    public function toggleAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get current roles of the user
        $roles = $user->getRoles();
        
        // Check if user already has admin role
        if (in_array('ROLE_ADMIN', $roles)) {
            // Remove admin role: filter out ROLE_ADMIN from roles array
            $newRoles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
            // Reindex array and set new roles
            $user->setRoles(array_values($newRoles));
            // Add success flash message (temporary session message)
            $this->addFlash('success', 'Admin rights removed from ' . $user->getEmail());
        } else {
            // Add admin role to existing roles
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            // Add success flash message
            $this->addFlash('success', 'Admin rights granted to ' . $user->getEmail());
        }
        
        // Save changes to database
        $entityManager->flush();
        
        // Redirect back to users list page
        return $this->redirectToRoute('app_admin_users');
    }
    
    // Route for configurations management page - GET /admin/configurations
    #[Route('/configurations', name: 'app_admin_configurations')]
    public function configurations(ConfigurationRepository $configurationRepository): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Fetch all configurations, ordered by creation date (newest first)
        $configurations = $configurationRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Render configurations list template
        return $this->render('admin/configurations.html.twig', [
            'configurations' => $configurations,
        ]);
    }
    
    // CAR MANAGEMENT CRUD (Create, Read, Update, Delete)
    
    // Route for cars management page - GET /admin/cars
    #[Route('/cars', name: 'app_admin_cars')]
    public function cars(CarRepository $carRepository): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Fetch all cars from database
        $cars = $carRepository->findAll();
        
        // Render cars list template
        return $this->render('admin/cars.html.twig', [
            'cars' => $cars,
        ]);
    }
    
    // Route for creating a new car - GET/POST /admin/car/new
    #[Route('/car/new', name: 'app_admin_car_new')]
    public function newCar(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Create new empty Car entity
        $car = new Car();
        // Create form for the car entity using CarType
        $form = $this->createForm(CarType::class, $car);
        // Handle the request (populates form with submitted data if POST request)
        $form->handleRequest($request);
        
        // Check if form was submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Prepare car for database insertion
            $entityManager->persist($car);
            // Execute insertion (save to database)
            $entityManager->flush();
            
            // Add success flash message
            $this->addFlash('success', 'Car added successfully!');
            // Redirect to cars list page
            return $this->redirectToRoute('app_admin_cars');
        }
        
        // Render car form template (for GET request or invalid form)
        return $this->render('admin/car_form.html.twig', [
            'form' => $form->createView(),  // Convert form to view format
            'car' => $car,                   // Pass car entity to template
            'is_edit' => false,              // Flag indicating this is create mode (not edit)
        ]);
    }
    
    // Route for editing an existing car - GET/POST /admin/car/{id}/edit
    #[Route('/car/{id}/edit', name: 'app_admin_car_edit')]
    public function editCar(Car $car, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Create form pre-populated with existing car data
        $form = $this->createForm(CarType::class, $car);
        // Handle the request (updates car with submitted data if POST)
        $form->handleRequest($request);
        
        // Check if form was submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            // Save changes to database (no need to persist - car is already managed)
            $entityManager->flush();
            
            // Add success flash message
            $this->addFlash('success', 'Car updated successfully!');
            // Redirect to cars list page
            return $this->redirectToRoute('app_admin_cars');
        }
        
        // Render car form template for editing
        return $this->render('admin/car_form.html.twig', [
            'form' => $form->createView(),  // Convert form to view format
            'car' => $car,                   // Pass existing car entity
            'is_edit' => true,               // Flag indicating this is edit mode
        ]);
    }
    
    // Route for deleting a car - POST /admin/car/{id}/delete
    // Note: METHOD is POST for security (prevents accidental GET deletions)
    #[Route('/car/{id}/delete', name: 'app_admin_car_delete', methods: ['POST'])]
    public function deleteCar(Car $car, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Ensure only admin can access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Verify CSRF token to prevent cross-site request forgery attacks
        // Token name is 'delete' + car ID
        if ($this->isCsrfTokenValid('delete' . $car->getId(), $request->request->get('_token'))) {
            // Get car name for flash message
            $carName = $car->getBrand() . ' ' . $car->getModel();
            // Mark car for deletion
            $entityManager->remove($car);
            // Execute deletion (remove from database)
            $entityManager->flush();
            // Add success flash message
            $this->addFlash('success', 'Car ' . $carName . ' deleted successfully!');
        } else {
            // Add error flash message if CSRF token is invalid
            $this->addFlash('error', 'Invalid token.');
        }
        
        // Redirect back to cars list page
        return $this->redirectToRoute('app_admin_cars');
    }
}