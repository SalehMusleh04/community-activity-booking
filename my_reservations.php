<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$db = new DBConn();
$pdo = $db->connect();

$userId = (int)$_SESSION['user_id'];
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    if ($_POST['action'] === 'cancel') {
        $resId = (int)$_POST['reservation_id'];
        $stmtCancel = $pdo->prepare("
            UPDATE reservations 
            SET status = 'cancelled' 
            WHERE reservation_id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
        ");
        $stmtCancel->execute([$resId, $userId]);
        $message = "Your reservation has been cancelled.";
    }
}

$stmt = $pdo->prepare("
    SELECT 
        r.reservation_id,
        r.status,
        r.note,
        r.created_at,
        a.title AS activity_title,
        a.location,
        ts.slot_date,
        ts.start_time,
        ts.end_time
    FROM reservations r
    INNER JOIN time_slots ts ON r.time_slot_id = ts.time_slot_id
    INNER JOIN activities a ON ts.activity_id = a.activity_id
    WHERE r.user_id = ?
    ORDER BY ts.slot_date DESC, ts.start_time DESC
");
$stmt->execute([$userId]);
$myReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="top-header mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">My Reservations</h2>
        <p class="text-muted small mb-0">Track your booking status or cancel upcoming appointments.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="activities.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
    <i class="bi bi-plus-lg me-1"></i> Book New Session
</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> Your booking request has been submitted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="custom-card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-0 px-4 py-3">Activity</th>
                    <th class="border-0 py-3">Location</th>
                    <th class="border-0 py-3">Date</th>
                    <th class="border-0 py-3">Time</th>
                    <th class="border-0 py-3">Note</th>
                    <th class="border-0 py-3">Status</th>
                    <th class="border-0 py-3 text-end px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($myReservations)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                            You don't have any bookings yet.
                            <div class="mt-2">
                              <a href="activities.php" class="btn btn-primary btn-sm rounded-pill px-3">
    Explore Activities
</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($myReservations as $row): ?>
                        <tr>
                            <td class="px-4 fw-semibold text-dark">
                                <?= htmlspecialchars($row['activity_title']) ?>
                            </td>
                            <td>
                                <span class="text-muted small"><?= htmlspecialchars($row['location']) ?></span>
                            </td>
                            <td>
                                <?= htmlspecialchars(date('M d, Y', strtotime($row['slot_date']))) ?>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('g:i A', strtotime($row['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($row['end_time']))) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small"><?= !empty($row['note']) ? htmlspecialchars($row['note']) : '-' ?></span>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($row['status']) {
                                    'confirmed' => 'bg-success-subtle text-success',
                                    'pending'   => 'bg-warning-subtle text-warning',
                                    'rejected'  => 'bg-danger-subtle text-danger',
                                    'cancelled' => 'bg-secondary-subtle text-secondary',
                                    default     => 'bg-light text-dark'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 fw-semibold">
                                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <?php if (in_array($row['status'], ['pending', 'confirmed'])): ?>
                                    <form method="POST" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                            Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>