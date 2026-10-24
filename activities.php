<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/classes/TimeSlot.php';

$availableSlots = [];
$loadError = false;

try {
    $db = (new DBConn())->connect();
    $timeSlotModel = new TimeSlot($db);
    $availableSlots = $timeSlotModel->getAvailable();
} catch (PDOException $e) {
    $loadError = true;
}

$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery !== '' && !empty($availableSlots)) {
    $availableSlots = array_filter($availableSlots, function ($slot) use ($searchQuery) {
        $title = $slot['title'] ?? $slot['activity_title'] ?? '';
        $location = $slot['location'] ?? '';
        return stripos($title, $searchQuery) !== false || stripos($location, $searchQuery) !== false;
    });
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="top-header mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h2 class="fw-bold mb-1">Browse Activities</h2>
        <p class="text-muted small mb-0">Explore available community sessions, workshops, and reserve your spot.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="my_reservations.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="bi bi-calendar-check me-1"></i> My Bookings
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" action="activities.php" class="row g-2 align-items-center">
        <div class="col-md-9 col-lg-10">
            <div class="input-group">
                <span class="input-group-text bg-light border-0 ps-3">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="form-control bg-light border-0 py-2" placeholder="Search by activity name or location...">
            </div>
        </div>
        <div class="col-md-3 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">
                Search
            </button>
            <?php if ($searchQuery !== ''): ?>
                <a href="activities.php" class="btn btn-light border rounded-pill px-3" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($loadError): ?>
    <div class="alert alert-danger rounded-3 shadow-sm text-center py-3">
        <i class="bi bi-exclamation-octagon me-2"></i> Unable to load activities at the moment. Please try again later.
    </div>
<?php elseif (empty($availableSlots)): ?>
    <div class="card border-0 shadow-sm rounded-4 text-center py-5 bg-white">
        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
        <h5 class="fw-bold mt-3 mb-1">No Available Sessions</h5>
        <p class="text-muted small mb-3">There are no upcoming activities matching your criteria right now.</p>
        <?php if ($searchQuery !== ''): ?>
            <div>
                <a href="activities.php" class="btn btn-outline-primary btn-sm rounded-pill px-4">Clear Filter</a>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($availableSlots as $slot): ?>
            <?php 
                $seatsLeft = (int)($slot['available_seats'] ?? $slot['capacity']);
                $formattedDate = date('D, M j, Y', strtotime($slot['slot_date']));
                $formattedTime = date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time']));
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 d-flex flex-column justify-content-between bg-white">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                <?= htmlspecialchars($slot['location'] ?? 'Community Center', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="badge <?= $seatsLeft <= 3 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> rounded-pill px-2 py-1">
                                <?= $seatsLeft ?> seats left
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mb-2">
                            <?= htmlspecialchars($slot['title'] ?? $slot['activity_title'] ?? 'Activity Session', ENT_QUOTES, 'UTF-8') ?>
                        </h5>

                        <p class="text-muted small mb-3" style="line-height: 1.6;">
                            <?= htmlspecialchars($slot['description'] ?? 'No session description provided.', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div>
                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="small text-muted mb-1">
                                <i class="bi bi-calendar3 text-primary me-2"></i> <?= $formattedDate ?>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-clock text-primary me-2"></i> <?= $formattedTime ?>
                            </div>
                        </div>

                        <a href="book.php?time_slot_id=<?= (int)$slot['time_slot_id'] ?>" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
                            Book Now <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>