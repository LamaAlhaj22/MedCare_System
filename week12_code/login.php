<?php
// login.php
require_once __DIR__ . '/models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $result = user_login($email, $password);
        if ($result['status'] === 'success') {
            header("Location: index.php");
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div class="card" style="width: 100%; max-width: 420px;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-heart-pulse" style="font-size: 3rem; color: var(--primary);"></i>
            <h2 class="mt-2">Welcome to MedCare</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Please enter your credentials to log in</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
        </form>
        
        <div class="text-center mt-4" style="font-size: 0.9rem;">
            <span style="color: var(--text-secondary);">Don't have an account?</span>
            <a href="register.php" style="color: var(--primary); font-weight: 600; margin-left: 0.25rem;">Register Here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
