<?php
session_start();
require_once 'includes/db.php';

// Handle login
$toastMessage = '';
$toastType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $toastMessage = 'Please fill in all fields.';
        $toastType = 'error';
    } else {
        // Query user from database
        $stmt = $conn->prepare("SELECT user_id, user_name, user_email, user_password, user_role FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['user_password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['user_email'] = $user['user_email'];
                $_SESSION['user_role'] = $user['user_role'];
                header('Location: dashboard.php');
                exit();
            } else {
                $toastMessage = 'Invalid password.';
                $toastType = 'error';
            }
        } else {
            $toastMessage = 'Email not found.';
            $toastType = 'error';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRACKO - Login</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>

<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h1 class="login-title">TRACKO</h1>
                <p class="login-subtitle">University Final Year Project Management</p>
            </div>
            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i data-lucide="user" class="icon-sm"></i>
                        Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required
                        placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i data-lucide="lock" class="icon-sm"></i>
                        Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required
                        placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary btn-md btn-login">
                    <i data-lucide="log-in" class="icon-sm"></i>
                    Sign In
                </button>
            </form>
            <div class="login-footer">
                <p>&copy; 2025 TRACKO. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if (!empty($toastMessage)): ?>
        <div id="toast" class="toast show toast-<?php echo $toastType; ?>">
            <div class="toast-body">
                <i data-lucide="<?php echo $toastType === 'error' ? 'alert-circle' : 'check-circle'; ?>"
                    class="icon-sm"></i>
                <?php echo htmlspecialchars($toastMessage); ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        // Auto-hide toast after 3 seconds
        const toast = document.getElementById('toast');
        if (toast) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>

</html>