<?php
// Namespace declaration - organizes the command class within the App\Command namespace
namespace App\Command;

// Import the Car entity class to work with car objects
use App\Entity\Car;
// Import Doctrine's EntityManagerInterface for database operations
use Doctrine\ORM\EntityManagerInterface;
// Import Symfony's base Command class to extend from
use Symfony\Component\Console\Command\Command;
// Import InputInterface to handle command input arguments/options
use Symfony\Component\Console\Input\InputInterface;
// Import OutputInterface to display messages to the console
use Symfony\Component\Console\Output\OutputInterface;

// Define the LoadCarsCommand class that extends Symfony's base Command class
class LoadCarsCommand extends Command
{
    // Define the default name for this command (how to call it from terminal: php bin/console app:load-cars)
    protected static $defaultName = 'app:load-cars';
    // Define the default description shown when listing all commands
    protected static $defaultDescription = 'Loads car data into the database';
    // Private property to hold the EntityManager instance
    private $entityManager;

    // Constructor method - called when the command is instantiated
    public function __construct(EntityManagerInterface $entityManager)
    {
        // Store the injected EntityManager in the class property
        $this->entityManager = $entityManager;
        // Call the parent class (Command) constructor
        parent::__construct();
    }

    // Configure method - sets up the command's metadata (name, description, options, arguments)
    protected function configure(): void
    {
        // Set/override the description of the command
        $this->setDescription('Loads car data into the database');
    }

    // Execute method - contains the main logic that runs when the command is called
    // Returns an integer status code (SUCCESS or FAILURE)
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Fetch all existing car records from the database using the Car repository
        $cars = $this->entityManager->getRepository(Car::class)->findAll();
        
        // Loop through each car found in the database
        foreach ($cars as $car) {
            // Mark each car for deletion (removal from database)
            $this->entityManager->remove($car);
        }
        // Execute all pending deletions (actually remove the cars from the database)
        $this->entityManager->flush();

        // Define an array containing all the car data to be loaded
        // Each car has brand, model, basePrice, and image fields
        $allCars = [
            ['brand' => 'Volkswagen', 'model' => 'Golf', 'basePrice' => 25000, 'image' => 'golf.jpg'],
            ['brand' => 'Volkswagen', 'model' => 'Tiguan', 'basePrice' => 28000, 'image' => 'tiguan.jpg'],
            ['brand' => 'Audi', 'model' => 'A3', 'basePrice' => 35000, 'image' => 'audi-a3.jpg'],
            ['brand' => 'Porsche', 'model' => '911', 'basePrice' => 120000, 'image' => 'porsche-911.jpg'],
            ['brand' => 'BMW', 'model' => '3 Series', 'basePrice' => 42000, 'image' => 'bmw-3series.jpg'],
            ['brand' => 'BMW', 'model' => 'X5', 'basePrice' => 62000, 'image' => 'bmw-x5.jpg'],
            ['brand' => 'MINI', 'model' => 'Cooper', 'basePrice' => 24000, 'image' => 'mini-cooper.jpg'],
        ];

        // Loop through each car in the $allCars array
        foreach ($allCars as $carData) {
            // Create a new Car entity instance
            $car = new Car();
            // Set the brand property on the car object
            $car->setBrand($carData['brand']);
            // Set the model property on the car object
            $car->setModel($carData['model']);
            // Set the basePrice property on the car object
            $car->setBasePrice($carData['basePrice']);
            // Set the image filename property on the car object
            $car->setImage($carData['image']);
            
            // Persist (prepare) the car entity for insertion into the database
            $this->entityManager->persist($car);
            // Write a message to the console output confirming which car is being added
            $output->writeln("Adding: " . $carData['brand'] . " " . $carData['model']);
        }

        // Execute all pending insertions (actually save all cars to the database)
        $this->entityManager->flush();
        
        // Write success message to console with the total count of cars loaded
        $output->writeln("Successfully loaded " . count($allCars) . " cars!");
        
        // Return SUCCESS status code (typically 0) to indicate the command completed successfully
        return Command::SUCCESS;
    }
}