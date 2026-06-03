<?php
// register.php
require_once __DIR__ . '/models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'Patient';
    
    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } elseif (!in_array($role, ['Patient', 'Doctor'])) {
        $error = 'Invalid role selection.';
    } else {
        $result = user_register($fullName, $email, $password, $role);
        if ($result['status'] === 'success') {
            $success = 'Registration successful! You can now log in.';
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 2rem 0;">
    <div class="card" style="width: 100%; max-width: 460px;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-user-plus" style="font-size: 3rem; color: var(--primary);"></i>
            <h2 class="mt-2">Create Account</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Join MedCare to manage clinical appointments</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-primary" style="width: 100%;">Proceed to Login</a>
            </div>
        <?php else: ?>
            <form action="register.php" method="POST" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required autocomplete="name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="role">Register As</label>
                    <select id="role" name="role" required>
                        <option value="Patient" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Patient') ? 'selected' : ''; ?>>Patient</option>
                        <option value="Doctor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Doctor') ? 'selected' : ''; ?>>Medical Doctor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
                    <small id="confirmError" style="color:var(--danger); display:none; margin-top:0.25rem;">Passwords do not match.</small>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    <i class="fa-solid fa-user-check"></i> Register Account
                </button>
            </form>
            
            <div class="text-center mt-4" style="font-size: 0.9rem;">
                <span style="color: var(--text-secondary);">Already have an account?</span>
                <a href="login.php" style="color: var(--primary); font-weight: 600; margin-left: 0.25rem;">Login here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple dynamic validation helper matching Week 7 client-side logic
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    let password = document.getElementById('password').value;
    let confirmPassword = document.getElementById('confirm_password').value;
    let errorEl = document.getElementById('confirmError');
    
    if (password !== confirmPassword) {
        e.preventDefault();
        errorEl.style.display = 'block';
    } else {
        errorEl.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
