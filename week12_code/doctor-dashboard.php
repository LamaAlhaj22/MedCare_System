<?php
// doctor-dashboard.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/DoctorModel.php';
require_once __DIR__ . '/models/AppointmentModel.php';

user_require_role(['Doctor']);

$doctorId = $_SESSION['doctor_id'];
$userId = $_SESSION['user_id'];

$successMessage = '';
$errorMessage = '';

if (($_GET['error'] ?? '') === 'unauthorized') {
    $errorMessage = 'Access Denied: You do not have permission to view that page.';
}

// Handle actions (Status update, profile update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $status = $_POST['status'] ?? '';
        
        // Validate appointment belongs to doctor
        $appt = appointment_get_by_id($appointmentId);
        if ($appt && $appt['doctor_id'] == $doctorId) {
            if (in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled'])) {
                $res = appointment_update_status($appointmentId, $status);
                if ($res['status'] === 'success') {
                    $successMessage = 'Appointment status updated successfully to ' . $status . '.';
                } else {
                    $errorMessage = 'Failed to update status: ' . $res['message'];
                }
            } else {
                $errorMessage = 'Invalid status selected.';
            }
        } else {
            $errorMessage = 'Unauthorized action.';
        }
    } elseif ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $availableDays = trim($_POST['available_days'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        
        if ($fullName === '' || $specialty === '') {
            $errorMessage = 'Full Name and Specialty are required fields.';
        } else {
            $res = doctor_update($doctorId, $fullName, $specialty, $phone, $availableDays, $startTime, $endTime);
            if ($res['status'] === 'success') {
                $successMessage = 'Professional profile updated successfully.';
            } else {
                $errorMessage = 'Failed to update profile: ' . $res['message'];
            }
        }
    }
}

// Fetch doctor details and appointments
$doctor = doctor_get_by_id($doctorId);
$appointments = appointment_get_by_doctor($doctorId);

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h2>Doctor Portal</h2>
    <p style="color: var(--text-secondary);">Manage patient requests and configure your session availability</p>
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
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
    <button class="btn btn-secondary btn-sm" id="btn-appointments" onclick="switchTab('appointments')"><i class="fa-solid fa-calendar-check"></i> Patient Appointments</button>
    <button class="btn btn-secondary btn-sm" id="btn-profile" onclick="switchTab('profile')"><i class="fa-solid fa-user-doctor"></i> My Schedule & Profile</button>
</div>

<!-- Appointments Section -->
<div id="tab-appointments" class="tab-content">
    <h3>Patient Appointments</h3>
    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">List of requests and scheduled meetings</p>
    
    <?php if (empty($appointments)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <i class="fa-solid fa-folder-open" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h3>No Appointments Listed</h3>
            <p style="color: var(--text-secondary);">Patients haven't scheduled any appointments with you yet.</p>
        </div>
    <?php else: ?>
        <div class="appointments-list">
            <?php foreach ($appointments as $appt): ?>
                <div class="card appointment-item">
                    <div class="appointment-details">
                        <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($appt['patient_name']); ?></h4>
                        <p><i class="fa-solid fa-calendar-day" style="width: 1.25rem; color: var(--primary);"></i> <strong>Date:</strong> <?php echo htmlspecialchars($appt['appointment_date']); ?></p>
                        <p><i class="fa-solid fa-clock" style="width: 1.25rem; color: var(--primary);"></i> <strong>Time:</strong> <?php echo htmlspecialchars($appt['appointment_time']); ?></p>
                        <p><i class="fa-solid fa-phone" style="width: 1.25rem; color: var(--primary);"></i> <strong>Patient Phone:</strong> <?php echo htmlspecialchars($appt['patient_phone'] ?: 'N/A'); ?></p>
                        <?php if ($appt['notes']): ?>
                            <p><i class="fa-solid fa-clipboard-question" style="width: 1.25rem; color: var(--primary);"></i> <strong>Symptoms/Notes:</strong> <?php echo htmlspecialchars($appt['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="appointment-actions" style="min-width: 220px; display: flex; flex-direction: column; gap: 0.5rem;">
                        <form action="doctor-dashboard.php" method="POST" style="display: flex; gap: 0.5rem; width: 100%;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                            <select name="status" style="padding: 0.5rem; font-size: 0.85rem; flex: 1;">
                                <option value="Pending" <?php echo $appt['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo $appt['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Completed" <?php echo $appt['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $appt['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Update</button>
                        </form>
                        <div class="text-center mt-1">
                            <span class="badge badge-<?php echo strtolower($appt['status']); ?>"><?php echo $appt['status']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Profile Section -->
<div id="tab-profile" class="tab-content" style="display: none;">
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3>Edit Professional Profile</h3>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Update details and define booking hours</p>
        
        <form action="doctor-dashboard.php" method="POST">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="full_name">Professional Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="specialty">Medical Specialty</label>
                <input type="text" id="specialty" name="specialty" value="<?php echo htmlspecialchars($doctor['specialty']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Contact Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label for="available_days">Available Schedule Days (e.g. Sunday-Monday)</label>
                <input type="text" id="available_days" name="available_days" value="<?php echo htmlspecialchars($doctor['available_days']); ?>" placeholder="Sunday - Thursday">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="start_time">Shift Start Time</label>
                    <input type="text" id="start_time" name="start_time" value="<?php echo htmlspecialchars($doctor['start_time']); ?>" placeholder="09:00 AM">
                </div>
                <div class="form-group">
                    <label for="end_time">Shift End Time</label>
                    <input type="text" id="end_time" name="end_time" value="<?php echo htmlspecialchars($doctor['end_time']); ?>" placeholder="03:00 PM">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                <i class="fa-solid fa-floppy-disk"></i> Save Profile Settings
            </button>
        </form>
    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all tabs
    document.querySelectorAll(".tab-content").forEach(el => el.style.display = 'none');
    // Show selected tab
    document.getElementById("tab-" + tabId).style.display = 'block';
    
    // Toggle active buttons styling
    document.getElementById("btn-appointments").className = "btn btn-secondary btn-sm";
    document.getElementById("btn-profile").className = "btn btn-secondary btn-sm";
    
    document.getElementById("btn-" + tabId).className = "btn btn-primary btn-sm";
    
    // Store in localStorage for persistence
    localStorage.setItem("doctor_active_tab", tabId);
}

// Initialise active tab
document.addEventListener("DOMContentLoaded", () => {
    let activeTab = localStorage.getItem("doctor_active_tab") || "appointments";
    switchTab(activeTab);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
