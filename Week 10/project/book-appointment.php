<?php
// book-appointment.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/DoctorModel.php';
require_once __DIR__ . '/models/AppointmentModel.php';

user_require_role(['Patient']);

$doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctor = doctor_get_by_id($doctorId);

if (!$doctor) {
    header("Location: patient-dashboard.php");
    exit();
}

$patientId = $_SESSION['patient_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($date === '' || $time === '') {
        $error = 'Please select a valid date and time.';
    } else {
        $result = appointment_create($patientId, $doctorId, $date, $time, $notes);
        if ($result['status'] === 'success') {
            header("Location: my-appointments.php?success=booked");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="mb-4">
        <a href="patient-dashboard.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Doctors</a>
    </div>
    
    <div class="card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fa-solid fa-calendar-check" style="font-size: 3rem; color: var(--primary);"></i>
            <h2 class="mt-2">Book Appointment</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Fill in the details to request your clinical consultation</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="book-appointment.php?doctor_id=<?php echo $doctorId; ?>" method="POST" id="appointmentForm">
            <div class="form-group">
                <label>Assigned Professional</label>
                <input type="text" value="<?php echo htmlspecialchars($doctor['full_name']); ?> (<?php echo htmlspecialchars($doctor['specialty']); ?>)" readonly style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
            </div>
            
            <div class="form-group">
                <label for="date">Select Date</label>
                <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                <small id="dateError" style="color:var(--danger); display:none; margin-top:0.25rem;">Appointment date is required.</small>
            </div>
            
            <div class="form-group">
                <label for="time">Select Time</label>
                <input type="time" id="time" name="time" required>
                <small id="timeError" style="color:var(--danger); display:none; margin-top:0.25rem;">Appointment time is required.</small>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes / Symptoms (Optional)</label>
                <textarea id="notes" name="notes" rows="4" placeholder="Briefly describe your symptoms or medical concern..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <a href="patient-dashboard.php" class="btn btn-secondary" style="flex: 1;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="flex: 2;"><i class="fa-solid fa-check"></i> Confirm Appointment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('appointmentForm')?.addEventListener('submit', function(e) {
    let date = document.getElementById('date').value;
    let time = document.getElementById('time').value;
    let valid = true;
    
    let dateError = document.getElementById('dateError');
    let timeError = document.getElementById('timeError');
    
    if (!date) {
        dateError.style.display = 'block';
        valid = false;
    } else {
        dateError.style.display = 'none';
    }
    
    if (!time) {
        timeError.style.display = 'block';
        valid = false;
    } else {
        timeError.style.display = 'none';
    }
    
    if (!valid) {
        e.preventDefault();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
