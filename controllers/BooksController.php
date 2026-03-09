<?php
//require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../repositories/BooksRepository.php';
class BooksController {
    //Get /books
    public static function index($userRole){
//create repository instance
    $repo= new BookRepository;
    //fetch all books from database
    $books = $repo->getAll();
        echo json_encode([
            "status" => "success",
            "data" => $books
        ]);
        }
        //post books(librarian only)
        public static function store($userRole){
            //security :
            if($userRole !== 'admin'){
                http_response_code(403);
                echo json_encode([
                    "status" => "error",
                    "message" => "access denied"
                ]);
                return;
            }
            //read json data from angular
            $data = json_decode(file_get_contents("php://input"),true);
            //basi validation
            if(empty($data['title']) || empty($data['author'])){
                http_response_code(400);
                echo json_encode([
                    "status" => "error",
                    "message" => "title and author required"
                ]);
                return;
        }
        //create repository instance
        $repo= new BookRepository();
        //insert book with copies
        $totalCopies     = isset($data['total_copies'])     ? (int)$data['total_copies']     : 1;
        $availableCopies = isset($data['available_copies']) ? (int)$data['available_copies'] : 1;
        $repo->create($data['title'], $data['author'], $totalCopies, $availableCopies);
        //return response
        echo json_encode([
            "status" => "success",
            "message" => "book added successfully"
        ]);
}

//put /books (librarian only)
public static function update($userRole){
    if($userRole !== 'admin'){
        echo json_encode([
            "status" => "error",
            "message" => "access denied"
        ]);
        return;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if(empty($data['id']) || empty($data['title']) || empty($data['author'])){
        echo json_encode([
            "status" => "error",
            "message" => "id, title and author required"
        ]);
        return;
    }
    //create repository instance
    $repo= new BookRepository();
    //update book
    $repo->update($data['id'] , $data['title'], $data['author']);
    echo json_encode([
        "status" => "success",
        "message" => "book updated successfully"
    ]);
}
//delete /books (librarian only)
public static function delete($id, $userRole){
    //header('Content-Type: application/json');
    if($userRole !== 'admin'){
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Forbidden"
        ]);
        return;
    }
    //validate Id from URL
    //$data = json_decode(file_get_contents("php://input"), true);
    if(empty($id)){
        http_response_code(400);//bad request
        echo json_encode([
            "status" => "error",
            "message" => "id required"
        ]);
        return;
    }
    //create repository instance
    $repo = new BookRepository();
    //delete book
    $deleted = $repo->delete($id);
    if (!$deleted) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "book not found or could not be deleted"
        ]);
        return;
    }
    // Return response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "book deleted successfully"
    ]);
    exit;
}

// PATCH /books/copies (admin only) — add copies to a book
public static function addCopies($userRole) {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'access denied']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || !isset($data['amount']) || (int)$data['amount'] <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'id and a positive amount are required']);
        return;
    }
    $repo = new BookRepository();
    $ok = $repo->addCopies($data['id'], $data['amount']);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'could not update copies']);
        return;
    }
    echo json_encode(['status' => 'success', 'message' => 'copies updated successfully']);
}
public static function search(){
    $repo = new BookRepository();
    //read query parameters 
    $search = $_GET['search'] ?? '';
    $availability = $_GET['availability'] ?? 'all';

    $books = $repo->searchBooks($search, $availability);
    echo json_encode([
        "status" => "success",
        "data" => $books
    ]);
    }


}
//userRole comes from the JWT
//"php://input"->reads json fron angular
//the controllers does the logic not the sql
?>