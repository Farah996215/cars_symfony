<?php

namespace App\Controller;

use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CarRepository $carRepository): Response
    {
        // Get featured cars (first 3 cars for the homepage)
        $featuredCars = $carRepository->findBy([], ['id' => 'ASC'], 3);
        
        return $this->render('home/index.html.twig', [
            'featuredCars' => $featuredCars,
        ]);
    }
}