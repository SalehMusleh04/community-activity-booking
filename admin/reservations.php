<?php
require_once __DIR__ . '/../config/db.php';
$db = new DBConn();
$pdo = $db->connect();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) {
    $resId = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    if (in_array($action, ['confirmed', 'rejected'], true)) {
        $stmtUpdate = $pdo->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
        $stmtUpdate->execute([$action, $resId]);
    }

    $redirectUrl = 'reservations.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirectUrl .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: $redirectUrl");
    exit;
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
        ts.slot_date,
        ts.start_time,
        ts.end_time
    FROM reservations r
    INNER JOIN users u ON r.user_id = u.user_id
    INNER JOIN time_slots ts ON r.time_slot_id = ts.time_slot_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND u.name LIKE ?";
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

$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="top-header">
    <div>
        <h2>Manage Bookings</h2>
        <p class="text-muted small mb-0">Approve, reject, filter, and inspect user reservations.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light rounded-circle shadow-sm p-2" style="width: 44px; height: 44px;">
            <i class="bi bi-bell"></i>
        </button>
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px;">
            A
        </div>
    </div>
</div>

<div class="custom-card mb-4 p-3 shadow-sm border-0">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control border-0 bg-light rounded-pill px-3" placeholder="Search by user name...">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select border-0 bg-light rounded-pill px-3">
                <option value="">All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="form-control border-0 bg-light rounded-pill px-3">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 rounded-pill">Filter</button>
        </div>
    </form>
</div>

<div class="custom-card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-0 px-4 py-3">User</th>
                    <th class="border-0 py-3">Date & Time</th>
                    <th class="border-0 py-3">Note</th>
                    <th class="border-0 py-3">Status</th>
                    <th class="border-0 py-3 text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No reservations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $row): ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold"><?= htmlspecialchars($row['user_name']) ?></div>
                                <span class="text-muted small"><?= htmlspecialchars($row['user_email']) ?></span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars(date('M d, Y', strtotime($row['slot_date']))) ?></div>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('g:i A', strtotime($row['start_time']))) ?> - 
                                    <?= htmlspecialchars(date('g:i A', strtotime($row['end_time']))) ?>
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
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="confirmed">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">Approve</button>
                                        </form>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">Reject</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'confirmed'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="rejected">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">Reject</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reservation_id" value="<?= (int)$row['reservation_id'] ?>">
                                            <input type="hidden" name="action" value="confirmed">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
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

<?php require_once '../includes/footer.php'; ?>