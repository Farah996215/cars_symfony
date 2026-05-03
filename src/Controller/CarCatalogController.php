<?php

namespace App\Controller;

use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CarCatalogController extends AbstractController
{
    #[Route('/cars', name: 'app_car_catalog')]
    public function index(CarRepository $carRepository): Response
    {
        $cars = $carRepository->findAllOrderedByBrand();
        
        // Debug: dump the number of cars found
        dump('Number of cars found: ' . count($cars));
        
        return $this->render('car_catalog/index.html.twig', [
            'cars' => $cars,
        ]);
    }
}