<?php
require_once 'Database.php';

class Member
{
    public function __construct(
        private int $id,
        private string $name
    ) {}

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }

    public static function all(): array
    {
        $rows = Database::connect()->query('SELECT * FROM members ORDER BY name')->fetchAll();
        return array_map(fn($r) => new Member($r['id'], $r['name']), $rows);
    }
}
