<?php
// index.php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'Patient') {
        header("Location: patient-dashboard.php");
    } elseif ($role === 'Doctor') {
        header("Location: doctor-dashboard.php");
    } elseif ($role === 'Admin') {
        header("Location: admin-dashboard.php");
    }
} else {
    header("Location: login.php");
}
exit();
