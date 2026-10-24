<?php

function requireAuth(?string $requiredRole = null): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user']['user_id'], $_SESSION['user']['role'])) {
        header('Location: /community-activity-booking/login.php');
        exit;
    }

    if (
        $requiredRole !== null &&
        $_SESSION['user']['role'] !== $requiredRole
    ) {
        http_response_code(403);
        exit('Access denied.');
    }
}