<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CarConfiguratorController extends AbstractController
{
    #[Route('/configure/{id}', name: 'app_car_configurator')]
    public function configure(int $id, CarRepository $carRepository): Response
    {
        $car = $carRepository->find($id);
        
        if (!$car) {
            throw $this->createNotFoundException('Car not found');
        }
        
        return $this->render('car_configurator/index.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route('/configure/save/{id}', name: 'app_save_configuration', methods: ['POST'])]
    public function saveConfiguration(
        int $id, 
        CarRepository $carRepository, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        // Check if user is logged in
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $car = $carRepository->find($id);
        
        if (!$car) {
            return $this->json(['error' => 'Car not found'], 404);
        }
        
        // Get configuration data from the request
        $data = json_decode($request->getContent(), true);
        
        // Create new configuration
        $configuration = new Configuration();
        $configuration->setUser($this->getUser());
        $configuration->setCar($car);
        $configuration->setColor($data['color']['name']);
        $configuration->setDesignPackage($data['package']['name']);
        $configuration->setInterior($data['interior']['name']);
        $configuration->setTotalPrice($data['totalPrice']);
        $configuration->setStatus('Pending');
        $configuration->setCreatedAt(new \DateTimeImmutable());
        
        $entityManager->persist($configuration);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Configuration saved successfully!',
            'configuration_id' => $configuration->getId()
        ]);
    }
}