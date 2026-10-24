<?php
require_once __DIR__ . '/../config/db.php';
$db = new DBConn();
$pdo = $db->connect();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $activityId = (int)($_POST['activity_id'] ?? 0);
        $slotDate = trim($_POST['slot_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 0);

        if ($activityId <= 0 || empty($slotDate) || empty($startTime) || empty($endTime) || $capacity <= 0) {
            $error = "Please fill in all fields with valid values.";
        } elseif (strtotime($endTime) <= strtotime($startTime)) {
            $error = "End time must be after start time.";
        } else {
            $stmtInsert = $pdo->prepare("
                INSERT INTO time_slots (activity_id, slot_date, start_time, end_time, capacity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$activityId, $slotDate, $startTime, $endTime, $capacity]);
            $message = "Time slot created successfully.";
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['time_slot_id'])) {
        $slotId = (int)$_POST['time_slot_id'];
        
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE time_slot_id = ?");
        $stmtCheck->execute([$slotId]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Cannot delete a slot with existing reservations.";
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM time_slots WHERE time_slot_id = ?");
            $stmtDel->execute([$slotId]);
            $message = "Time slot deleted successfully.";
        }
    }
}

$activities = $pdo->query("SELECT activity_id, title FROM activities ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$slotsQuery = "
    SELECT 
        ts.time_slot_id,
        ts.slot_date,
        ts.start_time,
        ts.end_time,
        ts.capacity,
        a.title AS activity_title,
        a.location,
        COUNT(CASE WHEN r.status IN ('pending', 'confirmed') THEN 1 END) AS booked_count
    FROM time_slots ts
    INNER JOIN activities a ON ts.activity_id = a.activity_id
    LEFT JOIN reservations r ON ts.time_slot_id = r.time_slot_id
    GROUP BY ts.time_slot_id, a.title, a.location
    ORDER BY ts.slot_date DESC, ts.start_time ASC
";
$slots = $pdo->query($slotsQuery)->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="top-header mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold mb-1">Manage Time Slots</h2>
        <p class="text-muted small mb-0">Create, monitor, and manage activity scheduling sessions.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createSlotModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Slot
        </button>
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

<div class="custom-card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="border-0 px-4 py-3">Activity</th>
                    <th class="border-0 py-3">Location</th>
                    <th class="border-0 py-3">Date</th>
                    <th class="border-0 py-3">Time</th>
                    <th class="border-0 py-3">Occupancy</th>
                    <th class="border-0 py-3 text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slots)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No time slots found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($slots as $row): ?>
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
                                <?php 
                                    $booked = (int)$row['booked_count'];
                                    $cap = (int)$row['capacity'];
                                    $badge = ($booked >= $cap) ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary';
                                ?>
                                <span class="badge <?= $badge ?> rounded-pill px-3 py-1">
                                    <?= $booked ?> / <?= $cap ?> Seats
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <button type="button" 
                                        class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-delete-slot" 
                                        data-slot-id="<?= (int)$row['time_slot_id'] ?>" 
                                        data-activity-name="<?= htmlspecialchars($row['activity_title'], ENT_QUOTES) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteSlotModal">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="createSlotModal" tabindex="-1" aria-labelledby="createSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="createSlotModalLabel">Create New Time Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Activity</label>
                        <select name="activity_id" class="form-select" required>
                            <option value="">Select Activity</option>
                            <?php foreach ($activities as $act): ?>
                                <option value="<?= (int)$act['activity_id'] ?>"><?= htmlspecialchars($act['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Session Date</label>
                        <input type="date" name="slot_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Capacity (Total Seats)</label>
                        <input type="number" name="capacity" class="form-control" min="1" max="100" value="10" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteSlotModal" tabindex="-1" aria-labelledby="deleteSlotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteSlotModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-secondary mb-0">
                    Are you sure you want to permanently delete the time slot for <strong id="deleteSlotActivityName" class="text-dark"></strong>?
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline m-0">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="time_slot_id" id="deleteSlotIdInput" value="">
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteSlotModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const slotId = button.getAttribute('data-slot-id');
            const activityName = button.getAttribute('data-activity-name');
            
            document.getElementById('deleteSlotIdInput').value = slotId;
            document.getElementById('deleteSlotActivityName').textContent = activityName;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>