<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$role = $isLoggedIn ? $_SESSION['role'] : '';
$fullName = $isLoggedIn ? $_SESSION['full_name'] : '';

// Get current filename to apply active class
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Slide-out Sidebar for all roles -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="close-btn" onclick="toggleSidebar()" style="position: absolute; top: 10px; right: 20px; font-size: 2rem;">&times;</a>
    <?php if (!$isLoggedIn): ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    <?php else: ?>
        <?php if ($role === 'Patient'): ?>
            <a href="patient-dashboard.php">Patient Dashboard</a>
            <a href="my-appointments.php">My Appointments</a>
        <?php elseif ($role === 'Doctor'): ?>
            <a href="doctor-dashboard.php">Doctor Dashboard</a>
        <?php elseif ($role === 'Admin'): ?>
            <a href="admin-dashboard.php">Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    <?php endif; ?>
</div>

<header>
    <div class="nav-container">
        <div style="display: flex; align-items: center;">
            <button class="open-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <a href="index.php" class="logo">
                <i class="fa-solid fa-heart-pulse"></i> MedCare
            </a>
        </div>
        <nav>
            <ul>
                <?php if (!$isLoggedIn): ?>
                    <li><a href="login.php" class="<?php echo $currentPage == 'login.php' ? 'active' : ''; ?>">Login</a></li>
                    <li><a href="register.php" class="<?php echo $currentPage == 'register.php' ? 'active' : ''; ?>">Register</a></li>
                <?php else: ?>
                    <?php if ($role === 'Patient'): ?>
                        <li><a href="patient-dashboard.php" class="<?php echo $currentPage == 'patient-dashboard.php' ? 'active' : ''; ?>">Doctors</a></li>
                        <li><a href="my-appointments.php" class="<?php echo $currentPage == 'my-appointments.php' ? 'active' : ''; ?>">My Appointments</a></li>
                    <?php elseif ($role === 'Doctor'): ?>
                        <li><a href="doctor-dashboard.php" class="<?php echo $currentPage == 'doctor-dashboard.php' ? 'active' : ''; ?>">My Schedule</a></li>
                    <?php elseif ($role === 'Admin'): ?>
                        <li><a href="admin-dashboard.php" class="<?php echo $currentPage == 'admin-dashboard.php' ? 'active' : ''; ?>">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><span class="user-tag"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($fullName); ?> (<?php echo $role; ?>)</span></li>
                    <li><a href="logout.php" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.8rem;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main>
<script>
function toggleSidebar() {
    let sidebar = document.getElementById("sidebar");
    if (sidebar.style.width === "250px") {
        sidebar.style.width = "0";
    } else {
        sidebar.style.width = "250px";
    }
}
</script>
