<?php
/**
 * Database configuration and connection class.
 * This class provides a static method to establish a connection to the MySQL database.
 * It uses PDO (PHP Data Objects) for secure and efficient database interactions.
 */
class Database { // Fixed class name: 'database' → 'Database' for consistency
    /**
     * Static method to connect to the database.
     * Can be called without instantiating the class.
     * @return PDO Database connection object
     */
    public static function connect() {
        // Database configuration
        $host = "localhost"; // Database host
        $dbname = "library_db"; // Database name
        $username = "root"; // Database username
        $password = ""; // Database password (empty for local development)

        // Try block to attempt database connection
        try {
            // Create PDO connection with DSN (Data Source Name)
            $db = new PDO(
                "mysql:host=" . $host . ";dbname=" . $dbname,
                $username,
                $password
            );
            // Set PDO attribute to throw exceptions on errors for easier debugging
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Return the database connection object
            return $db;
        // Catch block to handle connection errors
        } catch (PDOException $e) {
            // Terminate script and return JSON error if connection fails
            die(json_encode([
                "status" => "error",
                "message" => "Database connection failed: " . $e->getMessage() // Added error details for debugging
            ]));
        }
    }
}

// Notes:
// - try/catch prevents application crash on connection failure.
// - PDO: PHP Data Objects - provides a consistent interface for database access.
// - PDOException: Exception class for PDO-related errors.
// - PDO::ATTR_ERRMODE: Attribute to set error mode.
// - PDO::ERRMODE_EXCEPTION: Throws exceptions instead of warnings for better error handling.
// - MySQL: Relational database management system.
// - PDO helps prevent SQL injection by using prepared statements and parameter binding.
?>
