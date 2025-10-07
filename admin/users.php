<?php
// Admin User Management Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $user_name = trim(string: $_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $user_password = $_POST['user_password'] ?? '';
    $user_role = $_POST['user_role'] ?? '';

    // Validate input
    if (empty($user_name) || empty($user_email) || empty($user_password) || empty($user_role)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
    } elseif (strlen($user_password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
    } elseif (!in_array($user_role, ['admin', 'supervisor', 'student'])) {
        $_SESSION['error'] = 'Invalid user role.';
    } else {
        // Check if email already exists
        $check_query = "SELECT user_id FROM users WHERE user_email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $user_email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Email address already exists.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (user_name, user_email, user_password, user_role)
                           VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $user_name, $user_email, $hashed_password, $user_role);

            if ($insert_stmt->execute()) {
                $_SESSION['success'] = 'User created successfully!';
            } else {
                $_SESSION['error'] = 'Failed to create user. Please try again.';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=users";</script>';
    exit();
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'] ?? 0;
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $user_role = $_POST['user_role'] ?? '';

    // Validate input
    if (empty($user_name) || empty($user_email) || empty($user_role) || !is_numeric($user_id)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address.';
    } elseif (!in_array($user_role, ['admin', 'supervisor', 'student'])) {
        $_SESSION['error'] = 'Invalid user role.';
    } else {
        // Check if email already exists (excluding current user)
        $check_query = "SELECT user_id FROM users WHERE user_email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $user_email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Email address already exists.';
        } else {
            // Update user
            $update_query = "UPDATE users SET user_name = ?, user_email = ?, user_role = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $user_name, $user_email, $user_role, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'User updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update user. Please try again.';
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=users";</script>';
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'] ?? 0;

    if (!is_numeric($user_id) || $user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Invalid user or cannot delete yourself.';
    } else {
        // Check if user has associated projects/submissions (prevent deletion if they do)
        $check_projects = "SELECT COUNT(*) as count FROM projects WHERE project_student_id = ? OR project_supervisor_id = ?";
        $check_stmt = $conn->prepare($check_projects);
        $check_stmt->bind_param("ii", $user_id, $user_id);
        $check_stmt->execute();
        $projects_result = $check_stmt->get_result()->fetch_assoc();

        $check_submissions = "SELECT COUNT(*) as count FROM submissions WHERE submission_uploaded_by = ?";
        $check_stmt = $conn->prepare($check_submissions);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $submissions_result = $check_stmt->get_result()->fetch_assoc();

        if ($projects_result['count'] > 0 || $submissions_result['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete user with associated projects or submissions. Please reassign or remove dependencies first.';
        } else {
            // Delete user
            $delete_query = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $user_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success'] = 'User deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete user. Please try again.';
            }
            $delete_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=users";</script>';
    exit();
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$search_filter = trim($_GET['search'] ?? '');

// Build query with filters
$query_parts = [];
$params = [];
$types = '';

if (!empty($role_filter)) {
    $query_parts[] = "user_role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($search_filter)) {
    $query_parts[] = "(user_name LIKE ? OR user_email LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $types .= 'ss';
}

$where_clause = !empty($query_parts) ? "WHERE " . implode(" AND ", $query_parts) : "";

// Get all users with filters
$users_query = "SELECT user_id, user_name, user_email, user_role FROM users $where_clause ORDER BY user_id DESC";

if (!empty($params)) {
    $users_stmt = $conn->prepare($users_query);
    $users_stmt->bind_param($types, ...$params);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
} else {
    $users_result = $conn->query($users_query);
}

// Get user statistics
$user_stats_query = "SELECT
    COUNT(CASE WHEN user_role = 'admin' THEN 1 END) as total_admins,
    COUNT(CASE WHEN user_role = 'supervisor' THEN 1 END) as total_supervisors,
    COUNT(CASE WHEN user_role = 'student' THEN 1 END) as total_students,
    COUNT(*) as total_users
    FROM users";
$user_stats_result = $conn->query($user_stats_query);
$user_stats = $user_stats_result->fetch_assoc();

// Display success/error messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success"><i data-lucide="check-circle" class="icon-sm"></i>' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-error"><i data-lucide="alert-circle" class="icon-sm"></i>' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<div class="welcome-section">
    <h2>User Management</h2>
    <p>Manage students, supervisors, and administrators in the system.</p>
</div>

<!-- User Statistics -->
<div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="users" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $user_stats['total_users']; ?></h4>
            <p>Total Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="user-check" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $user_stats['total_students']; ?></h4>
            <p>Students</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="user-cog" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $user_stats['total_supervisors']; ?></h4>
            <p>Supervisors</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="shield" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $user_stats['total_admins']; ?></h4>
            <p>Admins</p>
        </div>
    </div>
</div>

<!-- User Filters -->
<div class="filters-section">
    <form method="GET" action="" class="filters-form">
        <input type="hidden" name="page" value="users">
        <div class="filter-row">
            <div class="filter-group">
                <label for="role" class="filter-label">Filter by Role</label>
                <select name="role" id="role" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="supervisor" <?php echo ($role_filter === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    <option value="student" <?php echo ($role_filter === 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="search" class="filter-label">Search by Name or Email</label>
                <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="Search users..." class="filter-select">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary btn-sm" style="align-self: end;">
                    <i data-lucide="filter" class="icon-sm"></i>
                    Apply Filters
                </button>
                <?php if (!empty($role_filter) || !empty($search_filter)): ?>
                    <a href="layout.php?page=users" class="btn btn-outline btn-sm" style="align-self: end; margin-left: 0.5rem;">
                        <i data-lucide="x" class="icon-sm"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>All Users <?php
            $user_count = $users_result->num_rows;
            if (!empty($role_filter) || !empty($search_filter)) {
                echo "<small class='text-muted'>($user_count filtered)</small>";
            } else {
                echo "<small class='text-muted'>($user_count total)</small>";
            }
        ?></h3>
        <button class="btn btn-primary btn-md" onclick="toggleAddUserForm()">
            <i data-lucide="user-plus" class="icon-sm"></i>
            Add New User
        </button>
    </div>

    <!-- Add New User Form -->
    <div id="add-user-section" class="add-user-section mb-5" style="display: none;">
        <!-- <div class="inline-form-container"> -->
        <h4>Add New User</h4>
        <form method="POST" action="" class="inline-form mt-3">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="user_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="user_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="user_password" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="user_role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="create_user" class="btn btn-primary btn-sm">
                    <i data-lucide="user-plus" class="icon-sm"></i>
                    Create User
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="toggleAddUserForm()">
                    <i data-lucide="x" class="icon-sm"></i>
                    Cancel
                </button>
            </div>
        </form>
        <!-- </div> -->
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['user_email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['user_role']; ?>">
                                    <?php echo ucfirst($user['user_role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline"
                                        onclick="toggleEditForm(<?php echo $user['user_id']; ?>)">
                                        <i data-lucide="edit" class="icon-sm"></i>
                                        Edit
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="toggleDeleteForm(<?php echo $user['user_id']; ?>)">
                                            <i data-lucide="trash" class="icon-sm"></i>
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <!-- Edit Form Row -->
                        <tr id="edit-row-<?php echo $user['user_id']; ?>" class="edit-form-row" style="display: none;">
                            <td colspan="5">
                                <div class="inline-form-container">
                                    <h4>Edit User</h4>
                                    <form method="POST" action="" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="user_name" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['user_name']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" name="user_email" class="form-control"
                                                    value="<?php echo htmlspecialchars($user['user_email']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Role</label>
                                                <select name="user_role" class="form-control" required>
                                                    <option value="student" <?php echo $user['user_role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                    <option value="supervisor" <?php echo $user['user_role'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                                    <option value="admin" <?php echo $user['user_role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_user" class="btn btn-primary btn-sm">
                                                <i data-lucide="save" class="icon-sm"></i>
                                                Update User
                                            </button>
                                            <button type="button" class="btn btn-outline btn-sm"
                                                onclick="toggleEditForm(<?php echo $user['user_id']; ?>)">
                                                <i data-lucide="x" class="icon-sm"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <!-- Delete Form Row -->
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <tr id="delete-row-<?php echo $user['user_id']; ?>" class="delete-form-row" style="display: none;">
                                <td colspan="5">
                                    <div class="inline-form-container">
                                        <h4>Delete User</h4>
                                        <form method="POST" action="" class="inline-form">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <div class="delete-confirmation">
                                                <p>Are you sure you want to delete
                                                    <strong><?php echo htmlspecialchars($user['user_name']); ?></strong>? This
                                                    action cannot be undone.
                                                </p>
                                                <div class="alert alert-warning">
                                                    <strong>Warning:</strong> This will permanently remove the user from the
                                                    system.
                                                </div>
                                            </div>
                                            <div class="form-actions">
                                                <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash" class="icon-sm"></i>
                                                    Delete User
                                                </button>
                                                <button type="button" class="btn btn-outline btn-sm"
                                                    onclick="toggleDeleteForm(<?php echo $user['user_id']; ?>)">
                                                    <i data-lucide="x" class="icon-sm"></i>
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Helper function to close all forms
    function closeAllForms() {
        // Close all edit forms
        const editRows = document.querySelectorAll('[id^="edit-row-"]');
        editRows.forEach(row => {
            row.style.display = 'none';
        });

        // Close all delete forms
        const deleteRows = document.querySelectorAll('[id^="delete-row-"]');
        deleteRows.forEach(row => {
            row.style.display = 'none';
        });

        // Close add user form
        const addSection = document.getElementById('add-user-section');
        if (addSection) {
            addSection.style.display = 'none';
        }
    }

    // Toggle edit form visibility (closes all other forms first)
    function toggleEditForm(userId) {
        const row = document.getElementById('edit-row-' + userId);
        const isCurrentlyOpen = row.style.display === 'table-row';

        // Close all forms first
        closeAllForms();

        // If it wasn't open, open it now
        if (!isCurrentlyOpen) {
            row.style.display = 'table-row';
        }
    }

    // Toggle delete form visibility (closes all other forms first)
    function toggleDeleteForm(userId) {
        const row = document.getElementById('delete-row-' + userId);
        const isCurrentlyOpen = row.style.display === 'table-row';

        // Close all forms first
        closeAllForms();

        // If it wasn't open, open it now
        if (!isCurrentlyOpen) {
            row.style.display = 'table-row';
        }
    }

    // Toggle add user form visibility (closes all other forms first)
    function toggleAddUserForm() {
        const section = document.getElementById('add-user-section');
        const isCurrentlyOpen = section.style.display === 'block';

        // Close all forms first
        closeAllForms();

        // If it wasn't open, open it now
        if (!isCurrentlyOpen) {
            section.style.display = 'block';
        }
    }

    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function () {
        lucide.createIcons();
    });
</script>