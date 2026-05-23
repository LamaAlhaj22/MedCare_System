<?php
// models/DoctorModel.php
require_once __DIR__ . '/../config/database.php';

function doctor_get_by_user_id($userId) {
    $db = db_connect();
    $stmt = $db->prepare("SELECT d.*, u.full_name, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function doctor_get_by_id($doctorId) {
    $db = db_connect();
    $stmt = $db->prepare("SELECT d.*, u.full_name, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?");
    $stmt->execute([$doctorId]);
    return $stmt->fetch();
}

function doctor_get_all($specialtyFilter = null, $searchQuery = null) {
    $db = db_connect();
    
    $query = "SELECT d.*, u.full_name, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE 1=1";
    $params = [];
    
    if ($specialtyFilter && $specialtyFilter !== 'all') {
        $query .= " AND d.specialty = ?";
        $params[] = $specialtyFilter;
    }
    
    if ($searchQuery) {
        $query .= " AND u.full_name LIKE ?";
        $params[] = '%' . $searchQuery . '%';
    }
    
    $query .= " ORDER BY u.full_name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function doctor_get_specialties() {
    $db = db_connect();
    $stmt = $db->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function doctor_update($doctorId, $fullName, $specialty, $phone, $availableDays, $startTime, $endTime) {
    $db = db_connect();
    try {
        $db->beginTransaction();
        
        // 1. Get user_id associated with this doctor
        $stmtGet = $db->prepare("SELECT user_id FROM doctors WHERE doctor_id = ?");
        $stmtGet->execute([$doctorId]);
        $row = $stmtGet->fetch();
        if (!$row) {
            throw new Exception("Doctor not found.");
        }
        $userId = $row['user_id'];
        
        // 2. Update users table (full_name)
        $stmtUser = $db->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmtUser->execute([$fullName, $userId]);
        
        // 3. Update doctors table
        $stmtDoctor = $db->prepare("UPDATE doctors SET specialty = ?, phone = ?, available_days = ?, start_time = ?, end_time = ? WHERE doctor_id = ?");
        $stmtDoctor->execute([$specialty, $phone, $availableDays, $startTime, $endTime, $doctorId]);
        
        $db->commit();
        
        // Update session name if current user is this doctor
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['full_name'] = $fullName;
        }
        
        return ['status' => 'success'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function doctor_delete($doctorId) {
    $db = db_connect();
    try {
        $db->beginTransaction();
        
        // Find associated user_id
        $stmtGet = $db->prepare("SELECT user_id FROM doctors WHERE doctor_id = ?");
        $stmtGet->execute([$doctorId]);
        $row = $stmtGet->fetch();
        if ($row) {
            $userId = $row['user_id'];
            // Deleting user automatically deletes doctor due to CASCADE
            $stmtDel = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmtDel->execute([$userId]);
        }
        
        $db->commit();
        return ['status' => 'success'];
    } catch (Exception $e) {
        $db->rollBack();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
