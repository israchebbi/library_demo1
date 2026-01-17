<?php
/**
 * Authentication Controller for handling login logic.
 * This controller manages user authentication processes, including login validation.
 * It interacts with the UserRepository to access user data.
 */

require_once '../repositories/UserRepository.php'; // Fixed path: '../repositories/UserRepository.php' (assuming correct relative path)

class AuthController {
    /**
     * Handle user login process.
     * Validates input, checks user existence, verifies password, and returns user data on success.
     */
    public static function login() {
        // Get JSON data from request body (e.g., from Angular or Postman)
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate input fields
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'email and password required'
            ]);
            return;
        }

        $email = $data['email'];
        $password = $data['password'];

        // Initialize UserRepository and find user by email
        $userRepo = new UserRepository();
        $user = $userRepo->findByEmail($email);

        // Check if user exists
        if (!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'user not found'
            ]);
            return;
        }

        // Verify hashed password
        if (!password_verify($password, $user['password'])) { // Fixed spacing: ($password , $user['password']) → ($password, $user['password'])
            echo json_encode([
                'status' => 'error',
                'message' => 'invalid password'
            ]);
            return;
        }

        // Successful login - return user data
        echo json_encode([
            'status' => 'success',
            'user' => [
                'id' => $user['id'], // Fixed spacing: $user ['id'] → $user['id']
                'name' => $user['name'], // Fixed spacing: 'name ' → 'name'
                'email' => $user['email'],
                'role' => $user['role'] // Fixed spacing: $user ['role'] → $user['role']
            ]
        ]);
    }
}

// Notes:
// - This controller handles HTTP logic for authentication.
// - It processes login requests and returns appropriate JSON responses.
// - Uses UserRepository for database interactions to maintain separation of concerns.
?>
