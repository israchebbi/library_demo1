<?php
/**
 * UserRepository class for handling user-related database operations.
 * This repository pattern separates data access logic from business logic.
 * It provides methods to find and create users securely.
 */

require_once '../src/config/database.php'; // Fixed path: '../config/database.php' → '../src/config/database.php'
require_once '../models/User.php';
class UserRepository {
    private $db; // Private property to hold database connection

    /**
     * Constructor to initialize database connection.
     */
    public function __construct() {
        $this->db = Database::connect(); // Fixed class name: 'database' → 'Database'
    }

    /**
     * Find a user by email (used for login process).
    
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email"; // :email is a placeholder to prevent SQL injection
        $stmt = $this->db->prepare($sql); // Fixed variable name: 'stm' → 'stmt'
        // Execute with safe parameter binding
        $stmt->execute([':email' => $email]);
        // Return user data as associative array or false if not found
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new user (used for registration).

     */
    public function create( User $user) {
        $sql = "INSERT INTO users (name, email, password, role) " .// Fixed typo: "ISERT" → "INSERT"
               "VALUES (:name, :email, :password, :role)"; // Fixed spacing and formatting
        $stmt = $this->db->prepare($sql); // Fixed variable name: 'stm' → 'stmt'
        return $stmt->execute([
            ':name' => $user->name,
            ':email' => $user->email,
            ':password' => $user->password,
            ':role' => $user->role
        ]);
    }
    public function getUserById($userId){
        $stmt = $this->db->prepare("
            SELECT id, name , email , role FROM users WHERE user_id= :user_id");
            $stmt ->execute([':user_id' =>$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Notes:
// - This repository is called by controllers to interact with user data in the database.
// - Using prepared statements with parameter binding provides security against SQL injection.
// - PDO::FETCH_ASSOC returns data as an associative array for easy access.
?>
