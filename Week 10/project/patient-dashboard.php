<?php
// patient-dashboard.php
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/DoctorModel.php';

user_require_role(['Patient']);

$specialtyFilter = $_GET['specialty'] ?? 'all';
$searchQuery = trim($_GET['search'] ?? '');

$doctors = doctor_get_all($specialtyFilter, $searchQuery);
$specialties = doctor_get_specialties();

require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4">
    <h2>Find and Book a Doctor</h2>
    <p style="color: var(--text-secondary);">Select a specialty or search by doctor's name to schedule your consultation</p>
</div>

<!-- Search and Filter Form -->
<form action="patient-dashboard.php" method="GET" class="card mb-4" style="padding: 1.5rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) gap 1.5rem; gap: 1rem; align-items: end;">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
