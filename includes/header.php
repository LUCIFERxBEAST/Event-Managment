<?php
if (!isset($page_title)) {
    $page_title = 'HackHub | Premium Hackathon Experience';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($page_title); ?>
    </title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <nav class="navbar glass-panel">
        <a href="api/index.php" class="logo">
            🚀 HackHub
        </a>

        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Logged In: Only Profile and Logout -->
            <div class="flex-center gap-1">
                <a href="api/profile.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">
                    👤 My Profile
                </a>
                <a href="api/logout.php" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                    Logout
                </a>
            </div>
            <?php
else: ?>
            <!-- Guest: Home, Events, Login -->
            <a href="api/index.php">Home</a>
            <a href="api/events.php">Events</a>
            <a href="api/login.php" class="nav-btn">Login / Register</a>
            <?php
endif; ?>
        </div>

        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" aria-label="Toggle Navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
    </nav>

    <script>
        // Mobile Menu Toggle
        const mobileToggle = document.querySelector('.mobile-toggle');
        const navLinks = document.querySelector('.nav-links');

        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileToggle.classList.toggle('active');
        });
    </script>
    </div>
    </nav>

    <div class="main-content-wrapper">