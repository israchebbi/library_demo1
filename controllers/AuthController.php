<?php
/**
 * Authentication Controller for handling login logic.
 * This controller manages user authentication processes, including login validation.
 * It interacts with the UserRepository to access user data.
 */

require_once __DIR__ . '/../repositories/UserRepository.php'; // Fixed path: '../repositories/UserRepository.php' (assuming correct relative path)
require_once __DIR__ . '/../src/helpers/jwt.php'; 
require_once __DIR__ . '/../models/User.php';
class AuthController {
    /**
     * Handle user login process.
     * Validates input, checks user existence, verifies password, and returns user data on success.
     */
    //register 
    public static function register(){
        //read jsin input 
        $data = json_decode(file_get_contents('php://input'), true);
        //validate input 
        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role'])){
            echo json_encode ([
                "status" => "error",
                "message" => "name, email, password and role required"
            ]);
            return;
        }
        $name=$data['name'];
        $email=$data['email'];
        $password=$data['password'];
        $role=$data['role'];
        //hash password 
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        //save user to database
        $userRepo = new UserRepository();
        //check if email already exists
        if ($userRepo->findByEmail($email)){
            echo json_encode ([
                "status" => "error",
                "message" => "email already exists"
            ]);
            return;
    }
    //create user//this varable $repo is not defined how o fix it 
    $user = new User($name, $email, $hashedPassword , $role);
    $userRepo->create($user);
    //return success response
    echo json_encode ([
        "status" => "success",
        "message" => "user registered successfully"
    ]); 
    
    }


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
                'message' => 'invalid credentials'
            ]);
            exit;
        }

        // Verify hashed password
        if (!password_verify($password, $user['password'])) { // Fixed spacing: ($password , $user['password']) → ($password, $user['password'])
            echo json_encode([
                'status' => 'error',
                'message' => 'invalid password'
            ]);
            exit;
        }
        $token = createJWT($user);

        // Successful login - return user data
        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'role' => $user['role']
        ]);
        exit;
    }
    public function profile($userId){
        $repo = new UserRepository();
        $user = $repo->getUserById($userId);

        if(!$user){
            echo json_encode([
                "status" => "error",
                "message" => "user not found"
            ]);
            return;
        }
        echo json_encode([
            "status" => "success",
            "message" =>'access granted',
            "data" => [
            'id'         => $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'created_at' => $user['created_at']
        ]
        ]);
    }
}

// Notes:
// - This controller handles HTTP logic for authentication.
// - It processes login requests and returns appropriate JSON responses.
// - Uses UserRepository for database interactions to maintain separation of concerns.
?>
