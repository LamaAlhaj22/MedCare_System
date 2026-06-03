<?php
// models/AppointmentModel.php
require_once __DIR__ . '/../config/database.php';

function appointment_create($patientId, $doctorId, $date, $time, $notes) {
    require_once __DIR__ . '/UserModel.php';
    if (!is_valid_date($date)) {
        return ['status' => 'error', 'message' => 'Invalid date format. Must be YYYY-MM-DD.'];
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return ['status' => 'error', 'message' => 'Cannot book appointments in the past.'];
    }
    
    $db = db_connect();
    try {
        $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$patientId, $doctorId, $date, $time, $notes]);
        return ['status' => 'success', 'appointment_id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function appointment_get_by_id($appointmentId) {
    $db = db_connect();
    $stmt = $db->prepare("
        SELECT a.*, 
               up.full_name AS patient_name, pp.phone AS patient_phone,
               ud.full_name AS doctor_name, d.specialty AS doctor_specialty
        FROM appointments a
        JOIN patients pp ON a.patient_id = pp.patient_id
        JOIN users up ON pp.user_id = up.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users ud ON d.user_id = ud.user_id
        WHERE a.appointment_id = ?
    ");
    $stmt->execute([$appointmentId]);
    return $stmt->fetch();
}

function appointment_get_by_patient($patientId) {
    $db = db_connect();
    $stmt = $db->prepare("
        SELECT a.*, ud.full_name AS doctor_name, d.specialty AS doctor_specialty
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users ud ON d.user_id = ud.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$patientId]);
    return $stmt->fetchAll();
}

function appointment_get_by_doctor($doctorId) {
    $db = db_connect();
    $stmt = $db->prepare("
        SELECT a.*, up.full_name AS patient_name, pp.phone AS patient_phone
        FROM appointments a
        JOIN patients pp ON a.patient_id = pp.patient_id
        JOIN users up ON pp.user_id = up.user_id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctorId]);
    return $stmt->fetchAll();
}

function appointment_get_all() {
    $db = db_connect();
    $stmt = $db->query("
        SELECT a.*, 
               up.full_name AS patient_name, 
               ud.full_name AS doctor_name, d.specialty AS doctor_specialty
        FROM appointments a
        JOIN patients pp ON a.patient_id = pp.patient_id
        JOIN users up ON pp.user_id = up.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users ud ON d.user_id = ud.user_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    return $stmt->fetchAll();
}

function appointment_update($appointmentId, $date, $time, $notes) {
    require_once __DIR__ . '/UserModel.php';
    if (!is_valid_date($date)) {
        return ['status' => 'error', 'message' => 'Invalid date format. Must be YYYY-MM-DD.'];
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return ['status' => 'error', 'message' => 'Cannot reschedule appointments to a past date.'];
    }
    
    $db = db_connect();
    try {
        $stmt = $db->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, notes = ? WHERE appointment_id = ?");
        $stmt->execute([$date, $time, $notes, $appointmentId]);
        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function appointment_update_status($appointmentId, $status) {
    if (!in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
        return ['status' => 'error', 'message' => 'Invalid status selected.'];
    }
    
    $db = db_connect();
    try {
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->execute([$status, $appointmentId]);
        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function appointment_delete($appointmentId) {
    $db = db_connect();
    try {
        $stmt = $db->prepare("DELETE FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        return ['status' => 'success'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
