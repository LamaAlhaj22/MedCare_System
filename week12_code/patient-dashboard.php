<?php
// patient-dashboard.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/DoctorModel.php';
require_once __DIR__ . '/models/PatientModel.php';

user_require_role(['Patient']);

$patientId = $_SESSION['patient_id'];
$successMessage = '';
$errorMessage = '';

if (($_GET['error'] ?? '') === 'unauthorized') {
    $errorMessage = 'Access Denied: You do not have permission to view that page.';
}

// Handle profile update action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Other';
    $dob = $_POST['dob'] ?? '';
    
    if ($fullName === '') {
        $errorMessage = 'Full Name is a required field.';
    } else {
        $res = patient_update($patientId, $fullName, $phone, $gender, $dob);
        if ($res['status'] === 'success') {
            $successMessage = 'Profile updated successfully.';
        } else {
            $errorMessage = 'Failed to update profile: ' . $res['message'];
        }
    }
}

// Fetch lists and profile details
$specialtyFilter = $_GET['specialty'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');
$doctors = doctor_get_all($specialtyFilter, $searchQuery);
$specialties = doctor_get_specialties();
$patient = patient_get_by_id($patientId);

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h2>Patient Dashboard</h2>
    <p style="color: var(--text-secondary);">Browse medical practitioners and manage your settings</p>
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
    <button class="btn btn-secondary btn-sm" id="btn-doctors" onclick="switchTab('doctors')"><i class="fa-solid fa-user-doctor"></i> Book a Doctor</button>
    <button class="btn btn-secondary btn-sm" id="btn-profile" onclick="switchTab('profile')"><i class="fa-solid fa-user"></i> My Profile Settings</button>
</div>

<!-- DOCTORS BOOKING SECTION -->
<div id="tab-doctors" class="tab-content">
    <!-- Search and Filter Form -->
    <form action="patient-dashboard.php" method="GET" class="card mb-4" style="padding: 1.5rem; background: var(--bg-secondary);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: end;">
            <div>
                <label for="specialty">Filter by Specialty</label>
                <select name="specialty" id="specialty" onchange="this.form.submit()">
                    <option value="all">All Specialties</option>
                    <?php foreach ($specialties as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo $specialtyFilter === $spec ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="search">Search Doctor Name</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="search" id="search" placeholder="Type doctor's name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <?php if ($searchQuery): ?>
                        <a href="patient-dashboard.php?specialty=<?php echo urlencode($specialtyFilter); ?>" style="position: absolute; right: 10px; color: var(--text-secondary);"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                <a href="patient-dashboard.php" class="btn btn-secondary" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </div>
    </form>

    <!-- Doctors Listing -->
    <?php if (empty($doctors)): ?>
        <div class="card text-center" style="padding: 3rem;">
            <i class="fa-solid fa-user-doctor" style="font-size: 4rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h3>No Doctors Found</h3>
            <p style="color: var(--text-secondary);">Try adjusting your search criteria or filter options.</p>
        </div>
    <?php else: ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doc): ?>
                <div class="card doctor-card">
                    <div class="doctor-info">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(59, 130, 246, 0.15); display: flex; justify-content: center; align-items: center; color: var(--primary); font-size: 1.5rem;">
                                <i class="fa-solid fa-user-doctor"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.15rem;"><?php echo htmlspecialchars($doc['full_name']); ?></h3>
                                <span class="badge badge-confirmed" style="font-size: 0.7rem;"><?php echo htmlspecialchars($doc['specialty']); ?></span>
                            </div>
                        </div>
                        
                        <p style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-calendar-days" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            <strong>Days:</strong> <?php echo htmlspecialchars($doc['available_days'] ?: 'Not Scheduled'); ?>
                        </p>
                        <p style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-clock" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            <strong>Hours:</strong> <?php echo htmlspecialchars($doc['start_time'] ? ($doc['start_time'] . ' - ' . $doc['end_time']) : 'Not Scheduled'); ?>
                        </p>
                        <p style="font-size: 0.9rem; margin-bottom: 1rem;">
                            <i class="fa-solid fa-phone" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($doc['phone'] ?: 'N/A'); ?>
                        </p>
                    </div>
                    
                    <div class="mt-2" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <a href="book-appointment.php?doctor_id=<?php echo $doc['doctor_id']; ?>" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- PATIENT PROFILE SECTION -->
<div id="tab-profile" class="tab-content" style="display: none;">
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3>Edit Personal Profile</h3>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Update details associated with your patient account</p>
        
        <form action="patient-dashboard.php" method="POST">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($patient['email']); ?>" readonly style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
            </div>
            
            <div class="form-group">
                <label for="phone">Contact Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" placeholder="0599XXXXXX">
            </div>
            
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo $patient['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $patient['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $patient['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>">
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
    document.getElementById("btn-doctors").className = "btn btn-secondary btn-sm";
    document.getElementById("btn-profile").className = "btn btn-secondary btn-sm";
    
    document.getElementById("btn-" + tabId).className = "btn btn-primary btn-sm";
    
    // Store in localStorage for persistence
    localStorage.setItem("patient_active_tab", tabId);
}

// Initialise active tab
document.addEventListener("DOMContentLoaded", () => {
    let activeTab = localStorage.getItem("patient_active_tab") || "doctors";
    
    // If we just redirected with query filters (specialty/search), force switch to doctors list
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('specialty') || urlParams.has('search')) {
        activeTab = "doctors";
    }
    
    switchTab(activeTab);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
