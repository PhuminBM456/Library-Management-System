<?php
require_once 'Database.php';

class Book
{
    public function __construct(
        private int $id = 0,
        private string $title = '',
        private string $author = '',
        private int $availableCopies = 0
    ) {}

    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getAvailableCopies(): int { return $this->availableCopies; }
    public function isAvailable(): bool { return $this->availableCopies > 0; }

    public static function all(): array
    {
        $rows = Database::connect()->query('SELECT * FROM books ORDER BY title')->fetchAll();
        return array_map(
            fn($r) => new Book($r['id'], $r['title'], $r['author'], $r['available_copies']),
            $rows
        );
    }

    public static function add(string $title, string $author, string $isbn, int $copies): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO books (title, author, isbn, total_copies, available_copies) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([$title, $author, $isbn, $copies, $copies]);
    }
}
