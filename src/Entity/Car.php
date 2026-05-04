<?php
// Namespace declaration - organizes the entity within the App\Entity namespace
namespace App\Entity;

// Import Doctrine ORM mappings for database configuration (Column, Entity, Id, etc.)
use Doctrine\ORM\Mapping as ORM;
// Import Symfony Validator components for input validation rules
use Symfony\Component\Validator\Constraints as Assert;

// ORM Entity declaration - tells Doctrine this class maps to a database table
// repositoryClass: Specifies which repository class to use for database queries
#[ORM\Entity(repositoryClass: 'App\Repository\CarRepository')]
// ORM Table declaration - defines the database table name
#[ORM\Table(name: 'car')]
class Car
{
    // Primary key field
    #[ORM\Id]                    // Marks this property as the primary key
    #[ORM\GeneratedValue]       // Database will auto-generate this value (auto-increment)
    #[ORM\Column]               // Maps this property to a database column
    private ?int $id = null;     // Property can be null initially (before persistence)
                                 // ?int allows null, but after save it becomes actual int

    // Brand field (e.g., "Volkswagen", "Audi", "BMW")
    #[ORM\Column(length: 255)]   // VARCHAR(255) database column
    #[Assert\NotBlank]           // Validation: this field cannot be empty
    private ?string $brand = null;

    // Model field (e.g., "Golf", "A3", "X5")
    #[ORM\Column(length: 255)]   // VARCHAR(255) database column
    #[Assert\NotBlank]           // Validation: this field cannot be empty
    private ?string $model = null;

    // Base price field (in dollars/euros, without options/add-ons)
    #[ORM\Column]                // INT database column
    #[Assert\NotBlank]           // Validation: price cannot be empty
    #[Assert\Positive]           // Validation: price must be greater than 0
    private ?int $basePrice = null;

    // Image filename field (stores the image file name, not the actual image)
    #[ORM\Column(length: 255, nullable: true)]  // VARCHAR(255), can be NULL in database
    private ?string $image = null;              // Nullable for cars without images

    // GETTERS AND SETTERS - allow controlled access to private properties
    
    /**
     * Get the car ID
     * @return int|null The car's unique identifier
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the car brand
     * @return string|null The brand name (e.g., "Volkswagen")
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * Set the car brand
     * @param string $brand The brand name
     * @return static Returns $this for method chaining
     */
    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;  // Allows chaining: $car->setBrand('BMW')->setModel('X5');
    }

    /**
     * Get the car model
     * @return string|null The model name (e.g., "Golf")
     */
    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * Set the car model
     * @param string $model The model name
     * @return static Returns $this for method chaining
     */
    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the base price
     * @return int|null The price as integer (e.g., 25000 for $25,000)
     */
    public function getBasePrice(): ?int
    {
        return $this->basePrice;
    }

    /**
     * Set the base price
     * @param int $basePrice The price as integer
     * @return static Returns $this for method chaining
     */
    public function setBasePrice(int $basePrice): static
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    /**
     * Get the image filename
     * @return string|null The image file name (e.g., "golf.jpg") or null if none
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Set the image filename
     * @param string|null $image The image file name or null
     * @return static Returns $this for method chaining
     */
    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Helper method to get formatted price with currency symbol and thousands separator
     * @return string Formatted price (e.g., "$25,000" or "€25.000" depending on locale)
     * 
     * Example: 25000 -> "$25,000"
     * Example: 120000 -> "$120,000"
     */
    public function getFormattedPrice(): string
    {
        // number_format($number, $decimals, $decimal_separator, $thousands_separator)
        // $number: the price (25000)
        // 0: no decimal places (no cents)
        // ',': thousands separator for US format
        // '.': decimal separator (not needed when decimals=0)
        return '$' . number_format($this->basePrice, 0, ',', '.');
    }
}