<?php

class TimeSlot
{
    private PDO $conn;
    private string $table = 'time_slots';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT 
                    ts.*, 
                    a.title, 
                    a.title AS activity_title,
                    a.description,
                    a.location,
                    (
                        SELECT COUNT(*)
                        FROM reservations r
                        WHERE r.time_slot_id = ts.time_slot_id
                        AND r.status IN ('pending', 'confirmed')
                    ) AS booked_count
                FROM {$this->table} ts
                JOIN activities a ON a.activity_id = ts.activity_id
                ORDER BY ts.slot_date, ts.start_time";

        return $this->conn->query($sql)->fetchAll();
    }

    public function getAvailable(): array
    {
        $sql = "SELECT 
                    ts.*, 
                    a.title, 
                    a.title AS activity_title,
                    a.description,
                    a.location,
                    ts.capacity - (
                        SELECT COUNT(*)
                        FROM reservations r
                        WHERE r.time_slot_id = ts.time_slot_id
                        AND r.status IN ('pending', 'confirmed')
                    ) AS available_seats
                FROM {$this->table} ts
                JOIN activities a ON a.activity_id = ts.activity_id
                WHERE TIMESTAMP(ts.slot_date, ts.start_time) > NOW()
                AND ts.capacity > (
                    SELECT COUNT(*)
                    FROM reservations r
                    WHERE r.time_slot_id = ts.time_slot_id
                    AND r.status IN ('pending', 'confirmed')
                )
                ORDER BY ts.slot_date, ts.start_time";

        return $this->conn->query($sql)->fetchAll();
    }

    public function getById(int $id)
    {
        $sql = "SELECT 
                    ts.*, 
                    a.title, 
                    a.title AS activity_title,
                    a.description,
                    a.location
                FROM {$this->table} ts
                JOIN activities a ON a.activity_id = ts.activity_id
                WHERE ts.time_slot_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function create(
        int $activityId,
        string $date,
        string $startTime,
        string $endTime,
        int $capacity
    ): bool {
        if (
            $activityId <= 0 ||
            $date < date('Y-m-d') ||
            $startTime >= $endTime ||
            $capacity <= 0 ||
            strtotime("$date $startTime") <= time()
        ) {
            return false;
        }

        $sql = "INSERT INTO {$this->table}
                (activity_id, slot_date, start_time, end_time, capacity)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $activityId,
            $date,
            $startTime,
            $endTime,
            $capacity
        ]);
    }

    public function update(
        int $id,
        int $activityId,
        string $date,
        string $startTime,
        string $endTime,
        int $capacity
    ): bool {
        if (
            $id <= 0 ||
            $activityId <= 0 ||
            $date < date('Y-m-d') ||
            $startTime >= $endTime ||
            $capacity <= 0 ||
            strtotime("$date $startTime") <= time()
        ) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM reservations
             WHERE time_slot_id = ?
             AND status IN ('pending', 'confirmed')"
        );
        $stmt->execute([$id]);

        if ($capacity < (int) $stmt->fetch()['total']) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET activity_id = ?, slot_date = ?, start_time = ?,
                    end_time = ?, capacity = ?
                WHERE time_slot_id = ?";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            $activityId,
            $date,
            $startTime,
            $endTime,
            $capacity,
            $id
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $check = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM reservations
             WHERE time_slot_id = ?
             AND status IN ('pending', 'confirmed')"
        );
        $check->execute([$id]);

        if ((int) $check->fetch()['total'] > 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM {$this->table} WHERE time_slot_id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}