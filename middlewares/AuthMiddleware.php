<?php
/**
 * Authentication Middleware for protecting routes.
 * This middleware checks for valid JWT tokens in the Authorization header.
 * It ensures that only authenticated users can access protected endpoints.
 */

require_once '../vendor/autoload.php';
require_once '../src/helpers/jwt.php'; // Fixed path: '../config/jwt.php' → '../src/helpers/jwt.php'

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    /**
     * Check for valid JWT token in Authorization header.
     * @return object Decoded user data from token
     */
    public static function check() {
        // Read Authorization header
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401); // Unauthorized
            echo json_encode(["message" => "token missing"]);
            exit;
        }

        // Extract token (remove 'Bearer ' prefix if present)
        $token = str_replace("Bearer ", "", $headers['Authorization']); // Fixed: "bearer" → "Bearer ", added space

        try {
            // Decode and verify token
            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256')); // Fixed: 'JWTConfig::$secret' → 'JWT_SECRET_KEY'
            // Return user data from token payload
            return $decoded->data; // Fixed: 'user' → 'data' to match payload structure

        } catch (Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(["message" => "invalid token"]);
            exit;
        }
    }
}

// Notes:
// - This middleware is used to protect routes that require authentication.
// - It blocks unauthorized users by checking for valid JWT tokens.
// - Centralizes security logic and runs before controllers.
// - Returns user data (id, role) for use in protected routes.
?>
