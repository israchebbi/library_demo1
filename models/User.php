<?php
/**
 * User model representing a user entity in the system.
 * This class encapsulates user data and provides a constructor for initialization.
 */
class User {
    // Public properties for user attributes
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $created_at;
    public $updates_at;

    /**
     * Constructor to initialize a User object with provided data.
    */
    public function __construct($name, $email, $password, $role) {
        $this->name = $name;
        $this->email = $email; // Fixed typo: "emial" → "email"
        $this->password = $password; // Fixed syntax: "$$this->password" → "$this->password"
        $this->role = $role;
    }
}
?>
