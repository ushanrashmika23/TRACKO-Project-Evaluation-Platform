<?php
// Admin Profile Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'New password must be at least 6 characters long.';
    } else {
        // Verify current password
        $user_query = "SELECT user_password FROM users WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();

        if (!password_verify($current_password, $user['user_password'])) {
            $_SESSION['error'] = 'Current password is incorrect.';
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET user_password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'Password updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update password. Please try again.';
            }
            $update_stmt->close();
        }
        $user_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=profile";</script>';
    exit();
}

$conn->close();
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>My Profile</h3>
            </div>
            <div class="card-body">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <i data-lucide="user" class="profile-avatar-icon"></i>
                    </div>
                    <div class="profile-details">
                        <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        <span class="badge badge-completed">Administrator</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>Account Settings</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i data-lucide="check-circle" class="icon-sm"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle" class="icon-sm"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary btn-md">
                        <i data-lucide="save" class="icon-sm"></i>
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>