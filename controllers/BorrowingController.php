<?php
//buisness logic for borrowing system
require_once __DIR__ . '/../repositories/BorrowingRepository.php';

class BorrowingController {
    //borrow a book
    public static function borrow($userId , $userRole){  

    //only students can borrow books
        if ($userRole !== 'student'){
            echo json_encode ([
                "status" => "error",
                "message" => "only students can borrow books"
            ]);
            return;
        }
        $data =json_decode(file_get_contents("php://input"), true);
        $bookId = $data['book_id'] ?? null;
        if (!$bookId) {
            echo json_encode ([
               "status" => "error",
               "message" => "book_id required" 
            ]);
            return;
        }
        $repo = new BorrowingRepository();
        //check if book is already borrowed(protect against double borrowing)
        if($repo->userBorrowedBook($userId, $bookId)) {
            echo json_encode([
                "status" => "error",
                "message" => "book already borrowed"
            ]);
            return ;
        }
        //check borrow limits
        if ($repo->countActiveLoans($userId) >= 3){
            echo json_encode([
                "status" => "error",
                "message" =>"Borrow limit reached (maximum 3 books)"
            ]);
            return;
        }
        //check if book has available copies
        if (!$repo->hasAvailableCopies($bookId)){
            echo json_encode([
                "status" => "error",
                "message" => "no available copies"
            ]);
            return;
        }
        //proceed to borrow - both operations together
        // repo->borrow() already handles decreasing copies inside its transaction
        $borrowSuccess = $repo->borrow($userId, $bookId);
        
        if ($borrowSuccess) {
            echo json_encode ([
                "status" => "success", 
                "message" => "book borrowed successfully"
            ]);
        } else {
            echo json_encode ([
                "status" => "error", 
                "message" => "failed to borrow book"
            ]);
        }
    }
    //return book 
public static function returnBook($userId, $role){
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['book_id'])) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "book_id missing"
        ]);
        return;
    }

    $repo = new BorrowingRepository();

    $success = $repo->returnBook(
        $userId,
        $data['book_id']
    );

    // repo->returnBook() already handles restoring copies inside its transaction
    if ($success){
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "book returned successfully"
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "failed to return book"
        ]);
    }
}

    //view own borrowings 
    public static function myBorrowings($userId){
        $repo = new BorrowingRepository();
        $borrowings = $repo->getUserBorrowings($userId);
        echo json_encode([
            "status" => "success",
            "data" => $borrowings
        ]);
    }
    //view borrowings (librarian only)
    public static function index($userRole){
        if ($userRole !== 'admin'){
            echo json_encode ([
                "status" => "error",
                "message" => "only librarians can view borrowings"
            ]);
            return;
        }
        $repo = new BorrowingRepository();
        echo json_encode ([
            "status" => "success",
            "data" => $repo->getAll()
        ]);
    }
    //borrow book endpoint
   /* public function borrowBook(){
        $data = json_decode(file_get_contents("php://input"), true);
        $userId= $date['user_id'];
        $bookId = $data['book_id'];
        $result = $this->repo->borrowBook($userId, $bookId);
        echo json_encode([
            "success" => $result
        ]);
    }
    //return endpoint
    public function return()
{
    $data = json_decode(file_get_contents("php://input"), true);
    //$this->repo->increaseAvailableCopies($data['book_id']);

    $borrowId = $data['borrow_id'];

    $result = $this->repo->returnBookByID($borrowId);

    echo json_encode([
        "success" => $result
    ]);
}*/
//history endpoint
    /* public function myBorrowingshistory(){
        $userId = $_GET['user_id'];
        $data = $this->repo->getUserBorrowingHistory($userId);
        echo json_encode([
            $data
        ]);
     }*/
     public static function adminLoans(){
        $repo = new BorrowingRepository();
        $data = $repo->getAllLoans();

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
     }
     //get overdueloans (admin only)
     public static function overdueLoans($userRole = null){
        if ($userRole !== 'admin'){
            echo json_encode([
                "status" => "error",
                "message" => "only admins can view overdue loans"
            ]);
            return ; 
        }
        $repo = new BorrowingRepository();
        $overdueLoans = $repo->getOverdueLoans();
        $overdueCount = $repo->getOverdueCount();
        echo json_encode([
            "status" => "success",
            "data" => [
                "overdue_count" => $overdueCount,
                "overdue_loans" => $overdueLoans
            ]
        ]);
     }
}



?>