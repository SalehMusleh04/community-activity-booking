
<?php

class Activity
{
    private PDO $conn;
    private string $table = 'activities';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT activity_id, title, description, location
                FROM {$this->table}
                ORDER BY activity_id DESC";

        return $this->conn->query($sql)->fetchAll();
    }

    public function getById(int $id)
    {
        $sql = "SELECT activity_id, title, description, location
                FROM {$this->table}
                WHERE activity_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function create(string $title, string $description, string $location): bool
    {
        $title = trim($title);
        $description = trim($description);
        $location = trim($location);

        if ($title === '' || $location === '') {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (title, description, location)
                VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$title, $description, $location]);
    }

    public function update(
        int $id,
        string $title,
        string $description,
        string $location
    ): bool {
        $title = trim($title);
        $description = trim($description);
        $location = trim($location);

        if ($id <= 0 || $title === '' || $location === '') {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET title = ?, description = ?, location = ?
                WHERE activity_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$title, $description, $location, $id]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE activity_id = ?";

        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
