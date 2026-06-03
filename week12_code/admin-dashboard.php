<?php
// admin-dashboard.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/DoctorModel.php';
require_once __DIR__ . '/models/PatientModel.php';
require_once __DIR__ . '/models/AppointmentModel.php';

user_require_role(['Admin']);

$successMessage = '';
$errorMessage = '';

if (($_GET['error'] ?? '') === 'unauthorized') {
    $errorMessage = 'Access Denied: You do not have permission to view that page.';
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_doctor') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '1234'; // Default password if empty
        $specialty = trim($_POST['specialty'] ?? 'General');
        $phone = trim($_POST['phone'] ?? '');
        $availableDays = trim($_POST['available_days'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        
        if ($fullName === '' || $email === '') {
            $errorMessage = 'Name and email are required.';
        } else {
            $res = user_register($fullName, $email, $password, 'Doctor');
            if ($res['status'] === 'success') {
                $docProfile = doctor_get_by_user_id($res['user_id']);
                if ($docProfile) {
                    doctor_update($docProfile['doctor_id'], $fullName, $specialty, $phone, $availableDays, $startTime, $endTime);
                }
                $successMessage = "Doctor '$fullName' successfully registered.";
            } else {
                $errorMessage = 'Failed to create doctor: ' . $res['message'];
            }
        }
    } elseif ($action === 'edit_doctor') {
        $doctorId = intval($_POST['doctor_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $availableDays = trim($_POST['available_days'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        
        if ($doctorId > 0 && $fullName !== '') {
            $res = doctor_update($doctorId, $fullName, $specialty, $phone, $availableDays, $startTime, $endTime);
            if ($res['status'] === 'success') {
                $successMessage = 'Doctor details updated successfully.';
            } else {
                $errorMessage = 'Update failed: ' . $res['message'];
            }
        } else {
            $errorMessage = 'Invalid input parameters.';
        }
    } elseif ($action === 'delete_doctor') {
        $doctorId = intval($_POST['doctor_id'] ?? 0);
        if ($doctorId > 0) {
            $res = doctor_delete($doctorId);
            if ($res['status'] === 'success') {
                $successMessage = 'Doctor successfully deleted.';
            } else {
                $errorMessage = 'Failed to delete: ' . $res['message'];
            }
        }
    } elseif ($action === 'add_patient') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '1234';
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'Other';
        $dob = $_POST['dob'] ?? '';
        
        if ($fullName === '' || $email === '') {
            $errorMessage = 'Name and email are required.';
        } else {
            $res = user_register($fullName, $email, $password, 'Patient');
            if ($res['status'] === 'success') {
                $patProfile = patient_get_by_user_id($res['user_id']);
                if ($patProfile) {
                    patient_update($patProfile['patient_id'], $fullName, $phone, $gender, $dob);
                }
                $successMessage = "Patient '$fullName' successfully registered.";
            } else {
                $errorMessage = 'Failed to create patient: ' . $res['message'];
            }
        }
    } elseif ($action === 'edit_patient') {
        $patientId = intval($_POST['patient_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'Other';
        $dob = $_POST['dob'] ?? '';
        
        if ($patientId > 0 && $fullName !== '') {
            $res = patient_update($patientId, $fullName, $phone, $gender, $dob);
            if ($res['status'] === 'success') {
                $successMessage = 'Patient details updated successfully.';
            } else {
                $errorMessage = 'Update failed: ' . $res['message'];
            }
        } else {
            $errorMessage = 'Invalid input parameters.';
        }
    } elseif ($action === 'delete_patient') {
        $patientId = intval($_POST['patient_id'] ?? 0);
        if ($patientId > 0) {
            $res = patient_delete($patientId);
            if ($res['status'] === 'success') {
                $successMessage = 'Patient successfully deleted.';
            } else {
                $errorMessage = 'Failed to delete patient: ' . $res['message'];
            }
        }
    } elseif ($action === 'delete_appointment') {
        $appointmentId = intval($_POST['appointment_id'] ?? 0);
        if ($appointmentId > 0) {
            $res = appointment_delete($appointmentId);
            if ($res['status'] === 'success') {
                $successMessage = 'Appointment successfully cancelled and removed.';
            } else {
                $errorMessage = 'Failed to remove appointment: ' . $res['message'];
            }
        }
    }
}

// Fetch lists
$doctors = doctor_get_all();
$patients = patient_get_all();
$appointments = appointment_get_all();

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h2>Administrative Control Panel</h2>
    <p style="color: var(--text-secondary);">Manage system users, medical practitioners, and appointments</p>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>

<!-- Tabs -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; flex-wrap: wrap;">
    <button class="btn btn-secondary btn-sm" id="btn-doctors" onclick="switchTab('doctors')"><i class="fa-solid fa-user-doctor"></i> Manage Doctors</button>
    <button class="btn btn-secondary btn-sm" id="btn-patients" onclick="switchTab('patients')"><i class="fa-solid fa-users"></i> Manage Patients</button>
    <button class="btn btn-secondary btn-sm" id="btn-appointments" onclick="switchTab('appointments')"><i class="fa-solid fa-calendar-check"></i> Manage Appointments</button>
</div>

<!-- DOCTORS TAB -->
<div id="tab-doctors" class="tab-content">
    <div class="flex-between mb-4">
        <h3>Doctors Directory</h3>
        <button class="btn btn-primary btn-sm" onclick="openAddDoctorModal()"><i class="fa-solid fa-plus"></i> Add New Doctor</button>
    </div>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                    <th style="padding: 0.75rem;">Name</th>
                    <th style="padding: 0.75rem;">Email</th>
                    <th style="padding: 0.75rem;">Specialty</th>
                    <th style="padding: 0.75rem;">Schedule Days</th>
                    <th style="padding: 0.75rem;">Hours</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doc): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($doc['full_name']); ?></td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($doc['email']); ?></td>
                        <td style="padding: 0.75rem;"><span class="badge badge-confirmed"><?php echo htmlspecialchars($doc['specialty']); ?></span></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo htmlspecialchars($doc['available_days'] ?: '-'); ?></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo $doc['start_time'] ? ($doc['start_time'] . ' - ' . $doc['end_time']) : '-'; ?></td>
                        <td style="padding: 0.75rem; text-align: right;">
                            <button class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.6rem; margin-right: 0.25rem;" onclick="openEditDoctorModal(<?php echo htmlspecialchars(json_encode($doc)); ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
                            <form action="admin-dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this doctor?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_doctor">
                                <input type="hidden" name="doctor_id" value="<?php echo $doc['doctor_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.3rem 0.6rem;"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PATIENTS TAB -->
<div id="tab-patients" class="tab-content" style="display: none;">
    <div class="flex-between mb-4">
        <h3>Patients Directory</h3>
        <button class="btn btn-primary btn-sm" onclick="openAddPatientModal()"><i class="fa-solid fa-plus"></i> Add New Patient</button>
    </div>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                    <th style="padding: 0.75rem;">Name</th>
                    <th style="padding: 0.75rem;">Email</th>
                    <th style="padding: 0.75rem;">Gender</th>
                    <th style="padding: 0.75rem;">DOB</th>
                    <th style="padding: 0.75rem;">Phone</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $pat): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($pat['full_name']); ?></td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($pat['email']); ?></td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($pat['gender'] ?: '-'); ?></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo htmlspecialchars($pat['date_of_birth'] ?: '-'); ?></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo htmlspecialchars($pat['phone'] ?: '-'); ?></td>
                        <td style="padding: 0.75rem; text-align: right;">
                            <button class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.6rem; margin-right: 0.25rem;" onclick="openEditPatientModal(<?php echo htmlspecialchars(json_encode($pat)); ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
                            <form action="admin-dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this patient?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_patient">
                                <input type="hidden" name="patient_id" value="<?php echo $pat['patient_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.3rem 0.6rem;"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- APPOINTMENTS TAB -->
<div id="tab-appointments" class="tab-content" style="display: none;">
    <div class="mb-4">
        <h3>Appointments Master Schedule</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-secondary);">
                    <th style="padding: 0.75rem;">Patient</th>
                    <th style="padding: 0.75rem;">Doctor</th>
                    <th style="padding: 0.75rem;">Specialty</th>
                    <th style="padding: 0.75rem;">Date</th>
                    <th style="padding: 0.75rem;">Time</th>
                    <th style="padding: 0.75rem;">Status</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem; font-weight: 500;"><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                        <td style="padding: 0.75rem; color: var(--text-secondary);"><?php echo htmlspecialchars($appt['doctor_specialty']); ?></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                        <td style="padding: 0.75rem; font-size: 0.9rem;"><?php echo htmlspecialchars($appt['appointment_time']); ?></td>
                        <td style="padding: 0.75rem;"><span class="badge badge-<?php echo strtolower($appt['status']); ?>"><?php echo $appt['status']; ?></span></td>
                        <td style="padding: 0.75rem; text-align: right;">
                            <form action="admin-dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel and remove this appointment?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_appointment">
                                <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.3rem 0.6rem;"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================= MODALS OVERLAYS ================= -->

<!-- Add Doctor Modal -->
<div id="addDoctorModal" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: auto; text-align: left;">
        <span class="close" onclick="closeAddDoctorModal()" style="font-size: 1.5rem; position: absolute; right: 15px; top: 10px;">&times;</span>
        <h3 class="mb-4"><i class="fa-solid fa-user-doctor" style="color: var(--primary); margin-right: 0.5rem;"></i> Add Practitioner Profile</h3>
        
        <form action="admin-dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_doctor">
            
            <div class="form-group">
                <label for="add_doc_name">Doctor's Full Name</label>
                <input type="text" id="add_doc_name" name="full_name" required placeholder="Dr. Sarah Connor">
            </div>
            
            <div class="form-group">
                <label for="add_doc_email">Email Address</label>
                <input type="email" id="add_doc_email" name="email" required placeholder="sarah@medcare.com">
            </div>
            
            <div class="form-group">
                <label for="add_doc_password">Temporary Password</label>
                <input type="password" id="add_doc_password" name="password" required value="1234">
            </div>
            
            <div class="form-group">
                <label for="add_doc_specialty">Specialty</label>
                <input type="text" id="add_doc_specialty" name="specialty" value="General Cardiology" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="add_doc_phone">Phone</label>
                    <input type="text" id="add_doc_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="add_doc_days">Schedule Days</label>
                    <input type="text" id="add_doc_days" name="available_days" placeholder="Sunday-Monday">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="add_doc_start">Shift Start</label>
                    <input type="text" id="add_doc_start" name="start_time" placeholder="09:00 AM">
                </div>
                <div class="form-group">
                    <label for="add_doc_end">Shift End</label>
                    <input type="text" id="add_doc_end" name="end_time" placeholder="03:00 PM">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Add Practitioner</button>
        </form>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div id="editDoctorModal" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: auto; text-align: left;">
        <span class="close" onclick="closeEditDoctorModal()" style="font-size: 1.5rem; position: absolute; right: 15px; top: 10px;">&times;</span>
        <h3 class="mb-4"><i class="fa-solid fa-user-doctor" style="color: var(--primary); margin-right: 0.5rem;"></i> Edit Practitioner Profile</h3>
        
        <form action="admin-dashboard.php" method="POST">
            <input type="hidden" name="action" value="edit_doctor">
            <input type="hidden" name="doctor_id" id="edit_doc_id">
            
            <div class="form-group">
                <label for="edit_doc_name">Doctor's Name</label>
                <input type="text" id="edit_doc_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_doc_specialty">Specialty</label>
                <input type="text" id="edit_doc_specialty" name="specialty" required>
            </div>
            
            <div class="form-group">
                <label for="edit_doc_phone">Phone</label>
                <input type="text" id="edit_doc_phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="edit_doc_days">Schedule Days</label>
                <input type="text" id="edit_doc_days" name="available_days">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_doc_start">Shift Start</label>
                    <input type="text" id="edit_doc_start" name="start_time">
                </div>
                <div class="form-group">
                    <label for="edit_doc_end">Shift End</label>
                    <input type="text" id="edit_doc_end" name="end_time">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Changes</button>
        </form>
    </div>
</div>

<!-- Add Patient Modal -->
<div id="addPatientModal" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: auto; text-align: left;">
        <span class="close" onclick="closeAddPatientModal()" style="font-size: 1.5rem; position: absolute; right: 15px; top: 10px;">&times;</span>
        <h3 class="mb-4"><i class="fa-solid fa-users" style="color: var(--primary); margin-right: 0.5rem;"></i> Add Patient Profile</h3>
        
        <form action="admin-dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_patient">
            
            <div class="form-group">
                <label for="add_pat_name">Patient's Full Name</label>
                <input type="text" id="add_pat_name" name="full_name" required placeholder="John Connor">
            </div>
            
            <div class="form-group">
                <label for="add_pat_email">Email Address</label>
                <input type="email" id="add_pat_email" name="email" required placeholder="john@example.com">
            </div>
            
            <div class="form-group">
                <label for="add_pat_password">Temporary Password</label>
                <input type="password" id="add_pat_password" name="password" required value="1234">
            </div>
            
            <div class="form-group">
                <label for="add_pat_gender">Gender</label>
                <select id="add_pat_gender" name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="add_pat_phone">Phone</label>
                    <input type="text" id="add_pat_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="add_pat_dob">Date of Birth</label>
                    <input type="date" id="add_pat_dob" name="dob">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Add Patient</button>
        </form>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editPatientModal" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: auto; text-align: left;">
        <span class="close" onclick="closeEditPatientModal()" style="font-size: 1.5rem; position: absolute; right: 15px; top: 10px;">&times;</span>
        <h3 class="mb-4"><i class="fa-solid fa-users" style="color: var(--primary); margin-right: 0.5rem;"></i> Edit Patient Profile</h3>
        
        <form action="admin-dashboard.php" method="POST">
            <input type="hidden" name="action" value="edit_patient">
            <input type="hidden" name="patient_id" id="edit_pat_id">
            
            <div class="form-group">
                <label for="edit_pat_name">Patient's Name</label>
                <input type="text" id="edit_pat_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="edit_pat_gender">Gender</label>
                <select id="edit_pat_gender" name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="edit_pat_phone">Phone</label>
                    <input type="text" id="edit_pat_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="edit_pat_dob">Date of Birth</label>
                    <input type="date" id="edit_pat_dob" name="dob">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Changes</button>
        </form>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll(".tab-content").forEach(el => el.style.display = 'none');
    document.getElementById("tab-" + tabId).style.display = 'block';
    
    document.getElementById("btn-doctors").className = "btn btn-secondary btn-sm";
    document.getElementById("btn-patients").className = "btn btn-secondary btn-sm";
    document.getElementById("btn-appointments").className = "btn btn-secondary btn-sm";
    
    document.getElementById("btn-" + tabId).className = "btn btn-primary btn-sm";
    localStorage.setItem("admin_active_tab", tabId);
}

// Modal handling functions
function openAddDoctorModal() { document.getElementById("addDoctorModal").style.display = "flex"; }
function closeAddDoctorModal() { document.getElementById("addDoctorModal").style.display = "none"; }

function openEditDoctorModal(doc) {
    document.getElementById("edit_doc_id").value = doc.doctor_id;
    document.getElementById("edit_doc_name").value = doc.full_name;
    document.getElementById("edit_doc_specialty").value = doc.specialty;
    document.getElementById("edit_doc_phone").value = doc.phone || '';
    document.getElementById("edit_doc_days").value = doc.available_days || '';
    document.getElementById("edit_doc_start").value = doc.start_time || '';
    document.getElementById("edit_doc_end").value = doc.end_time || '';
    document.getElementById("editDoctorModal").style.display = "flex";
}
function closeEditDoctorModal() { document.getElementById("editDoctorModal").style.display = "none"; }

function openAddPatientModal() { document.getElementById("addPatientModal").style.display = "flex"; }
function closeAddPatientModal() { document.getElementById("addPatientModal").style.display = "none"; }

function openEditPatientModal(pat) {
    document.getElementById("edit_pat_id").value = pat.patient_id;
    document.getElementById("edit_pat_name").value = pat.full_name;
    document.getElementById("edit_pat_gender").value = pat.gender || 'Other';
    document.getElementById("edit_pat_phone").value = pat.phone || '';
    document.getElementById("edit_pat_dob").value = pat.date_of_birth || '';
    document.getElementById("editPatientModal").style.display = "flex";
}
function closeEditPatientModal() { document.getElementById("editPatientModal").style.display = "none"; }

// Close modals on overlay click
window.onclick = function(event) {
    let addDoc = document.getElementById("addDoctorModal");
    let editDoc = document.getElementById("editDoctorModal");
    let addPat = document.getElementById("addPatientModal");
    let editPat = document.getElementById("editPatientModal");
    
    if (event.target === addDoc) addDoc.style.display = "none";
    if (event.target === editDoc) editDoc.style.display = "none";
    if (event.target === addPat) addPat.style.display = "none";
    if (event.target === editPat) editPat.style.display = "none";
}

document.addEventListener("DOMContentLoaded", () => {
    let activeTab = localStorage.getItem("admin_active_tab") || "doctors";
    switchTab(activeTab);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
