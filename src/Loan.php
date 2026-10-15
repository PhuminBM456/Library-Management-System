<?php
require_once 'Database.php';

class Loan
{
    const LOAN_DAYS = 7;

    public static function borrow(int $bookId, int $memberId): void
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT available_copies FROM books WHERE id = ? FOR UPDATE');
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book || $book['available_copies'] < 1) {
                throw new Exception('หนังสือเล่มนี้ไม่ว่าง');
            }

            $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE id = ?')
                ->execute([$bookId]);
            $pdo->prepare('INSERT INTO loans (book_id, member_id, due_date) VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))')
                ->execute([$bookId, $memberId, self::LOAN_DAYS]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function returnBook(int $loanId): void
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT book_id FROM loans WHERE id = ? AND returned_at IS NULL FOR UPDATE');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception('ไม่พบรายการยืม หรือคืนไปแล้ว');
            }

            $pdo->prepare('UPDATE loans SET returned_at = NOW() WHERE id = ?')->execute([$loanId]);
            $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE id = ?')
                ->execute([$loan['book_id']]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function active(): array
    {
        return Database::connect()->query(
            'SELECT l.id, b.title, m.name, l.borrowed_at, l.due_date, (l.due_date < CURDATE()) AS overdue
             FROM loans l
             JOIN books b ON b.id = l.book_id
             JOIN members m ON m.id = l.member_id
             WHERE l.returned_at IS NULL
             ORDER BY l.due_date'
        )->fetchAll();
    }
}
