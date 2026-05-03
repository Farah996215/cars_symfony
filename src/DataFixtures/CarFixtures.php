<?php

namespace App\DataFixtures;

use App\Entity\Car;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CarFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Volkswagen Group Cars
        $volkswagenCars = [
            ['brand' => 'Volkswagen', 'model' => 'Golf', 'basePrice' => 25000, 'image' => 'golf.jpg'],
            ['brand' => 'Volkswagen', 'model' => 'Tiguan', 'basePrice' => 28000, 'image' => 'tiguan.jpg'],
            ['brand' => 'Audi', 'model' => 'A3', 'basePrice' => 35000, 'image' => 'audi-a3.jpg'],
            ['brand' => 'Porsche', 'model' => '911', 'basePrice' => 120000, 'image' => 'porsche-911.jpg'],
        ];

        // BMW Group Cars
        $bmwCars = [
            ['brand' => 'BMW', 'model' => '3 Series', 'basePrice' => 42000, 'image' => 'bmw-3series.jpg'],
            ['brand' => 'BMW', 'model' => 'X5', 'basePrice' => 62000, 'image' => 'bmw-x5.jpg'],
            ['brand' => 'MINI', 'model' => 'Cooper', 'basePrice' => 24000, 'image' => 'mini-cooper.jpg'],
        ];

        // Combine all cars
        $allCars = array_merge($volkswagenCars, $bmwCars);

        foreach ($allCars as $carData) {
            $car = new Car();
            $car->setBrand($carData['brand']);
            $car->setModel($carData['model']);
            $car->setBasePrice($carData['basePrice']);
            $car->setImage($carData['image']);
            
            $manager->persist($car);
        }

        $manager->flush();
        
        echo "Loaded " . count($allCars) . " cars successfully!\n";
    }
}