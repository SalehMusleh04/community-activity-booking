<?php 
require_once __DIR__ . '/../config/db.php';
$db = new DBConn();
$pdo = $db->connect();
require_once __DIR__ . '/../includes/header.php';

$statsQuery = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM reservations) AS total_reservations,
        (SELECT COUNT(*) FROM reservations WHERE status = 'pending') AS total_pending,
        (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed') AS total_confirmed,
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users
");
$stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

$stmtChart = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM reservations 
    GROUP BY status
");
$rawChartData = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);

$statuses = ['pending', 'confirmed', 'rejected', 'cancelled'];
$chartCounts = [];
foreach ($statuses as $st) {
    $chartCounts[] = (int)($rawChartData[$st] ?? 0);
}
?>

<div class="top-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2>Admin Dashboard</h2>
            <p class="text-muted small mb-0">System performance, reservation overview, and metrics.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light rounded-circle shadow-sm p-2" style="width: 44px; height: 44px;">
                <i class="bi bi-bell"></i>
            </button>
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px;">
                <?= htmlspecialchars(strtoupper(substr($user['name'] ?? 'A', 0, 1))) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Reservations</span>
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-bookmark-check fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0"><?= $stats['total_reservations'] ?? 0 ?></h3>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Pending Approvals</span>
                <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0"><?= $stats['total_pending'] ?? 0 ?></h3>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Confirmed Bookings</span>
                <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-check-circle fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0"><?= $stats['total_confirmed'] ?? 0 ?></h3>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Registered Users</span>
                <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-people fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0"><?= $stats['total_users'] ?? 0 ?></h3>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3 h-100">
            <h5 class="fw-bold mb-3">Reservations Analytics</h5>
            <div style="height: 250px; position: relative;">
                <canvas id="reservationsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="custom-card border-0 shadow-sm p-4 bg-white rounded-3 h-100">
            <h5 class="fw-bold mb-3">Quick Actions</h5>
            <div class="d-flex flex-column gap-2">
                <a href="slots.php" class="btn btn-light text-start py-3 px-3 rounded-4 border-0">
                    <i class="bi bi-calendar-plus text-primary me-2"></i> Create New Time Slot
                </a>
                <a href="reservations.php" class="btn btn-light text-start py-3 px-3 rounded-4 border-0">
                    <i class="bi bi-inbox text-warning me-2"></i> Review Pending Bookings
                </a>
                <a href="users.php" class="btn btn-light text-start py-3 px-3 rounded-4 border-0">
                    <i class="bi bi-person-gear text-info me-2"></i> Manage User Accounts
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('reservationsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Pending', 'Confirmed', 'Rejected', 'Cancelled'],
            datasets: [{
                label: 'Reservations Count',
                data: <?= json_encode($chartCounts); ?>,
                backgroundColor: [
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(239, 68, 68, 0.85)',
                    'rgba(107, 114, 128, 0.85)'
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { precision: 0 },
                    grid: { borderDash: [4, 4] } 
                },
                x: { 
                    grid: { display: false } 
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>