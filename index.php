<?php
session_start();
$baseUrl = '/community-activity-booking';

require_once 'config/db.php';
require_once 'classes/TimeSlot.php';

$availableSlots = [];$loadError = false;

try {
    $db = (new DBConn())->connect();
    $timeSlotModel = new TimeSlot($db);
    $availableSlots =$timeSlotModel->getAvailable();
} catch (PDOException $e) {$loadError = true;
}

$user =$_SESSION['user'] ?? null;
$isLoggedIn = is_array($user);$isAdmin = $isLoggedIn && ($user['role'] ?? '') === 'admin';
$displayName =$isLoggedIn
    ? htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8')
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GatherPoint | Discover Your Community</title>
    <meta name="description" content="Discover workshops, classes, and events at your community center. Explore activities and manage your reservations online.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563EB;
            --primary-hover: #1D4ED8;
            --dark-navy: #172554;
            --light-blue: #EFF6FF;
            --soft-blue: #DBEAFE;
            --white: #FFFFFF;
            --text-dark: #1F2937;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 90px; }
        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-dark);
            background: var(--white);
            line-height: 1.7;
        }
        a { text-decoration: none; }
        .navbar {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #F1F5F9;
            padding: 18px 0;
        }
        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--dark-navy);
            font-size: 1.35rem;
            font-weight: 800;
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            background: var(--primary-blue);
            border-radius: 13px;
            font-size: 1.35rem;
        }
        .navbar-brand:hover { color: var(--primary-blue); }
        .nav-link {
            color: #475569;
            font-size: 0.92rem;
            font-weight: 600;
            margin: 0 8px;
            transition: color 0.2s ease;
        }
        .nav-link:hover, .nav-link:focus { color: var(--primary-blue); }
        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 23px;
            border: 1px solid var(--primary-blue);
            border-radius: 12px;
            background: var(--primary-blue);
            color: var(--white);
            font-size: 0.93rem;
            font-weight: 700;
            transition: 0.2s ease;
        }
        .btn-primary-custom:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: var(--white);
            transform: translateY(-2px);
        }
        .btn-outline-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 23px;
            border: 1px solid #CBD5E1;
            border-radius: 12px;
            background: var(--white);
            color: var(--dark-navy);
            font-size: 0.93rem;
            font-weight: 700;
            transition: 0.2s ease;
        }
        .btn-outline-custom:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background: var(--light-blue);
        }
        .hero {
            position: relative;
            overflow: hidden;
            padding: 100px 0 110px;
            background: radial-gradient(circle at 90% 10%, rgba(219, 234, 254, 0.8), transparent 35%), linear-gradient(180deg, #FFFFFF 0%, #F8FAFF 100%);
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            margin-bottom: 25px;
            border: 1px solid #BFDBFE;
            border-radius: 30px;
            background: var(--light-blue);
            color: var(--primary-hover);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .hero h1 {
            max-width: 650px;
            margin-bottom: 24px;
            color: var(--dark-navy);
            font-size: clamp(2.5rem, 5vw, 4.1rem);
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -2px;
        }
        .hero h1 span { color: var(--primary-blue); }
        .hero-description {
            max-width: 570px;
            margin-bottom: 32px;
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.9;
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 13px; }
        .hero-note {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 25px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .hero-note i { color: var(--primary-blue); font-size: 1.1rem; }
        .hero-image-wrapper { position: relative; padding: 12px; }
        .hero-image {
            width: 100%;
            min-height: 400px;
            max-height: 470px;
            object-fit: cover;
            border-radius: 28px;
            box-shadow: 0 25px 60px rgba(23, 37, 84, 0.15);
        }
        .floating-card {
            position: absolute;
            left: -12px;
            bottom: 40px;
            display: flex;
            align-items: center;
            gap: 13px;
            max-width: 245px;
            padding: 17px 19px;
            border: 1px solid #E2E8F0;
            border-radius: 17px;
            background: var(--white);
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.1);
        }
        .floating-icon {
            width: 45px;
            height: 45px;
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: var(--light-blue);
            color: var(--primary-blue);
            font-size: 1.35rem;
        }
        .floating-card strong { display: block; color: var(--dark-navy); font-size: 0.88rem; }
        .floating-card small { color: var(--text-muted); font-size: 0.75rem; }
        .section { padding: 95px 0; }
        .section-label {
            display: inline-block;
            margin-bottom: 13px;
            color: var(--primary-blue);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }
        .section-title {
            margin-bottom: 18px;
            color: var(--dark-navy);
            font-size: clamp(2rem, 3vw, 2.7rem);
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -1px;
        }
        .section-description {
            max-width: 700px;
            margin: 0 auto;
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.9;
        }
        .about-section { background: var(--white); }
        .about-visual {
            position: relative;
            min-height: 350px;
            padding: 35px;
            overflow: hidden;
            border-radius: 25px;
            background: var(--light-blue);
        }
        .about-visual::before, .about-visual::after {
            position: absolute;
            content: "";
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: 50%;
        }
        .about-visual::before { width: 290px; height: 290px; top: -100px; right: -70px; }
        .about-visual::after { width: 210px; height: 210px; bottom: -100px; left: -50px; }
        .about-icon-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
            max-width: 360px;
            margin: 0 auto;
        }
        .about-icon-card {
            min-height: 125px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 19px;
            background: rgba(255, 255, 255, 0.9);
            text-align: center;
        }
        .about-icon-card i { color: var(--primary-blue); font-size: 2rem; }
        .about-icon-card span { color: var(--dark-navy); font-size: 0.86rem; font-weight: 700; }
        .about-copy { color: var(--text-muted); line-height: 1.95; }
        .about-points { display: grid; gap: 13px; margin-top: 25px; }
        .about-point {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            color: var(--text-dark);
            font-size: 0.93rem;
            font-weight: 600;
        }
        .about-point i { margin-top: 3px; color: var(--primary-blue); font-size: 1.1rem; }
        .features-section { background: #F8FAFF; }
        .feature-card {
            height: 100%;
            padding: 30px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: var(--white);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(23, 37, 84, 0.07);
        }
        .feature-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
            border-radius: 16px;
            background: var(--light-blue);
            color: var(--primary-blue);
            font-size: 1.7rem;
        }
        .feature-card h3 { margin-bottom: 12px; color: var(--dark-navy); font-size: 1.12rem; font-weight: 800; }
        .feature-card p { margin-bottom: 0; color: var(--text-muted); font-size: 0.91rem; line-height: 1.85; }
        .steps-section { background: var(--white); }
        .step-card {
            position: relative;
            height: 100%;
            padding: 28px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: var(--white);
        }
        .step-number {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            border-radius: 14px;
            background: var(--primary-blue);
            color: var(--white);
            font-size: 1.1rem;
            font-weight: 800;
        }
        .step-card h3 { margin-bottom: 12px; color: var(--dark-navy); font-size: 1.1rem; font-weight: 800; }
        .step-card p { margin-bottom: 0; color: var(--text-muted); font-size: 0.91rem; line-height: 1.85; }
        .cta-section { padding: 35px 0 95px; }
        .cta-box {
            position: relative;
            overflow: hidden;
            padding: 65px 25px;
            border-radius: 28px;
            background: var(--dark-navy);
            text-align: center;
        }
        .cta-box::before, .cta-box::after {
            position: absolute;
            content: "";
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .cta-box::before { width: 300px; height: 300px; top: -190px; left: -80px; }
        .cta-box::after { width: 250px; height: 250px; right: -70px; bottom: -170px; }
        .cta-box h2 {
            position: relative;
            z-index: 1;
            margin-bottom: 16px;
            color: var(--white);
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 800;
        }
        .cta-box p {
            position: relative;
            z-index: 1;
            max-width: 650px;
            margin: 0 auto 28px;
            color: #CBD5E1;
            line-height: 1.9;
        }
        .cta-box .btn { position: relative; z-index: 1; }
        .footer { padding: 35px 0; border-top: 1px solid var(--border-color); background: var(--white); }
        .footer-brand { color: var(--dark-navy); font-size: 1.1rem; font-weight: 800; }
        .footer-description { margin-top: 8px; margin-bottom: 0; color: var(--text-muted); font-size: 0.84rem; }
        .footer-copy { color: var(--text-muted); font-size: 0.82rem; }
        .footer-link { color: #475569; font-size: 0.85rem; font-weight: 600; }
        .footer-link:hover { color: var(--primary-blue); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $baseUrl ?>/index.php">
            <span class="brand-icon">
                <i class="bi bi-people-fill"></i>
            </span>
            <span>GatherPoint</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto my-3 my-lg-0">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#available-sessions">Activities</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
            </ul>
            <div class="d-flex flex-column flex-lg-row gap-2">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline-custom">Log In</a>
                    <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary-custom">Get Started <i class="bi bi-arrow-right"></i></a>
                <?php elseif ($isAdmin): ?>
                    <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-primary-custom">Admin Dashboard</a>
                    <a href="<?= $baseUrl ?>/logout.php" class="btn btn-outline-custom">Log Out</a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/my_reservations.php" class="btn btn-outline-custom">My Reservations</a>
                    <a href="<?= $baseUrl ?>/logout.php" class="btn btn-primary-custom">Log Out</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<section class="hero" id="home">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-stars"></i>
                    YOUR COMMUNITY, YOUR ACTIVITIES
                </div>
                <h1>Discover.<span><br> Book.</span> <br> Connect.</h1>
                <p class="hero-description">Explore community workshops, classes, and events. Choose a date and time, then reserve your place online.</p>
                <div class="hero-actions">
    <a href="#available-sessions" class="btn btn-primary-custom">
        Explore Activities <i class="bi bi-arrow-down-right"></i>
    </a>
    <?php if (!$isLoggedIn): ?>
        <a href="<?= $baseUrl ?>/register.php" class="btn btn-outline-custom">
            Create an Account
        </a>
    <?php elseif ($isAdmin): ?>
        <a href="<?= $baseUrl ?>/admin/reservations.php" class="btn btn-outline-custom">
            Manage Reservations
        </a>
    <?php else: ?>
        <a href="<?= $baseUrl ?>/my_reservations.php" class="btn btn-outline-custom">
            My Reservations
        </a>
    <?php endif; ?>
</div>
                <div class="hero-note">
                    <i class="bi bi-calendar-check"></i>
                    <span>Explore activities and manage your reservations in one place.</span>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper">
                    <img class="hero-image" src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1200&q=85" alt="Community gathering">
                    <div class="floating-card">
                        <div class="floating-icon"><i class="bi bi-calendar2-check"></i></div>
                        <div>
                            <strong>Simple Online Reservations</strong>
                            <small>Find an activity and reserve your place.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section bg-light" id="available-sessions">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">UPCOMING SESSIONS</span>
            <h2 class="section-title">Available Activities & Classes</h2>
            <p class="section-description">Choose an upcoming time slot and secure your attendance instantly.</p>
        </div>

        <?php if ($loadError): ?>
            <div class="alert alert-danger text-center">Unable to load scheduled activities at the moment.</div>
        <?php elseif (empty($availableSlots)): ?>
            <div class="text-center py-5 border rounded-4 bg-white shadow-sm">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                <h4 class="fw-bold mt-3">No Available Sessions Right Now</h4>
                <p class="text-muted small">Please check back soon for updated schedules.</p>
            </div>
        <?php else: ?>
            <?php 
                $displayedSlots = array_slice($availableSlots, 0, 6); 
                $totalSlotsCount = count($availableSlots);
            ?>
            <div class="row g-4">
                <?php foreach ($displayedSlots as $slot): ?>
                    <?php 
                        $seatsLeft = (int)($slot['available_seats'] ?? $slot['capacity']);
                        $formattedDate = date('M d, Y', strtotime($slot['slot_date']));
                        $formattedTime = date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time']));
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                                    <?= htmlspecialchars($slot['location'] ?? 'Community Center') ?>
                                </span>
                                <span class="badge <?= $seatsLeft <= 3 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> rounded-pill px-2 py-1">
                                    <?= $seatsLeft ?> seats left
                                </span>
                            </div>
                            <h3 class="mb-2"><?= htmlspecialchars($slot['title'] ?? $slot['activity_title'] ?? 'Activity Session') ?></h3>
                            <p class="flex-grow-1 text-muted small mb-3">
                                <?= htmlspecialchars($slot['description'] ?? 'No session description provided.') ?>
                            </p>
                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="small text-muted mb-1"><i class="bi bi-calendar3 me-1"></i> <?= $formattedDate ?></div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i> <?= $formattedTime ?></div>
                            </div>
                            <a href="<?= $baseUrl ?>/book.php?time_slot_id=<?= (int)$slot['time_slot_id'] ?>" class="btn btn-primary-custom w-100">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalSlotsCount > 6): ?>
                <div class="text-center mt-5">
                    <a href="<?= $baseUrl ?>/activities.php" class="btn btn-outline-custom px-4 py-3 shadow-sm">
                        See All Activities (<?= $totalSlotsCount ?>) <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<section class="section about-section" id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-visual">
                    <div class="about-icon-grid">
                        <div class="about-icon-card"><i class="bi bi-easel2"></i><span>Workshops</span></div>
                        <div class="about-icon-card"><i class="bi bi-book"></i><span>Learning Activities</span></div>
                        <div class="about-icon-card"><i class="bi bi-people"></i><span>Community Events</span></div>
                        <div class="about-icon-card"><i class="bi bi-calendar-week"></i><span>Scheduled Sessions</span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <span class="section-label">ABOUT COMMUNITYHUB</span>
                <h2 class="section-title">Making Community Participation Easier</h2>
                <p class="about-copy">Discover community activities and manage your reservations in one convenient place.</p>
                <div class="about-points">
                    <div class="about-point"><i class="bi bi-check-circle-fill"></i><span>Explore activities and their schedules.</span></div>
                    <div class="about-point"><i class="bi bi-check-circle-fill"></i><span>Choose a suitable date and time.</span></div>
                    <div class="about-point"><i class="bi bi-check-circle-fill"></i><span>Keep track of your reservation status.</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section features-section" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">WHAT YOU CAN DO</span>
            <h2 class="section-title">Everything You Need to Get Started</h2>
            <p class="section-description">Discover activities, reserve your place, and manage bookings easily.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-search"></i></div>
                    <h3>Discover Activities</h3>
                    <p>Browse workshops, classes, and community events.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-calendar2-plus"></i></div>
                    <h3>Reserve Your Place</h3>
                    <p>Choose a suitable date and time to reserve your place.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-card-checklist"></i></div>
                    <h3>Track Your Reservations</h3>
                    <p>Check your reservation status whenever you need.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-clock-history"></i></div>
                    <h3>Find a Convenient Time</h3>
                    <p>Find a session that suits your schedule.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section steps-section" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">HOW IT WORKS</span>
            <h2 class="section-title">Get Started in Three Simple Steps</h2>
            <p class="section-description">Find an activity and reserve your place in three steps.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <h3>Create Your Account</h3>
                    <p>Create an account to manage your reservations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <h3>Choose an Activity</h3>
                    <p>Choose an activity, date, and available time.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <h3>Book and Track Your Place</h3>
                    <p>Book your place and check the reservation status.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Ready to Get Involved?</h2>
            <p>Explore local activities and find your next experience.</p>
            <?php if (!$isLoggedIn): ?>
                <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary-custom">Create Your Account <i class="bi bi-arrow-right"></i></a>
            <?php elseif ($isAdmin): ?>
                <a href="<?= $baseUrl ?>/admin/dashboard.php" class="btn btn-primary-custom">Go to Dashboard <i class="bi bi-arrow-right"></i></a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/my_reservations.php" class="btn btn-primary-custom">View My Reservations <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6">
                <a href="<?= $baseUrl ?>/index.php" class="footer-brand">
                    <i class="bi bi-people-fill text-primary"></i>
                    GatherPoint
                </a>
                <p class="footer-description">Discover activities. Reserve your place.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex flex-wrap justify-content-md-end gap-3 mb-2">
                    <a href="#home" class="footer-link">Home</a>
                    <a href="#about" class="footer-link">About</a>
                    <a href="#features" class="footer-link">Features</a>
                    <?php if (!$isLoggedIn): ?>
                        <a href="<?= $baseUrl ?>/login.php" class="footer-link">Log In</a>
                    <?php endif; ?>
                </div>
                <div class="footer-copy">&copy; <?= date('Y') ?> GatherPoint. All rights reserved.</div>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>