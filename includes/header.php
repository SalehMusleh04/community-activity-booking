<?php
require_once __DIR__ . '/auth_guard.php';
requireAuth();

$baseUrl = '/community-activity-booking';
$user = $_SESSION['user'];
$isAdmin = ($user['role'] === 'admin');

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GatherPoint</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fb;
            color: #222;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            padding: 25px 18px;
            background: #fff;
            border-right: 1px solid #e8edf4;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 10px;
            margin-bottom: 35px;
            color: #222;
            text-decoration: none;
            font-size: 22px;
            font-weight: 800;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2563eb;
            color: #fff;
            border-radius: 12px;
        }

        .menu-label {
            margin: 0 10px 12px;
            color: #8a94a6;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            margin-bottom: 5px;
            border-radius: 10px;
            color: #667085;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .menu-link:hover {
            background: #f2f6ff;
            color: #2563eb;
        }

        .menu-link.active {
            background: #eaf1ff;
            color: #2563eb;
        }

        .menu-link i {
            font-size: 18px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #edf0f5;
        }

        .user-info {
            padding: 12px 10px;
            margin-bottom: 8px;
            overflow-wrap: anywhere;
        }

        .user-name {
            font-size: 14px;
            font-weight: 700;
        }

        .user-role {
            margin-top: 3px;
            color: #8a94a6;
            font-size: 12px;
        }

        .logout-link {
            color: #dc3545;
        }

        .main-wrapper {
            margin-left: 260px;
            padding: 35px;
            min-height: 100vh;
        }


        .page-heading {
            flex: 1;
            min-width: 0;
        }

        .header-actions {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 210px;
            }

            .main-wrapper {
                margin-left: 210px;
                padding: 20px 15px;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 75px;
                padding: 20px 8px;
            }

            .brand {
                justify-content: center;
                padding: 0;
                font-size: 0;
            }

            .brand-icon {
                flex-shrink: 0;
            }

            .menu-label,
            .menu-text,
            .user-info {
                display: none;
            }

            .menu-link {
                justify-content: center;
                padding: 13px 5px;
            }

            .menu-link i {
                font-size: 21px;
            }

            .main-wrapper {
                margin-left: 75px;
                padding: 20px 12px;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="<?= $baseUrl ?>/index.php" class="brand">
        <span class="brand-icon">
            <i class="bi bi-calendar-check"></i>
        </span>
        GatherPoint
    </a>

    <?php if ($isAdmin): ?>
        <p class="menu-label">Center Management</p>
        <a href="<?= $baseUrl ?>/admin/dashboard.php" class="menu-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i>
            <span class="menu-text">Dashboard</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/reservations.php" class="menu-link <?= $currentPage === 'reservations.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i>
            <span class="menu-text">Manage Reservations</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/slots.php" class="menu-link <?= $currentPage === 'slots.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-week"></i>
            <span class="menu-text">Manage Time Slots</span>
        </a>
        <a href="<?= $baseUrl ?>/admin/users.php" class="menu-link <?= $currentPage === 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span class="menu-text">Manage Users</span>
        </a>
    <?php else: ?>
        <p class="menu-label">Main Menu</p>
        <a href="<?= $baseUrl ?>/index.php" class="menu-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-house"></i>
            <span class="menu-text">Home</span>
        </a>
        <a href="<?= $baseUrl ?>/activities.php" class="menu-link <?= $currentPage === 'activities.php' ? 'active' : '' ?>">
    <i class="bi bi-calendar-event"></i>
    <span class="menu-text">Browse Activities</span>
</a>
        <a href="<?= $baseUrl ?>/my_reservations.php" class="menu-link <?= $currentPage === 'my_reservations.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i>
            <span class="menu-text">My Reservations</span>
        </a>
    <?php endif; ?>

    <div class="sidebar-bottom">
        <div class="user-info">
            <div class="user-name">
                <?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="user-role">
                <?= $isAdmin ? 'Administrator' : 'Member' ?>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/logout.php" class="menu-link logout-link">
            <i class="bi bi-box-arrow-right"></i>
            <span class="menu-text">Log Out</span>
        </a>
    </div>
</aside>

<main class="main-wrapper">