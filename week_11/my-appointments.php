<?php
// my-appointments.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/AppointmentModel.php';

user_require_role(['Patient']);

$patientId = $_SESSION['patient_id'];
$successMessage = '';
$errorMessage = '';

// Handle actions (Edit, Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    
    if ($action === 'cancel' && $appointmentId > 0) {
        // Validate appointment belongs to patient
        $appt = appointment_get_by_id($appointmentId);
        if ($appt && $appt['patient_id'] == $patientId) {
            $res = appointment_delete($appointmentId);
            if ($res['status'] === 'success') {
                $successMessage = 'Appointment successfully cancelled and removed.';
            } else {
                $errorMessage = 'Failed to cancel appointment: ' . $res['message'];
            }
        } else {
            $errorMessage = 'Unauthorized action.';
        }
    } elseif ($action === 'edit' && $appointmentId > 0) {
        $date = trim($_POST['date'] ?? '');
        $time = trim($_POST['time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        $appt = appointment_get_by_id($appointmentId);
        if ($appt && $appt['patient_id'] == $patientId) {
            if ($date === '' || $time === '') {
                $errorMessage = 'Date and time are required.';
            } else {
                $res = appointment_update($appointmentId, $date, $time, $notes);
                if ($res['status'] === 'success') {
                    $successMessage = 'Appointment successfully rescheduled.';
                } else {
                    $errorMessage = 'Failed to reschedule: ' . $res['message'];
                }
            }
        } else {
            $errorMessage = 'Unauthorized action.';
        }
    }
}

// Check for redirect notifications
$notif = $_GET['success'] ?? '';
if ($notif === 'booked') {
    $successMessage = 'Appointment booked successfully!';
}

$appointments = appointment_get_by_patient($patientId);

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4 flex-between">
    <div>
        <h2>My Clinical Appointments</h2>
        <p style="color: var(--text-secondary);">Manage and monitor your upcoming appointments and schedules</p>
    </div>
    <a href="patient-dashboard.php" class="btn btn-primary"><i class="fa-solid fa-calendar-plus"></i> Book New</a>
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

<?php if (empty($appointments)): ?>
    <div class="card text-center" style="padding: 3rem;">
        <i class="fa-solid fa-calendar-xmark" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
        <h3>No Appointments Yet</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">You don't have any booked appointments with us.</p>
        <a href="patient-dashboard.php" class="btn btn-primary">Book an Appointment Now</a>
    </div>
<?php else: ?>
    <div class="appointments-list">
        <?php foreach ($appointments as $appt): ?>
            <div class="card appointment-item">
                <div class="appointment-details">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <h4 style="font-size: 1.2rem; margin: 0;"><?php echo htmlspecialchars($appt['doctor_name']); ?></h4>
                        <span class="badge badge-confirmed" style="font-size: 0.65rem; padding: 0.2rem 0.5rem;"><?php echo htmlspecialchars($appt['doctor_specialty']); ?></span>
                    </div>
                    <p><i class="fa-solid fa-calendar-day" style="width: 1.25rem; color: var(--primary);"></i> <strong>Date:</strong> <?php echo htmlspecialchars($appt['appointment_date']); ?></p>
                    <p><i class="fa-solid fa-clock" style="width: 1.25rem; color: var(--primary);"></i> <strong>Time:</strong> <?php echo htmlspecialchars($appt['appointment_time']); ?></p>
                    <?php if ($appt['notes']): ?>
                        <p><i class="fa-solid fa-file-waveform" style="width: 1.25rem; color: var(--primary);"></i> <strong>Symptoms/Notes:</strong> <?php echo htmlspecialchars($appt['notes']); ?></p>
                    <?php endif; ?>
                    <div class="mt-2">
                        <strong>Status:</strong> 
                        <span class="badge badge-<?php echo strtolower($appt['status']); ?>"><?php echo $appt['status']; ?></span>
                    </div>
                </div>
                
                <div class="appointment-actions" style="display: flex; gap: 0.5rem;">
                    <?php if (in_array($appt['status'], ['Pending', 'Confirmed'])): ?>
                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($appt)); ?>)">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        <form action="my-appointments.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this appointment?');" style="display: inline;">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i> Cancel</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Edit Modal Layer -->
<div id="editModal" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; margin: auto; text-align: left;">
        <span class="close" onclick="closeEditModal()" style="font-size: 1.5rem; position: absolute; right: 15px; top: 10px;">&times;</span>
        <h3 class="mb-4"><i class="fa-solid fa-calendar-check" style="color: var(--primary); margin-right: 0.5rem;"></i> Reschedule Appointment</h3>
        
        <form action="my-appointments.php" method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="appointment_id" id="edit_appointment_id">
            
            <div class="form-group">
                <label>Doctor</label>
                <input type="text" id="edit_doctor_name" readonly style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
            </div>
            
            <div class="form-group">
                <label for="edit_date">Reschedule Date</label>
                <input type="date" id="edit_date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_time">Reschedule Time</label>
                <input type="time" id="edit_time" name="time" required>
            </div>
            
            <div class="form-group">
                <label for="edit_notes">Notes / Symptoms</label>
                <textarea id="edit_notes" name="notes" rows="3"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeEditModal()">Close</button>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(appt) {
    document.getElementById("edit_appointment_id").value = appt.appointment_id;
    document.getElementById("edit_doctor_name").value = appt.doctor_name + " (" + appt.doctor_specialty + ")";
    document.getElementById("edit_date").value = appt.appointment_date;
    document.getElementById("edit_time").value = appt.appointment_time;
    document.getElementById("edit_notes").value = appt.notes || '';
    
    let modal = document.getElementById("editModal");
    modal.style.display = "flex";
}

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}

window.onclick = function(event) {
    let modal = document.getElementById("editModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
