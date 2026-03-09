<?php
require_once __DIR__ . '/../src/config/database.php';
class BookRepository {
    private $pdo;
    public function __construct(){
        $this->pdo=Database::connect();
    }
    //get all books
    public function getAll(){
        $stmt = $this->pdo->query(" SELECT 
        b.*,

        -- check if book is currently borrowed
        CASE 
            WHEN l.book_id IS NOT NULL 
            THEN 1 
            ELSE 0 
        END AS is_borrowed

    FROM books b

    LEFT JOIN loans l 
        ON b.id = l.book_id 
        AND l.return_date IS NULL");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    //add new book(create)
    public function create($title, $author, $totalCopies = 1, $availableCopies = 1){
        $stmt = $this->pdo->prepare(
            "INSERT INTO books (title, author, total_copies, available_copies) 
              VALUES (:title, :author, :total_copies, :available_copies)"
        );
        return $stmt->execute([
            ':title'           => $title,
            ':author'          => $author,
            ':total_copies'    => (int) $totalCopies,
            ':available_copies'=> (int) $availableCopies
        ]);
    }
    //update book
    public function update($id, $title,$author){
         $stmt = $this->pdo->prepare(
        "UPDATE books SET title = :title, author = :author WHERE id= :id"
         );
         return $stmt->execute([
            ':id' =>$id,
            ':title'=>$title,
            ':author'=>$author
         ]);
    }
    //delete book
    public function delete($id){
        $id = (int) $id; // cast to int to ensure correct binding

        // Delete related loans first to avoid foreign key constraint violation
        $stmtLoans = $this->pdo->prepare("DELETE FROM loans WHERE book_id = :id");
        $stmtLoans->execute([':id' => $id]);

        $stmt = $this->pdo->prepare("DELETE FROM books WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);

        return $result && $stmt->rowCount() > 0; // returns false if no row was deleted
    }
    public function searchBooks($search='' , $availability=''){
        //base query:
        $query= "SELECT  b.*,
            -- check if book is currently borrowed
            CASE 
                WHEN l.book_id IS NOT NULL 
                THEN 1 
                ELSE 0 
            END AS is_borrowed
        FROM books b
        LEFT JOIN loans l 
            ON b.id = l.book_id 
            AND l.return_date IS NULL
             WHERE 1=1";
        $param = [];
        //search filter 
        if(!empty($search)){
            $query .= " AND (b.title LIKE :search OR b.author LIKE :search)";
            $param[':search'] = "%$search%";
        }
        //availabilitty filter:
        if ($availability === 'available'){
            $query .= " AND b.available_copies > 0" ; 
        }elseif ($availability === 'unavailable'){
            $query .= " AND b.available_copies =0";
        }
        //prepare + execute 
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($param);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateCopies($bookId , $copies){
        $stmt = $this->pdo->prepare("
            UPDATE books 
            SET available_copies = :copies 
            WHERE id = :book_id
        ");
        return $stmt->execute([
            ':copies' => $copies,
            ':book_id' => $bookId
        ]);
    }
    //add copies :increase bothe total and available coopies by a given amout
    public function addCopies ($bookId , $amount){
        $amount = (int) $amount;
        if($amount <= 0) return false;
        $stmt = $this->pdo->prepare("
        UPDATE books 
        SET total_copies = total_copies + :amount,
        available_copies = available_copies + :amount
        WHERE id = :book_id");
        return $stmt->execute([
            ':amount' => $amount,
            ':book_id' =>(int) $bookId
        ]);
    }
}

?>