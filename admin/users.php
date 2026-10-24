<?php
require_once __DIR__ . '/../config/db.php';
$db = new DBConn();
$pdo = $db->connect();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $targetUserId = (int)$_POST['user_id'];
    $currentAdminId = (int)($_SESSION['user_id'] ?? 0);

    if ($_POST['action'] === 'toggle_status') {
        if ($targetUserId === $currentAdminId) {
            $error = "You cannot deactivate your own account.";
        } else {
            $stmtToggle = $pdo->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE user_id = ?");
            $stmtToggle->execute([$targetUserId]);
            $message = "User status updated successfully.";
        }
    } elseif ($_POST['action'] === 'change_role' && isset($_POST['role'])) {
        $newRole = $_POST['role'];
        if (in_array($newRole, ['admin', 'user'], true)) {
            if ($targetUserId === $currentAdminId && $newRole !== 'admin') {
                $error = "You cannot demote your own administrator account.";
            } else {
                $stmtRole = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $stmtRole->execute([$newRole, $targetUserId]);
                $message = "User role changed to " . ucfirst($newRole) . ".";
            }
        }
    }
}

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');

$sql = "
    SELECT 
        u.user_id,
        u.name,
        u.email,
        u.role,
        u.is_active,
        u.created_at,
        COUNT(r.reservation_id) AS total_reservations
    FROM users u
    LEFT JOIN reservations r ON u.user_id = r.user_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($roleFilter !== '' && in_array($roleFilter, ['admin', 'user'], true)) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="top-header mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Manage Users</h2>
        <p class="text-muted small mb-0">View registered accounts, manage access roles, and toggle activity statuses.</p>
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

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="custom-card mb-4 p-3 shadow-sm border-0">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control border-0 bg-light rounded-pill px-3" placeholder="Search by name or email address...">
        </div>
        <div class="col-md-4">
            <select name="role" class="form-select border-0 bg-light rounded-pill px-3">
                <option value="">All Roles</option>
                <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 rounded-pill">Filter</button>
        </div>
    </form>
</div>

<div class="custom-card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-0 px-4 py-3">User</th>
                    <th class="border-0 py-3">Role</th>
                    <th class="border-0 py-3">Status</th>
                    <th class="border-0 py-3">Total Bookings</th>
                    <th class="border-0 py-3">Joined Date</th>
                    <th class="border-0 py-3 text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No user accounts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                <span class="text-muted small"><?= htmlspecialchars($u['email']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : 'bg-secondary-subtle text-secondary' ?> rounded-pill px-3 py-1">
                                    <?= ucfirst(htmlspecialchars($u['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= (int)$u['is_active'] === 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-3 py-1">
                                    <?= (int)$u['is_active'] === 1 ? 'Active' : 'Deactivated' ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold"><?= (int)$u['total_reservations'] ?></span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('M d, Y', strtotime($u['created_at']))) ?>
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <button type="submit" class="btn btn-sm rounded-pill px-3 <?= (int)$u['is_active'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= (int)$u['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                        <button type="submit" class="btn btn-sm btn-light border rounded-pill px-3" title="Change Role">
                                            Make <?= $u['role'] === 'admin' ? 'User' : 'Admin' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once '../includes/footer.php'; ?>