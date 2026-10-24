
<?php

class Reservation
{
    private PDO $conn;
    private string $table = 'reservations';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByUser(int $userId): array
    {
        $sql = "SELECT r.*, ts.slot_date, ts.start_time, ts.end_time,
                       a.title AS activity_title, a.location
                FROM {$this->table} r
                JOIN time_slots ts ON ts.time_slot_id = r.time_slot_id
                JOIN activities a ON a.activity_id = ts.activity_id
                WHERE r.user_id = ?
                ORDER BY ts.slot_date DESC, ts.start_time DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function getAll(): array
    {
        $sql = "SELECT r.*, u.name AS user_name, u.email,
                       ts.slot_date, ts.start_time, ts.end_time,
                       a.title AS activity_title
                FROM {$this->table} r
                JOIN users u ON u.user_id = r.user_id
                JOIN time_slots ts ON ts.time_slot_id = r.time_slot_id
                JOIN activities a ON a.activity_id = ts.activity_id
                ORDER BY r.created_at DESC";

        return $this->conn->query($sql)->fetchAll();
    }

    public function create(int $userId, int $timeSlotId, string $note = ''): bool
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare(
                "SELECT time_slot_id, capacity, slot_date, start_time
                 FROM time_slots
                 WHERE time_slot_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$timeSlotId]);

            $slot = $stmt->fetch();

            if (
                !$slot ||
                strtotime($slot['slot_date'] . ' ' . $slot['start_time']) <= time()
            ) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare(
                "SELECT reservation_id, status
                 FROM {$this->table}
                 WHERE user_id = ? AND time_slot_id = ?"
            );
            $stmt->execute([$userId, $timeSlotId]);

            $existing = $stmt->fetch();

            if (
                $existing &&
                !in_array($existing['status'], ['cancelled', 'rejected'], true)
            ) {
                $this->conn->rollBack();
                return false;
            }

            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM {$this->table}
                 WHERE time_slot_id = ?
                 AND status IN ('pending', 'confirmed')"
            );
            $stmt->execute([$timeSlotId]);

            $bookedCount = (int) $stmt->fetch()['total'];

            if ($bookedCount >= (int) $slot['capacity']) {
                $this->conn->rollBack();
                return false;
            }

            if ($existing) {
                $stmt = $this->conn->prepare(
                    "UPDATE {$this->table}
                     SET status = 'pending', note = ?, updated_at = NOW()
                     WHERE reservation_id = ?"
                );
                $stmt->execute([$note, $existing['reservation_id']]);
            } else {
                $stmt = $this->conn->prepare(
                    "INSERT INTO {$this->table}
                     (user_id, time_slot_id, status, note)
                     VALUES (?, ?, 'pending', ?)"
                );
                $stmt->execute([$userId, $timeSlotId, $note]);
            }

            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            return false;
        }
    }

    public function cancel(int $reservationId, int $userId): bool
    {
        $sql = "UPDATE {$this->table} r
                JOIN time_slots ts ON ts.time_slot_id = r.time_slot_id
                SET r.status = 'cancelled', r.updated_at = NOW()
                WHERE r.reservation_id = ?
                AND r.user_id = ?
                AND r.status IN ('pending', 'confirmed')
                AND TIMESTAMP(ts.slot_date, ts.start_time) > NOW()";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$reservationId, $userId]);

        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $reservationId, string $status): bool
    {
        $allowedStatuses = ['confirmed', 'rejected', 'cancelled'];

        if (!in_array($status, $allowedStatuses, true)) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET status = ?, updated_at = NOW()
                WHERE reservation_id = ?
                AND status IN ('pending', 'confirmed')";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$status, $reservationId]);

        return $stmt->rowCount() > 0;
    }
}
