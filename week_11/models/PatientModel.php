<?php
// models/PatientModel.php
require_once __DIR__ . '/../config/database.php';

function patient_get_by_user_id($userId) {
    $db = db_connect();
    $stmt = $db->prepare("SELECT p.*, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function patient_get_by_id($patientId) {
    $db = db_connect();
    $stmt = $db->prepare("SELECT p.*, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
    $stmt->execute([$patientId]);
    return $stmt->fetch();
}

function patient_get_all() {
    $db = db_connect();
    $stmt = $db->query("SELECT p.*, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.user_id ORDER BY u.full_name ASC");
    return $stmt->fetchAll();
}

function patient_update($patientId, $fullName, $phone, $gender, $dob) {
    $db = db_connect();
    try {
        $db->beginTransaction();
        
        // 1. Get user_id associated with this patient
        $stmtGet = $db->prepare("SELECT user_id FROM patients WHERE patient_id = ?");
        $stmtGet->execute([$patientId]);
        $row = $stmtGet->fetch();
        if (!$row) {
            throw new Exception("Patient not found.");
        }
        $userId = $row['user_id'];
        
        // 2. Update users table (full_name)
        $stmtUser = $db->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmtUser->execute([$fullName, $userId]);
        
        // 3. Update patients table
        $stmtPatient = $db->prepare("UPDATE patients SET phone = ?, gender = ?, date_of_birth = ? WHERE patient_id = ?");
        $stmtPatient->execute([$phone, $gender, $dob, $patientId]);
        
        $db->commit();
        
        // Update session name if current user is this patient
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

function patient_delete($patientId) {
    $db = db_connect();
    try {
        $db->beginTransaction();
        
        // Find associated user_id
        $stmtGet = $db->prepare("SELECT user_id FROM patients WHERE patient_id = ?");
        $stmtGet->execute([$patientId]);
        $row = $stmtGet->fetch();
        if ($row) {
            $userId = $row['user_id'];
            // Deleting user automatically deletes patient due to CASCADE
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
