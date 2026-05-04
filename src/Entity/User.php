<?php
// Namespace declaration - organizes the entity within the App\Entity namespace
namespace App\Entity;

// Import Doctrine collections for handling one-to-many relationships
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// Import Doctrine ORM mappings for database configuration
use Doctrine\ORM\Mapping as ORM;
// Import Symfony Security interfaces for authentication and password handling
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
// Import Symfony Validator components for input validation rules
use Symfony\Component\Validator\Constraints as Assert;

// ORM Entity declaration - tells Doctrine this class maps to a database table
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
// ORM Table declaration - defines the database table name (escaped with backticks because 'user' is a reserved word in SQL)
#[ORM\Table(name: '`user`')]
// User class implements two Symfony Security interfaces:
// - UserInterface: Provides methods for authentication (roles, password, user identifier)
// - PasswordAuthenticatedUserInterface: Provides method to get hashed password for verification
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Primary key field
    #[ORM\Id]                    // Marks this property as the primary key
    #[ORM\GeneratedValue]       // Database will auto-generate this value (auto-increment)
    #[ORM\Column]               // Maps this property to a database column
    private ?int $id = null;     // Property can be null initially (before persistence)

    // Email field - used as username for authentication
    #[ORM\Column(length: 180, unique: true)]  // VARCHAR(180), unique constraint - no two users can share an email
    #[Assert\NotBlank]                         // Validation: email cannot be empty
    #[Assert\Email]                            // Validation: must be a valid email format (user@example.com)
    private ?string $email = null;

    // Roles field - stores user roles (ROLE_USER, ROLE_ADMIN, etc.)
    #[ORM\Column]                // JSON type in database (since array stored as JSON)
    private array $roles = [];   // Default empty array - ROLE_USER will be added automatically

    // Password field - stores the HASHED password, NOT plain text!
    #[ORM\Column]                // VARCHAR(255) in database
    private ?string $password = null;  // Stores bcrypt/argon2 hash, not raw password

    // Name field - user's display name
    #[ORM\Column(length: 180)]   // VARCHAR(180) database column
    #[Assert\NotBlank]           // Validation: name cannot be empty
    private ?string $name = null;

    // Email verification status
    #[ORM\Column]                // BOOLEAN/TINYINT in database
    private bool $isVerified = false;  // Default false - user must verify email before accessing certain features

    // One-to-Many relationship: ONE user can have MANY configurations
    // mappedBy: 'user' refers to the property in Configuration entity
    // cascade: ['remove'] - if user is deleted, all their configurations are also deleted
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Configuration::class, cascade: ['remove'])]
    private Collection $configurations;  // Collection of Configuration entities belonging to this user

    // Constructor - initializes the configurations collection when user object is created
    public function __construct()
    {
        // Create an empty ArrayCollection to store configurations
        $this->configurations = new ArrayCollection();
    }

    // GETTERS AND SETTERS
    
    /**
     * Get the user ID
     * @return int|null The user's unique identifier
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the user's email
     * @return string|null The email address
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the user's email
     * @param string $email The email address
     * @return static Returns $this for method chaining
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Required by UserInterface - returns the unique identifier for the user
     * Symfony uses this for authentication (usually email or username)
     * @return string The user identifier (email in this case)
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;  // Cast to string to ensure type safety
    }

    /**
     * Get user roles (for authorization)
     * Automatically adds ROLE_USER to every user
     * @return array Array of role strings (e.g., ['ROLE_USER', 'ROLE_ADMIN'])
     */
    public function getRoles(): array
    {
        // Start with stored roles from database
        $roles = $this->roles;
        // EVERY user gets ROLE_USER (guarantees minimum access level)
        $roles[] = 'ROLE_USER';
        // Remove duplicates (in case ROLE_USER was already in $this->roles)
        return array_unique($roles);
    }

    /**
     * Set user roles
     * @param array $roles Array of role strings
     * @return static Returns $this for method chaining
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Required by PasswordAuthenticatedUserInterface - returns the hashed password
     * @return string The hashed password
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the hashed password
     * @param string $password The HASHED password (never store plain text!)
     * @return static Returns $this for method chaining
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Required by UserInterface - clears sensitive data from the user object
     * Called after authentication to clean up any plain-text passwords
     * In this implementation, we don't store any sensitive data beyond password hash
     */
    public function eraseCredentials(): void
    {
        // Clear sensitive data if needed (e.g., plainPassword property if you had one)
    }

    /**
     * Get the user's display name
     * @return string|null The user's name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the user's display name
     * @param string $name The user's name
     * @return static Returns $this for method chaining
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Check if user's email is verified
     * @return bool True if verified, false otherwise
     */
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    /**
     * Set email verification status
     * @param bool $isVerified Whether email is verified
     * @return static Returns $this for method chaining
     */
    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    /**
     * Get all configurations belonging to this user
     * @return Collection<int, Configuration> Collection of Configuration entities
     */
    public function getConfigurations(): Collection
    {
        return $this->configurations;
    }

    /**
     * Add a configuration to this user
     * Maintains both sides of the bidirectional relationship
     * @param Configuration $configuration The configuration to add
     * @return static Returns $this for method chaining
     */
    public function addConfiguration(Configuration $configuration): static
    {
        // Check if configuration is not already in the collection
        if (!$this->configurations->contains($configuration)) {
            // Add to collection
            $this->configurations->add($configuration);
            // Set this user as the owner of the configuration (bidirectional sync)
            $configuration->setUser($this);
        }
        return $this;
    }

    /**
     * Remove a configuration from this user
     * Maintains both sides of the bidirectional relationship
     * @param Configuration $configuration The configuration to remove
     * @return static Returns $this for method chaining
     */
    public function removeConfiguration(Configuration $configuration): static
    {
        // Remove from collection (removeElement returns true if element was found and removed)
        if ($this->configurations->removeElement($configuration)) {
            // If the configuration still points to this user, clear the reference
            if ($configuration->getUser() === $this) {
                $configuration->setUser(null);
            }
        }
        return $this;
    }

    /**
     * Helper method to check if user has admin privileges
     * @return bool True if user has ROLE_ADMIN, false otherwise
     */
    public function isAdmin(): bool
    {
        // Check if ROLE_ADMIN exists in the user's roles
        return in_array('ROLE_ADMIN', $this->getRoles());
    }
}