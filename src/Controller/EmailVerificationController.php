<?php
// Namespace declaration - organizes the controller within the App\Controller namespace
namespace App\Controller;

// Import User entity to work with user account data
use App\Entity\User;
// Import UserRepository to query user data from database
use App\Repository\UserRepository;
// Import Doctrine's EntityManager for database operations (update user verification status)
use Doctrine\ORM\EntityManagerInterface;
// Import Symfony's base controller class for common controller functionality
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Import Request object to access HTTP request data (URL parameters, etc.)
use Symfony\Component\HttpFoundation\Request;
// Import Response object to send HTTP responses back to the client
use Symfony\Component\HttpFoundation\Response;
// Import Route attribute for defining URL routes
use Symfony\Component\Routing\Annotation\Route;
// Import exception interface for handling email verification errors
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
// Import helper service for generating and validating email verification links
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

// EmailVerificationController - handles email verification functionality
// This ensures users confirm their email address before accessing certain features
class EmailVerificationController extends AbstractController
{
    // Property to hold the VerifyEmailHelper service (for signed URL generation/validation)
    private VerifyEmailHelperInterface $verifyEmailHelper;
    // Property to hold the EntityManager for database operations
    private EntityManagerInterface $entityManager;

    // Constructor - called when controller is instantiated
    // Both dependencies are injected automatically by Symfony
    public function __construct(
        VerifyEmailHelperInterface $verifyEmailHelper,  // Service for email verification
        EntityManagerInterface $entityManager           // Doctrine entity manager
    ) {
        // Store injected dependencies in class properties
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->entityManager = $entityManager;
    }

    // Route for verifying email address (when user clicks the link in email)
    // - Path: '/verify/email'
    // - Name: 'app_verify_email'
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        // Extract 'id' parameter from the URL query string
        // Example URL: /verify/email?id=42&expires=123456&signature=abc...
        $id = $request->get('id');
        
        // Check if ID parameter exists in the URL
        if (null === $id) {
            // No ID provided - redirect to registration page
            return $this->redirectToRoute('app_register');
        }

        // Find user by ID from the database
        $user = $userRepository->find($id);

        // Check if user exists
        if (null === $user) {
            // User not found - redirect to registration page
            return $this->redirectToRoute('app_register');
        }

        // Attempt to validate the email confirmation link
        // This checks the signature, expiration, and ensures the link is valid
        try {
            // Validate the confirmation link:
            // Parameters:
            // 1. Full URI of the request (contains signature and expiration)
            // 2. User ID
            // 3. User email (ensures link is for the correct user)
            $this->verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            // Validation failed - link is invalid, tampered with, or expired
            $this->addFlash('error', 'The verification link is invalid or expired. Please try registering again.');
            // Redirect to registration page to try again
            return $this->redirectToRoute('app_register');
        }

        // Link is valid - mark user as verified in the database
        $user->setIsVerified(true);
        // Save changes to database (UPDATE query)
        $this->entityManager->flush();

        // Add success message for the user
        $this->addFlash('success', 'Your email has been verified! You can now log in.');

        // Redirect to login page so user can sign in
        return $this->redirectToRoute('app_login');
    }

    // Route for resending verification email (if user didn't receive or link expired)
    // - Path: '/verify/resend'
    // - Name: 'app_verify_resend'
    #[Route('/verify/resend', name: 'app_verify_resend')]
    public function resendVerificationEmail(Request $request): Response
    {
        // Security check: only logged-in users can request a new verification email
        // User must be authenticated to resend (they have an account already)
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Get the currently logged-in user
        $user = $this->getUser();
        
        // Check if user is already verified
        if ($user->isVerified()) {
            // User already verified - inform them and redirect to dashboard
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Generate a new signed verification link
        // Parameters for generateSignature():
        // 1. Route name for verification (app_verify_email)
        // 2. User ID (will be added to URL)
        // 3. User email (encrypted in signature to prevent tampering)
        // 4. Additional parameters to include in URL (id)
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',     // Route to link to
            $user->getId(),          // User ID for URL
            $user->getEmail(),       // Email for signature
            ['id' => $user->getId()] // Extra URL parameters
        );

        // Extract the full signed URL from signature components
        // URL format: /verify/email?id=42&expires=1234567890&signature=hashed_value
        $verificationUrl = $signatureComponents->getSignedUrl();
        
        // For demonstration/development: display the verification link
        // IN PRODUCTION: You would send this URL via email, NOT display it on screen
        $this->addFlash('info', 'Click this link to verify your email: ' . $verificationUrl);
        
        // Redirect user back to dashboard
        return $this->redirectToRoute('app_dashboard');
    }
}