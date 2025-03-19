<!--
views/includes/header.php
The main header template that's included at the top of each page
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>

    <!-- Place these lines in the <head> section of your HTML -->
    <link rel="icon" type="image/svg+xml" href="/logo.svg">
    <link rel="alternate icon" href="/logo.ico"> <!-- Fallback for browsers that don't support SVG favicons -->

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vars.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/grid.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/badges.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/modal.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/breadcrumbs.css"> 
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/project-view.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/deliverable-view.css">
   
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/modal.js"></script> <!-- Add the custom modal JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/project-view.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?php echo BASE_URL; ?>" class="nav-brand"><img src="/logo.svg" /> <?php echo SITE_NAME; ?></a>
                <button class="nav-toggle" id="navToggle">☰</button>
                <ul class="nav-links" id="navLinks">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/projects.php">Projects</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/reports.php">Reports</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/profile.php">Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main">
        <div class="container">
            <?php
            // Display flash messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>