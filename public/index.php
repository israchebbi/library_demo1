<?php
/**
 * Main API Entry Point for Library Management System.
 * This file handles routing for login and protected endpoints.
 * It uses JWT for authentication and role-based access control.
 */

// Set response header to JSON
header("Content-Type: application/json");

// Load required libraries and configuration
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader for dependencies
require_once __DIR__ . '/../src/config/database.php'; // Database connection
require_once __DIR__ . '/../src/helpers/jwt.php'; // JWT helper functions
require_once __DIR__ .'../controllers/AuthController.php'; // Auth controller for login
require_once __DIR__ . '/../controllers/BooksController.php'; // Books controller for book management(router)
// Get HTTP method and request path
$method = $_SERVER['REQUEST_METHOD']; // e.g., POST, GET
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // e.g., /login, /profile

//////////////////////////////////////////////////
// LOGIN ROUTE (PUBLIC - NO TOKEN REQUIRED)
//////////////////////////////////////////////////
if ($method === 'POST' && $path === '/login') {
    // Read JSON data from request body
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode([
            "status" => "error",
            "message" => "email and password required"
        ]);
        exit;
    }

    $email = $data['email'];
    $password = $data['password'];

    // Establish database connection
    $pdo = Database::connect(); // Fixed: Use Database::connect() instead of undefined $pdo

    // Prepare and execute query to find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);

    // Fetch user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "invalid credentials"
        ]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            "status" => "error",
            "message" => "invalid credentials"
        ]);
        exit;
    }

    // Generate JWT token
    $token = createJWT($user);

    // Return success response with token
    echo json_encode([
        "status" => "success",
        "token" => $token,
        "role" => $user['role']
    ]);
    exit;
}

//////////////////////////////////////////////////
// HELPER FUNCTION: EXTRACT TOKEN FROM HEADER
//////////////////////////////////////////////////
/**
 * Extract Bearer token from Authorization header.
 * @return string|null Token string or null if not found
 */
function getBearerToken() {
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        return null;
    }

    return str_replace('Bearer ', '', $headers['Authorization']);
}

//////////////////////////////////////////////////
// PROTECTED ROUTES (TOKEN REQUIRED)
//////////////////////////////////////////////////
// Get token from request header
$token = getBearerToken();

if (!$token) {
    echo json_encode([
        "status" => "error",
        "message" => "token missing"
    ]);
    exit;
}

try {
    // Verify and decode JWT token
    $decoded = verifyJWT($token);

    // Extract user data from token payload
    $userId = $decoded->data->id;
    $userRole = $decoded->data->role;

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "invalid token"
    ]);
    exit;
}

//////////////////////////////////////////////////
// PROTECTED PROFILE ROUTE
//////////////////////////////////////////////////
if ($method === 'GET' && $path === '/profile') {
    echo json_encode([
        "status" => "success",
        "message" => "access granted",
        "role" => $userRole
    ]);
    exit;
}


//GET all books (student and librarian)
if ($method === 'GET' && $path === '/books'){
    $pdo = Database::connect();

    $stmt = $pdo->query("SELECT * FROM books");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $books
    ]);
    exit;
}
///ADD book (librarian only)
if ($method ==='POST' && $path ==='/books'){

    if($userRole !== 'librarian'){
        echo json_encode([
            "status" => "error",
            "message" => "access denied"
        ]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"),true);

    if(empty($data['title']) || empty($data['author'])){
        echo json_encode([
            "status" => "error",
            "message" => "title and author required"
        ]);
        exit;
    }

    $pdo = Database::connect();
    $stmt =$pdo->prepare("INSERT INTO books (title, author) VALUES (:title, :author)");
    $stmt->execute([
        ':title' => $data['title'],
        ':author' => $data['author']
    ]);
    echo json_encode([
        "status" => "success",
        "message" => "books added successfuly"
    ]);
    exit;
}
//UPDATE vookk (librarian only)

if ($method === 'PUT' && $path=== '/books'){
    if ($userRole !== 'librarian'){
        echo json_encode([
            "status" => "error",
            "message" => "access denied"
        ]);
        exit;
}
$data = json_decode(file_get_contents("php://input"), true);
if (empty($data['id'])|| empty($data['title']) || empty($data['author'])){
    echo json_encode ([
        "status" => "error",
        "message"=> "id, title and author required"
    ]);
    exit;
}
$pdo=Database::connect();
$stmt = $pdo->prepare(
    "UPDATE books SET title = :title, author = :author WHERE id =:id"
);
$stmt->execute([
    ':id' => $data['id'],
    ':title' => $data['title'],
    ':author' => $data['author']
]);
echo json_encode ([
    "status" => "success",
    "message" => "books update successfully"
]);
exit;
}
///DELETE book (librarian only)
if ($method === 'DELETE' && $path =='/books'){
    if ($userRole !== 'librarian'){
        echo json_encode([
            "status" => "error",
            "message" => "access denied"            
        ]);
        exit;
    }
    $data =json_decode(file_get_contents("php://input"), true);

    if (empty($data['id'])){
        echo json_encode([
            "status" => "error",
            "message" => "id required"
        ]);
        exit;
    }
    $pdo =Database::connect();
    $stmt =$pdo->prepare("DELETE FROM books WHERE id = :id");
    $stmt->execute([':id'=> $data['id']]);
    echo json_encode ([
        "status" => "success",
        "message" => "book deleted successfully"
    ]);
    exit;
}








// If no route matches, return 404
http_response_code(404);
echo json_encode([
    "status" => "error",
    "message" => "endpoint not found"
]);

?>
