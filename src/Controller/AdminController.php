<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Configuration;
use App\Entity\User;
use App\Form\CarType;
use App\Repository\CarRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        CarRepository $carRepository,
        ConfigurationRepository $configurationRepository
    ): Response {
        // Check if user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $stats = [
            'total_users' => count($userRepository->findAll()),
            'total_cars' => count($carRepository->findAll()),
            'total_configurations' => count($configurationRepository->findAll()),
            'pending_configurations' => count($configurationRepository->findBy(['status' => 'Pending'])),
            'confirmed_configurations' => count($configurationRepository->findBy(['status' => 'Confirmed'])),
            'completed_configurations' => count($configurationRepository->findBy(['status' => 'Completed'])),
        ];
        
        $recentConfigurations = $configurationRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $recentUsers = $userRepository->findBy([], ['id' => 'DESC'], 10);
        
        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentConfigurations' => $recentConfigurations,
            'recentUsers' => $recentUsers,
        ]);
    }
    
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/user/{id}/toggle-admin', name: 'app_admin_toggle_admin')]
    public function toggleAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            // Remove admin role
            $newRoles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
            $user->setRoles(array_values($newRoles));
            $this->addFlash('success', 'Admin rights removed from ' . $user->getEmail());
        } else {
            // Add admin role
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $this->addFlash('success', 'Admin rights granted to ' . $user->getEmail());
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('app_admin_users');
    }
    
    #[Route('/configurations', name: 'app_admin_configurations')]
    public function configurations(ConfigurationRepository $configurationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $configurations = $configurationRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/configurations.html.twig', [
            'configurations' => $configurations,
        ]);
    }
    
    // Car Management CRUD
    
    #[Route('/cars', name: 'app_admin_cars')]
    public function cars(CarRepository $carRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $cars = $carRepository->findAll();
        
        return $this->render('admin/cars.html.twig', [
            'cars' => $cars,
        ]);
    }
    
    #[Route('/car/new', name: 'app_admin_car_new')]
    public function newCar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $car = new Car();
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($car);
            $entityManager->flush();
            
            $this->addFlash('success', 'Car added successfully!');
            return $this->redirectToRoute('app_admin_cars');
        }
        
        return $this->render('admin/car_form.html.twig', [
            'form' => $form->createView(),
            'car' => $car,
            'is_edit' => false,
        ]);
    }
    
    #[Route('/car/{id}/edit', name: 'app_admin_car_edit')]
    public function editCar(Car $car, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(CarType::class, $car);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Car updated successfully!');
            return $this->redirectToRoute('app_admin_cars');
        }
        
        return $this->render('admin/car_form.html.twig', [
            'form' => $form->createView(),
            'car' => $car,
            'is_edit' => true,
        ]);
    }
    
    #[Route('/car/{id}/delete', name: 'app_admin_car_delete', methods: ['POST'])]
    public function deleteCar(Car $car, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete' . $car->getId(), $request->request->get('_token'))) {
            $carName = $car->getBrand() . ' ' . $car->getModel();
            $entityManager->remove($car);
            $entityManager->flush();
            $this->addFlash('success', 'Car ' . $carName . ' deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid token.');
        }
        
        return $this->redirectToRoute('app_admin_cars');
    }
}