<?php
/**
 * Role-based Access Control Middleware.
 * This middleware checks if the authenticated user has the required role.
 * It restricts access to certain routes based on user roles.
 */

class RoleMiddleware {
    /**
     * Check if user has the required role for access.
     * @param string $role Required role (e.g., 'librarian')
     * @param object $user User object with role property
     */
    public static function only($role, $user) {
        if ($user->role !== $role) { // Fixed spacing: !==$role → !== $role
            http_response_code(403); // Forbidden
            echo json_encode([
                "message" => "access denied" // Fixed spacing: "message"=>"access denied" → "message" => "access denied"
            ]);
            exit;
        }
    }
}

// Notes:
// - Checks user role to restrict access based on roles.
// - Used after authentication middleware to ensure user is logged in.
// - Allows or denies access to specific roles (e.g., only librarians can access certain endpoints).
?>
