<?php
require_once __DIR__ . '/../src/config/database.php';
class BooksController {
    //Get /books
    public static function index(){
        $pdo = Database::connect();
        $stm = $pdo->query("SELECT * FROM books");
        $books = $stm->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            "status" => "success",
            "data" => $books
        ]);
        }
        //post books(librarian only)
        public static function store($userRole){
            if($userRole !== 'librarian'){
                echo json_encode([
                    "status" => "error",
                    "message" => "access denied"
                ]);
                return;
            }
            $data = json_decode(file_get_contents("php//input"),true);
            if(empty($data['title']) || empty($data['author'])){
                echo json_encode([
                    "status" => "error",
                    "message" => "title and author required"
                ]);
                return;
        }
        $pdo = Database::connect();
        $stmt = $pdo->prepare("INSERT INTO books (title, author) VALUES (:title, :author)");
        $stmt->execute([
            ':title' => $data['title'],
            ':author' => $data['author']
        ]);
        echo json_encode([
            "status" => "success",
            "message" => "book added successfully"
        ]);
}
//put /books (librarian only)
public static function update($userRole){
    if($userRole !== 'librarian'){
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
    $pdo = Database::connect();
    $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author WHERE id = :id");
    $stmt->execute([
        ':id' => $data['id'],
        ':title' => $data['title'],
        ':author' => $data['author']
    ]);
    echo json_encode([
        "status" => "success",
        "message" => "book updated successfully"
    ]);
}
//delete /books (librarian only)
public static function delete($userRole){
    if($userRole !== 'librarian'){
        echo json_encode([
            "status" => "error",
            "message" => "access denied"
        ]);
        return;
    }
    $data = json_decode(file_get_contents("php://input"), true);
    if(empty($data['id'])){
        echo json_encode([
            "status" => "error",
            "message" => "id required"
        ]);
        return;
    }
    $pdo = Database::connect();
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = :id");
    $stmt->execute([
        ':id' => $data['id']
    ]);
    echo json_encode([
        "status" => "success",
        "message" => "book deleted successfully"
    ]);
}


}
?>