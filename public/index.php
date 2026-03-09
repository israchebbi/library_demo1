<?php
/**
 * Main API Entry Point for Library Management System.
 * This file handles routing for login and protected endpoints.
 * It uses JWT for authentication and role-based access control.
 */

// Buffer ALL output so no stray whitespace/warnings leak before our JSON
ob_start();

// Set response header to JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Load required libraries and configuration
require_once __DIR__ . '/../controllers/BorrowingController.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoloader for dependencies
require_once __DIR__ . '/../src/config/database.php'; // Database connection
require_once __DIR__ . '/../src/helpers/jwt.php'; // JWT helper functions
require_once __DIR__ . '/../controllers/AuthController.php'; // Auth controller for login
require_once __DIR__ . '/../controllers/BooksController.php'; // Books controller for book management(router)

// Get HTTP method and request path
$method = $_SERVER['REQUEST_METHOD']; // e.g., POST, GET
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // e.g., /login, /profile

// REMOVE PROJECT FOLDER FROM PATH (IMPORTANT)
$path = str_replace('/backend_project/public', '', $path);

//////////////////////////////////////////////////
// LOGIN ROUTE (PUBLIC - NO TOKEN REQUIRED)
//////////////////////////////////////////////////
if ($path === '/register' && $method === 'POST'){
    AuthController::register();
    exit;
}

if ($method === 'POST' && $path === '/login') {
    AuthController::login();
    exit;
}

//////////////////////////////////////////////////
// HELPER FUNCTION: EXTRACT TOKEN FROM HEADER
//////////////////////////////////////////////////

function getBearerToken() {
    $headers = getallheaders();
    return $headers['Authorization'] ?? null;
}

//AUTH CHECK
$authHeader = getBearerToken();
if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')){
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "token missing"
    ]);
    exit;
}

// Get token from request header
$token = str_replace("Bearer ", "", $authHeader);

try {
    // Verify and decode JWT token
    $decoded = verifyJWT($token);

    // Extract user data from token payload
    $userId = $decoded->data->id;
    $userRole = $decoded->data->role;

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "invalid token"
    ]);
    exit;
}

//////////////////////////////////////////////////
// PROTECTED ROUTES
//////////////////////////////////////////////////

//profile
if ($method === 'GET' && $path === '/profile') {
    ob_clean();
    echo json_encode([
        "status" => "success",
        "message" => "access granted",
        "role" => $userRole
    ]);
    exit;
}

//search books route — must be BEFORE GET /books to avoid being shadowed
if ($method === 'GET' &&  $path === '/books/search'){
    ob_clean();
    BooksController::search();
    exit;
}

//GET all books (student and librarian)
if ($method === 'GET' && $path === '/books'){
    ob_clean();
    BooksController::index($userRole);
    exit;
}

//ADD book (librarian only)
if ($method === 'POST' && $path === '/books'){
    ob_clean();
    BooksController::store($userRole);
    exit;
}

//UPDATE book (librarian only)
if ($method === 'PUT' && $path === '/books'){
    ob_clean();
    BooksController::update($userRole);
    exit;
}


//ADD COPIES to a book (admin only)
if ($method === 'PATCH' && $path === '/books/copies'){
    ob_clean();
    BooksController::addCopies($userRole);
    exit;
}

//DELETE book (librarian only)
if ($method === 'DELETE' && preg_match('#^/books/(\d+)$#', $path, $matches)){
    ob_clean();
    BooksController::delete($matches[1], $userRole);
    exit;
}

//BORROWING - borrow a book
if ($method === 'POST' && $path === '/borrow'){
    ob_clean();
    BorrowingController::borrow($userId, $userRole);
    exit;
}

//BORROWING - return a book
if ($method === 'POST' && $path === '/return'){
    ob_clean();
    BorrowingController::returnBook($userId, $userRole);
    exit;
}

//student view his own borrowings
if ($method === 'GET' && $path === '/myBorrowings') {
    ob_clean();
    BorrowingController::myBorrowings($userId);
    exit;
}

//VIEW BORROWINGS (librarian only)
if ($method === 'GET' && $path === '/borrowings'){
    ob_clean();
    BorrowingController::index($userRole);
    exit;
}

//ADMIN LOANS - view all loans
if ($method === 'GET' && $path === '/adminLoans'){
    ob_clean();
    BorrowingController::adminLoans();
    exit;
}
//get overdue loans (admin only)
if ($method === 'GET' && $path === '/overdueLoans'){
    ob_clean();
    BorrowingController::overdueLoans();
    exit;
}
//profile route
if ($method === 'GET' && $path === '/profile') {
    ob_clean();
    AuthController::profile($userId);
    exit;
}


// If no route matches, return 404
ob_clean();
http_response_code(404);
echo json_encode([
    "status" => "error",
    "message" => "endpoint not found"
]);

?>