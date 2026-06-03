<?php
// models/UserModel.php
require_once __DIR__ . '/../config/database.php';

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'use_only_cookies' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'cookie_samesite' => 'Lax'
        ]);
    }
}

function user_register($fullName, $email, $password, $role) {
    $db = db_connect();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['status' => 'error', 'message' => 'Email is already registered.'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $db->beginTransaction();
        
        $stmtInsert = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$fullName, $email, $hashedPassword, $role]);
        $userId = $db->lastInsertId();
        
        // Auto-create empty profiles for Doctors or Patients
        if ($role === 'Patient') {
            $stmtProfile = $db->prepare("INSERT INTO patients (user_id) VALUES (?)");
            $stmtProfile->execute([$userId]);
        } elseif ($role === 'Doctor') {
            $stmtProfile = $db->prepare("INSERT INTO doctors (user_id, specialty) VALUES (?, 'General')");
            $stmtProfile->execute([$userId]);
        }
        
        $db->commit();
        return ['status' => 'success', 'user_id' => $userId];
    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function user_login($email, $password) {
    $db = db_connect();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['status' => 'error', 'message' => 'Invalid email or password.'];
    }
    
    // Fetch associated Patient ID or Doctor ID
    $patientId = null;
    $doctorId = null;
    
    if ($user['role'] === 'Patient') {
        $stmtProfile = $db->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $stmtProfile->execute([$user['user_id']]);
        $profile = $stmtProfile->fetch();
        $patientId = $profile ? $profile['patient_id'] : null;
    } elseif ($user['role'] === 'Doctor') {
        $stmtProfile = $db->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
        $stmtProfile->execute([$user['user_id']]);
        $profile = $stmtProfile->fetch();
        $doctorId = $profile ? $profile['doctor_id'] : null;
    }
    
    // Start session and regenerate session ID to prevent fixation
    start_secure_session();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['patient_id'] = $patientId;
    $_SESSION['doctor_id'] = $doctorId;
    
    return ['status' => 'success', 'user' => $user];
}

function user_get_by_id($userId) {
    $db = db_connect();
    $stmt = $db->prepare("SELECT user_id, full_name, email, role FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function user_is_logged_in() {
    start_secure_session();
    return isset($_SESSION['user_id']);
}

function user_require_role($allowedRoles) {
    if (!user_is_logged_in()) {
        header("Location: login.php");
        exit();
    }
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        $role = $_SESSION['role'];
        if ($role === 'Patient') {
            header("Location: patient-dashboard.php?error=unauthorized");
        } elseif ($role === 'Doctor') {
            header("Location: doctor-dashboard.php?error=unauthorized");
        } elseif ($role === 'Admin') {
            header("Location: admin-dashboard.php?error=unauthorized");
        } else {
            header("Location: login.php?error=unauthorized");
        }
        exit();
    }
}

// Global Validation Helper: Dates
function is_valid_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
