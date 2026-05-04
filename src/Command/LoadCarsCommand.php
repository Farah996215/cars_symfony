<?php

namespace App\Command;

use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCarsCommand extends Command
{
    protected static $defaultName = 'app:load-cars';
    protected static $defaultDescription = 'Loads car data into the database';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Loads car data into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Clear existing cars
        $cars = $this->entityManager->getRepository(Car::class)->findAll();
        foreach ($cars as $car) {
            $this->entityManager->remove($car);
        }
        $this->entityManager->flush();

        // Volkswagen Group Cars
        $allCars = [
            ['brand' => 'Volkswagen', 'model' => 'Golf', 'basePrice' => 25000, 'image' => 'golf.jpg'],
            ['brand' => 'Volkswagen', 'model' => 'Tiguan', 'basePrice' => 28000, 'image' => 'tiguan.jpg'],
            ['brand' => 'Audi', 'model' => 'A3', 'basePrice' => 35000, 'image' => 'audi-a3.jpg'],
            ['brand' => 'Porsche', 'model' => '911', 'basePrice' => 120000, 'image' => 'porsche-911.jpg'],
            ['brand' => 'BMW', 'model' => '3 Series', 'basePrice' => 42000, 'image' => 'bmw-3series.jpg'],
            ['brand' => 'BMW', 'model' => 'X5', 'basePrice' => 62000, 'image' => 'bmw-x5.jpg'],
            ['brand' => 'MINI', 'model' => 'Cooper', 'basePrice' => 24000, 'image' => 'mini-cooper.jpg'],
        ];

        foreach ($allCars as $carData) {
            $car = new Car();
            $car->setBrand($carData['brand']);
            $car->setModel($carData['model']);
            $car->setBasePrice($carData['basePrice']);
            $car->setImage($carData['image']);
            
            $this->entityManager->persist($car);
            $output->writeln("Adding: " . $carData['brand'] . " " . $carData['model']);
        }

        $this->entityManager->flush();
        
        $output->writeln("Successfully loaded " . count($allCars) . " cars!");
        return Command::SUCCESS;
    }
}