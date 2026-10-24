<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
$db = new DBConn();
$pdo = $db->connect();

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $reservationId = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    if (in_array($action, ['confirmed', 'rejected', 'cancelled'], true)) {
        try {
            $stmtUpdate = $pdo->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
            $stmtUpdate->execute([$action, $reservationId]);
            $message = "Reservation #$reservationId status updated to " . ucfirst($action) . ".";
        } catch (PDOException $e) {
            $error = "Failed to update reservation status.";
        }
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');

$sql = "
    SELECT 
        r.reservation_id,
        r.status,
        r.note,
        r.created_at,
        u.name AS user_name,
        u.email AS user_email,
        a.title AS activity_title,
        a.location,
        ts.slot_date,
        ts.start_time,
        ts.end_time
    FROM reservations r
    INNER JOIN users u ON r.user_id = u.user_id
    INNER JOIN time_slots ts ON r.time_slot_id = ts.time_slot_id
    INNER JOIN activities a ON ts.activity_id = a.activity_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR a.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'confirmed', 'rejected', 'cancelled'], true)) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter !== '') {
    $sql .= " AND ts.slot_date = ?";
    $params[] = $dateFilter;
}

$sql .= " ORDER BY ts.slot_date DESC, ts.start_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="top-header mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Manage Bookings</h2>
        <p class="text-muted small mb-0">Approve, reject, filter, and inspect user reservations.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light rounded-circle shadow-sm p-2" style="width: 44px; height: 44px;">
            <i class="bi bi-bell"></i>
        </button>
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px;">
            <?= htmlspecialchars(strtoupper(substr($_SESSION['user']['name'] ?? 'A', 0, 1))) ?>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control bg-light border-0 rounded-pill px-3 py-2" placeholder="Search by user or activity...">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light border-0 rounded-pill px-3 py-2">
                <option value="">All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="form-control bg-light border-0 rounded-pill px-3 py-2">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-semibold">Filter</button>
            <?php if ($search !== '' || $statusFilter !== '' || $dateFilter !== ''): ?>
                <a href="reservations.php" class="btn btn-light border rounded-pill px-3 py-2" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-0 px-4 py-3">User</th>
                    <th class="border-0 py-3">Activity</th>
                    <th class="border-0 py-3">Date & Time</th>
                    <th class="border-0 py-3">Note</th>
                    <th class="border-0 py-3">Status</th>
                    <th class="border-0 py-3 text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No bookings found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($res['user_name']) ?></div>
                                <span class="text-muted small"><?= htmlspecialchars($res['user_email']) ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($res['activity_title']) ?></div>
                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                    <?= htmlspecialchars($res['location']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?= htmlspecialchars(date('M d, Y', strtotime($res['slot_date']))) ?>
                                </div>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('g:i A', strtotime($res['start_time']))) ?> - <?= htmlspecialchars(date('g:i A', strtotime($res['end_time']))) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <?= !empty($res['note']) ? htmlspecialchars($res['note']) : '-' ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($res['status']) {
                                    'confirmed' => 'bg-success-subtle text-success',
                                    'pending'   => 'bg-warning-subtle text-warning',
                                    'rejected'  => 'bg-danger-subtle text-danger',
                                    'cancelled' => 'bg-secondary-subtle text-secondary',
                                    default     => 'bg-light text-dark'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> rounded-pill px-3 py-2 fw-semibold">
                                    <?= ucfirst(htmlspecialchars($res['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <?php if ($res['status'] === 'pending'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="confirmed">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($res['status'] === 'confirmed'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($res['status'] === 'rejected'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="confirmed">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                Approve
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Cancelled by user</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>