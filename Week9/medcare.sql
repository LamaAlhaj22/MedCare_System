--CREATE TABLE

CREATE TABLE users (
    user_id NUMBER PRIMARY KEY,
    full_name VARCHAR2(100) NOT NULL,
    email VARCHAR2(100) UNIQUE NOT NULL,
    password VARCHAR2(100) NOT NULL,
    role VARCHAR2(20) NOT NULL
);

CREATE TABLE doctors (
    doctor_id NUMBER PRIMARY KEY,
    user_id NUMBER UNIQUE NOT NULL,
    specialty VARCHAR2(100) NOT NULL,
    phone VARCHAR2(20),
    available_days VARCHAR2(100),
    start_time VARCHAR2(20),
    end_time VARCHAR2(20),
    CONSTRAINT fk_doctor_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE patients (
    patient_id NUMBER PRIMARY KEY,
    user_id NUMBER UNIQUE NOT NULL,
    phone VARCHAR2(20),
    gender VARCHAR2(10),
    date_of_birth DATE,
    CONSTRAINT fk_patient_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE appointments (
    appointment_id NUMBER PRIMARY KEY,
    patient_id NUMBER NOT NULL,
    doctor_id NUMBER NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR2(20) NOT NULL,
    status VARCHAR2(30) DEFAULT 'Pending',
    notes VARCHAR2(255),
    CONSTRAINT fk_appointment_patient
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    CONSTRAINT fk_appointment_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
);

--INSERT INTO

INSERT INTO users VALUES
(1, 'Nada Raed', 'nada@gmail.com', '1234', 'Patient');

INSERT INTO users VALUES
(2, 'Dr. Ahmad', 'ahmad@gmail.com', '1234', 'Doctor');

INSERT INTO users VALUES
(3, 'Admin User', 'admin@gmail.com', '1234', 'Admin');

INSERT INTO users VALUES
(4, 'Dr. Lama', 'lama@gmail.com', '1234', 'Doctor');

INSERT INTO users VALUES
(5, 'lama Ali', 'lama@gmail.com', '1234', 'Patient');

INSERT INTO patients VALUES
(1, 1, '0599123456', 'Female', DATE '2003-04-03');

INSERT INTO patients VALUES
(2, 5, '0599109876', 'Female', DATE '2003-04-05');

INSERT INTO doctors VALUES
(1, 2, 'Cardiology', '0599888777',
'Sunday-Monday', '09:00 AM', '03:00 PM');

INSERT INTO doctors VALUES
(2, 4, 'Dermatology', '0599000000',
'Tuesday-Wednesday', '10:00 AM', '02:00 PM'

INSERT INTO appointments VALUES
(1, 1, 1,
DATE '2026-05-05',
'10:00 AM',
'Pending',
'General Checkup');

INSERT INTO appointments VALUES
(2, 2, 2,
DATE '2026-06-05',
'11:30 AM',
'Confirmed',
'Skin Consulation');