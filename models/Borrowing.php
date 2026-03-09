<?php

class Borrowing {
    private $db;
    public function __construct($db) {
        $this->$db;
    }
    //borrow a book
    public function borrow($userId, $bookId){
        $sql="INSERT INTO loans(user_id , book_id, loan_date) 
            VALUES (:user_id , :book_id , NOW())";
        $stmt=$this->db->prepare($sql);
        return $stmt->execute([$userId , $bookId]);
    }
    //return a book
    public function returnBook($userId, $bookId) {
        $sql=" UPDATE loans SET return_date= NOW()
        WHERE user_id= :user_id AND book_id= :book_id AND return_date IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $bookId]);
    }
}
//keep borrow history 
//prevent double return 
?>