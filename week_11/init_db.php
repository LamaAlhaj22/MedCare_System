<?php
// init_db.php
require_once __DIR__ . '/config/database.php';

try {
    $db = db_connect();
    
    // Disable foreign key checks momentarily to drop tables
    $db->exec("PRAGMA foreign_keys = OFF;");
    $db->exec("DROP TABLE IF EXISTS appointments;");
    $db->exec("DROP TABLE IF EXISTS patients;");
    $db->exec("DROP TABLE IF EXISTS doctors;");
    $db->exec("DROP TABLE IF EXISTS users;");
    $db->exec("PRAGMA foreign_keys = ON;");
    
    echo "Creating tables...<br>";
    
    // 1. Users Table
    $db->exec("CREATE TABLE users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT CHECK(role IN ('Patient', 'Doctor', 'Admin')) NOT NULL
    );");
    
    // 2. Doctors Table
    $db->exec("CREATE TABLE doctors (
        doctor_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        specialty TEXT NOT NULL,
        phone TEXT,
        available_days TEXT,
        start_time TEXT,
        end_time TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );");
    
    // 3. Patients Table
    $db->exec("CREATE TABLE patients (
        patient_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        phone TEXT,
        gender TEXT CHECK(gender IN ('Male', 'Female', 'Other')),
        date_of_birth TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );");
    
    // 4. Appointments Table
    $db->exec("CREATE TABLE appointments (
        appointment_id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id INTEGER NOT NULL,
        doctor_id INTEGER NOT NULL,
        appointment_date TEXT NOT NULL,
        appointment_time TEXT NOT NULL,
        status TEXT CHECK(status IN ('Pending', 'Confirmed', 'Completed', 'Cancelled')) DEFAULT 'Pending',
        notes TEXT,
        FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
    );");
    
    echo "Tables created successfully!<br>Seeding data...<br>";
    
    // Passwords hashed using bcrypt
    $hashedPassword = password_hash('1234', PASSWORD_DEFAULT);
    
    // Insert Users
    $stmtUser = $db->prepare("INSERT INTO users (user_id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmtUser->execute([1, 'Aya Ali', 'aya@gmail.com', $hashedPassword, 'Patient']);
    $stmtUser->execute([2, 'Dr. Ahmad', 'ahmad@gmail.com', $hashedPassword, 'Doctor']);
    $stmtUser->execute([3, 'Admin User', 'admin@gmail.com', $hashedPassword, 'Admin']);
    $stmtUser->execute([4, 'Dr. Sara', 'sara@gmail.com', $hashedPassword, 'Doctor']);
    $stmtUser->execute([5, 'Mlaak Al-Shawa', 'mlaak@gmail.com', $hashedPassword, 'Patient']);
    $stmtUser->execute([6, 'Lama Alhaj', 'lama@gmail.com', $hashedPassword, 'Patient']);
    
    // Insert Patients
    $stmtPatient = $db->prepare("INSERT INTO patients (patient_id, user_id, phone, gender, date_of_birth) VALUES (?, ?, ?, ?, ?)");
    $stmtPatient->execute([1, 1, '0599111222', 'Female', '2003-08-22']);
    $stmtPatient->execute([2, 5, '0599333444', 'Female', '2003-02-02']);
    $stmtPatient->execute([3, 6, '0599555666', 'Female', '2003-03-03']);
    
    // Insert Doctors
    $stmtDoctor = $db->prepare("INSERT INTO doctors (doctor_id, user_id, specialty, phone, available_days, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtDoctor->execute([1, 2, 'Cardiology', '0599888777', 'Sunday-Monday', '09:00 AM', '03:00 PM']);
    $stmtDoctor->execute([2, 4, 'Dermatology', '0599000000', 'Tuesday-Wednesday', '10:00 AM', '02:00 PM']);
    
    // Insert Appointments
    $stmtAppointment = $db->prepare("INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtAppointment->execute([1, 1, 1, '2026-05-05', '10:00 AM', 'Pending', 'General Checkup']);
    $stmtAppointment->execute([2, 2, 2, '2026-06-05', '11:30 AM', 'Confirmed', 'Skin Consultation']);
    
    // Reset AUTOINCREMENT counters
    $db->exec("UPDATE sqlite_sequence SET seq = 6 WHERE name = 'users';");
    $db->exec("UPDATE sqlite_sequence SET seq = 3 WHERE name = 'patients';");
    $db->exec("UPDATE sqlite_sequence SET seq = 2 WHERE name = 'doctors';");
    $db->exec("UPDATE sqlite_sequence SET seq = 2 WHERE name = 'appointments';");
    
    echo "Database successfully initialized and seeded with Week 9 sample data!";
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage();
}
