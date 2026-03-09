<?php
require_once __DIR__ .'/../src/config/database.php';
//borrowingRepository hendels databasse operations for borrowings

class BorrowingRepository {
    private $pdo;
    const MAX_BORROW_LIMIT = 3;
    
    public function __construct(){
        $this->pdo = Database::connect();
    }
    //check if a book is already borrowed by user
    public function isBookBorrowed($bookId){
        $stmt = $this->pdo ->prepare(
            "SELECT COUNT(*) FROM 
            loans WHERE book_id = :book_id 
            AND return_date IS NULL"
        );
        $stmt->execute([':book_id' => $bookId]);
        return $stmt->fetchColumn() > 0;
        
    }
    //borrow a book 
    public function borrow($userId, $bookId){
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+7 days'));

        // Check active loans limit
        $activeLoans = $this->countActiveLoans($userId);
        if ($activeLoans >= self::MAX_BORROW_LIMIT){
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            // Insert the loan record
            $stmt = $this->pdo->prepare(
                "INSERT INTO loans (user_id, book_id, loan_date, due_date) 
                VALUES (:user_id, :book_id, :loan_date, :due_date)"
            );
            $result = $stmt->execute([
                ':user_id'   => $userId,
                ':book_id'   => $bookId,
                ':loan_date' => $borrowDate,
                ':due_date'  => $dueDate
            ]);

            // Decrease available copies ONCE - right here inside the transaction
            $stmt2 = $this->pdo->prepare(
                "UPDATE books 
                SET available_copies = available_copies - 1 
                WHERE id = :book_id AND available_copies > 0"
            );
            $stmt2->execute([':book_id' => $bookId]);

            // If no row was updated, no copies were available — roll back
            if ($stmt2->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return $result;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    //check if user borrowed the book:
public function userBorrowedBook($userId, $bookId){
    $stmt = $this->pdo->prepare(
        "SELECT id FROM loans 
         WHERE user_id = :user_id 
         AND book_id = :book_id 
         AND return_date IS NULL"
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':book_id' => $bookId
    ]);

    return $stmt->rowCount() > 0;
}
    //return a book + restore available copies (ONE place only - inside the transaction)
    public function returnBook($userId, $bookId){
        try{
            // Transaction ensures both the loan update AND copies restore succeed together
            $this->pdo->beginTransaction();

            // Step 1: mark the loan as returned
            $stmt = $this->pdo->prepare(
                "UPDATE loans 
                SET return_date = NOW() 
                WHERE user_id = :user_id 
                AND book_id = :book_id 
                AND return_date IS NULL"
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':book_id' => $bookId
            ]);

            // Guard: if no active loan was found, stop here (prevents double-return)
            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();
                return false;
            }

            // Step 2: restore available copies ONCE - only here, nowhere else
            $stmt2 = $this->pdo->prepare(
                "UPDATE books 
                SET available_copies = available_copies + 1 
                WHERE id = :book_id"
            );
            $stmt2->execute([':book_id' => $bookId]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    //get borrowings of one user
    public function  getUserBorrowings($userId){
        $stmt = $this->pdo->prepare(
            "SELECT  l.id as loan_id,
            b.id AS book_id ,
            b.title ,
            l.loan_date,
            l.return_date, 
            l.due_date
            FROM loans l
            JOIN books b ON b.id= l.book_id
            WHERE l.user_id= :user_id
            AND l.return_date IS NULL"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    //view all borrowins (libaraian only)
    public function getAll(){
        $stmt =$this->pdo->query(
            "SELECT users.name, books.title , loans.loan_date, loans.return_date, loans.due_date
            FROM loans
            JOIN users ON users.id = loans.user_id
            JOIN books ON books.id = loans.book_id"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
// Count active loans for a user
public function countActiveLoans($userId){
    $stmt =$this->pdo->prepare(
        "SELECT COUNT(*) FROM loans
        WHERE user_id = :user_id
        AND return_date IS NULL"
    );
    $stmt->execute([':user_id'=> $userId]);
    return (int)$stmt->fetchColumn();
}
public function getAllLoans(){
    $stmt = $this->pdo->query(
        "SELECT u.name, b.title, l.loan_date, l.due_date, l.return_date
        FROM loans l 
        JOIN users u ON l.user_id = u.id
        JOIN books b ON l.book_id = b.id
        ORDER BY l.loan_date DESC"
    );
    return $stmt-> fetchAll(PDO::FETCH_ASSOC);
}

public function hasAvailableCopies($bookId){
    $stmt = $this->pdo->prepare(
        "SELECT available_copies FROM books WHERE id= :book_id"
    );
    $stmt->execute([':book_id' => $bookId]);
    $copies= $stmt->fetchColumn();
    return $copies >0;
}
public function decreaseAvailableCopies($bookId){
    $stmt = $this->pdo->prepare(
        "UPDATE books b 
        SET b.available_copies = b.available_copies - 1 
        WHERE id = :book_id "
    );
    return $stmt->execute([':book_id' => $bookId]);
}
public function increaseAvailableCopies($bookId){
    // Cap at total_copies so available_copies can never exceed the real total
    $stmt = $this->pdo->prepare(
        "UPDATE books
        SET available_copies = LEAST(available_copies + 1, total_copies)
        WHERE id = :book_id"
    );
    return $stmt->execute([':book_id' => $bookId]);
}
//get all overdue loans (admin only)
public function getOverdueLoans(){
    $stmt = $this->pdo->query(
        "SELECT 
            l.id as loan_id,
            u.id as user_id,
            u.name as user_name,
            u.email as user_email,
            b.id as book_id,
            b.title as book_title,
            l.loan_date,
            l.due_date,
            DATEDIFF(CURDATE(), l.due_date) as days_overdue
        FROM loans l
        JOIN users u ON l.user_id = u.id
        JOIN books b ON l.book_id = b.id
        WHERE l.return_date IS NULL 
        AND l.due_date < CURDATE()
        ORDER BY days_overdue DESC"
    );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
//get overdue count (for quick stats)
public function getOverdueCount(){
    $stmt = $this->pdo->query(
        "SELECT COUNT(*) FROM loans
        WHERE return_date IS NULL 
        AND due_date < CURDATE()"
    );
    return (int) $stmt->fetchColumn();
}


}
?>