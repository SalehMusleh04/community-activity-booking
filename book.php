<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/config/db.php';
$db = new DBConn();
$pdo = $db->connect();

$userId = (int)$_SESSION['user_id'];
$slotId = (int)($_GET['time_slot_id'] ?? $_POST['time_slot_id'] ?? 0);

if ($slotId <= 0) {
    header('Location: index.php');
    exit;
}

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $note = trim($_POST['note'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmtSlot = $pdo->prepare("
            SELECT ts.*, a.title 
            FROM time_slots ts
            INNER JOIN activities a ON ts.activity_id = a.activity_id
            WHERE ts.time_slot_id = ?
            FOR UPDATE
        ");
        $stmtSlot->execute([$slotId]);
        $slotData = $stmtSlot->fetch(PDO::FETCH_ASSOC);

        if (!$slotData) {
            $pdo->rollBack();
            $errorMessage = "The selected session could not be found.";
        } elseif (strtotime("{$slotData['slot_date']} {$slotData['start_time']}") <= time()) {
            $pdo->rollBack();
            $errorMessage = "You cannot reserve a session that has already passed.";
        } else {
            $stmtUserCheck = $pdo->prepare("
                SELECT reservation_id 
                FROM reservations 
                WHERE user_id = ? AND time_slot_id = ? AND status != 'cancelled'
            ");
            $stmtUserCheck->execute([$userId, $slotId]);
            if ($stmtUserCheck->fetch()) {
                $pdo->rollBack();
                $errorMessage = "You already have an active booking for this session.";
            } else {
                $stmtCount = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM reservations 
                    WHERE time_slot_id = ? AND status IN ('pending', 'confirmed')
                ");
                $stmtCount->execute([$slotId]);
                $activeBookings = (int)$stmtCount->fetchColumn();

                if ($activeBookings >= (int)$slotData['capacity']) {
                    $pdo->rollBack();
                    $errorMessage = "Sorry, this session is now completely fully booked.";
                } else {
                    $stmtInsert = $pdo->prepare("
                        INSERT INTO reservations (user_id, time_slot_id, status, note) 
                        VALUES (?, ?, 'pending', ?)
                    ");
                    $stmtInsert->execute([$userId, $slotId, !empty($note) ? $note : null]);

                    $pdo->commit();
                    header('Location: my_reservations.php?success=1');
                    exit;
                }
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = "An unexpected error occurred while processing your reservation. Please try again.";
    }
}

$stmtView = $pdo->prepare("
    SELECT 
        ts.time_slot_id,
        ts.slot_date,
        ts.start_time,
        ts.end_time,
        ts.capacity,
        a.title,
        a.description,
        a.location,
        (ts.capacity - COUNT(CASE WHEN r.status IN ('pending', 'confirmed') THEN 1 END)) AS available_seats
    FROM time_slots ts
    INNER JOIN activities a ON ts.activity_id = a.activity_id
    LEFT JOIN reservations r ON ts.time_slot_id = r.time_slot_id
    WHERE ts.time_slot_id = ?
    GROUP BY ts.time_slot_id, a.title, a.description, a.location
");
$stmtView->execute([$slotId]);
$slot = $stmtView->fetch(PDO::FETCH_ASSOC);

if (!$slot) {
    header('Location: index.php');
    exit;
}

$availableSeats = (int)$slot['available_seats'];
$isAvailable = $availableSeats > 0 && strtotime("{$slot['slot_date']} {$slot['start_time']}") > time();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <div class="d-flex align-items-center mb-4">
                <a href="index.php" class="btn btn-light rounded-circle shadow-sm me-3" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h3 class="fw-bold mb-0">Confirm Your Booking</h3>
                    <p class="text-muted small mb-0">Review the activity details and confirm your attendance.</p>
                </div>
            </div>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                        <?= htmlspecialchars($slot['location'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($slot['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p class="text-muted small mb-4">
                        <?= !empty($slot['description']) ? htmlspecialchars($slot['description'], ENT_QUOTES, 'UTF-8') : 'No additional description provided.' ?>
                    </p>

                    <div class="bg-light rounded-3 p-3 mb-4 border">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Date:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars(date('l, F j, Y', strtotime($slot['slot_date']))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Time:</span>
                            <span class="fw-semibold text-dark">
                                <?= htmlspecialchars(date('g:i A', strtotime($slot['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($slot['end_time']))) ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Available Seats:</span>
                            <span class="badge <?= $availableSeats <= 3 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> rounded-pill px-2 py-1">
                                <?= max(0, $availableSeats) ?> of <?= (int)$slot['capacity'] ?> remaining
                            </span>
                        </div>
                    </div>

                    <?php if (!$isAvailable): ?>
                        <div class="alert alert-warning text-center rounded-3 mb-0">
                            <strong>This session is no longer available for booking.</strong>
                            <p class="small mb-0 mt-1">It might be full or the session time has already passed.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="time_slot_id" value="<?= (int)$slot['time_slot_id'] ?>">
                            
                            <div class="mb-4">
                                <label for="note" class="form-label small fw-semibold text-dark">Special Requests / Notes (Optional)</label>
                                <textarea name="note" id="note" rows="3" class="form-control rounded-3" placeholder="Any special requirements, equipment notes, or questions..."></textarea>
                            </div>

                            <button type="submit" name="confirm_booking" class="btn btn-primary w-100 py-3 rounded-pill fw-semibold shadow-sm">
                                Confirm & Book Spot
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>