<?php
// Admin Milestones Management Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle milestone creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_milestone'])) {
    $milestone_title = trim($_POST['milestone_title'] ?? '');
    $milestone_description = trim($_POST['milestone_description'] ?? '');
    $milestone_due_date = $_POST['milestone_due_date'] ?? '';

    // Validate input
    if (empty($milestone_title) || empty($milestone_description) || empty($milestone_due_date)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (strtotime($milestone_due_date) < time()) {
        $_SESSION['error'] = 'Due date cannot be in the past.';
    } else {
        // Insert milestone
        $insert_query = "INSERT INTO milestones (milestone_title, milestone_description, milestone_due_date)
                        VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $milestone_title, $milestone_description, $milestone_due_date);

        if ($insert_stmt->execute()) {
            $_SESSION['success'] = 'Milestone created successfully!';
        } else {
            $_SESSION['error'] = 'Failed to create milestone. Please try again.';
        }
        $insert_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=milestones";</script>';
    exit();
}

// Handle milestone update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_milestone'])) {
    $milestone_id = $_POST['milestone_id'] ?? 0;
    $milestone_title = trim($_POST['milestone_title'] ?? '');
    $milestone_description = trim($_POST['milestone_description'] ?? '');
    $milestone_due_date = $_POST['milestone_due_date'] ?? '';

    // Validate input
    if (empty($milestone_title) || empty($milestone_description) || empty($milestone_due_date) || !is_numeric($milestone_id)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (strtotime($milestone_due_date) < time()) {
        $_SESSION['error'] = 'Due date cannot be in the past.';
    } else {
        // Update milestone
        $update_query = "UPDATE milestones SET milestone_title = ?, milestone_description = ?, milestone_due_date = ? WHERE milestone_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssi", $milestone_title, $milestone_description, $milestone_due_date, $milestone_id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = 'Milestone updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update milestone. Please try again.';
        }
        $update_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=milestones";</script>';
    exit();
}

// Handle milestone deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_milestone'])) {
    $milestone_id = $_POST['milestone_id'] ?? 0;

    if (!is_numeric($milestone_id)) {
        $_SESSION['error'] = 'Invalid milestone ID.';
    } else {
        // Check if milestone has submissions
        $check_submissions = "SELECT COUNT(*) as count FROM submissions WHERE submission_milestone_id = ?";
        $check_stmt = $conn->prepare($check_submissions);
        $check_stmt->bind_param("i", $milestone_id);
        $check_stmt->execute();
        $submissions_result = $check_stmt->get_result()->fetch_assoc();

        if ($submissions_result['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete milestone with associated submissions. Please remove submissions first.';
        } else {
            // Delete milestone
            $delete_query = "DELETE FROM milestones WHERE milestone_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $milestone_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success'] = 'Milestone deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete milestone. Please try again.';
            }
            $delete_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=milestones";</script>';
    exit();
}

// Get all milestones
$milestones_query = "SELECT milestone_id, milestone_title, milestone_description, milestone_due_date, milestone_created_at
                    FROM milestones ORDER BY milestone_created_at DESC";
$milestones_result = $conn->query($milestones_query);

// Get milestone statistics
$milestone_stats_query = "SELECT COUNT(*) as total_milestones FROM milestones";
$milestone_stats_result = $conn->query($milestone_stats_query);
$milestone_stats = $milestone_stats_result->fetch_assoc();

// Get upcoming milestones (next 7 days)
$upcoming_query = "SELECT COUNT(*) as upcoming_milestones FROM milestones WHERE milestone_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_stats = $upcoming_result->fetch_assoc();

// Get overdue milestones
$overdue_query = "SELECT COUNT(*) as overdue_milestones FROM milestones WHERE milestone_due_date < CURDATE()";
$overdue_result = $conn->query($overdue_query);
$overdue_stats = $overdue_result->fetch_assoc();

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
    <h2>Milestones Management</h2>
    <p>Manage project milestones and deadlines.</p>
</div>

<!-- Milestone Statistics -->
<div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="target" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $milestone_stats['total_milestones']; ?></h4>
            <p>Total Milestones</p>
        </div>
    </div>

    <!-- <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="calendar" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php //echo $upcoming_stats['upcoming_milestones']; ?></h4>
            <p>Due This Week</p>
        </div>
    </div> -->

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="alert-triangle" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $overdue_stats['overdue_milestones']; ?></h4>
            <p>Overdue</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="activity" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $milestone_stats['total_milestones'] - $overdue_stats['overdue_milestones']; ?></h4>
            <p>Active</p>
        </div>
    </div>
</div>

<!-- Milestones Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>All Milestones</h3>
        <button class="btn btn-primary btn-md" onclick="toggleAddMilestoneForm()">
            <i data-lucide="calendar-plus" class="icon-sm"></i>
            Add New Milestone
        </button>
    </div>

    <div class="card-body">
        <!-- Add New Milestone Form -->
        <div id="add-milestone-section" class="add-milestone-section mb-5" style="display: none;">
            <h4>Add New Milestone</h4>
            <form method="POST" action="" class="inline-form mt-3">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Milestone Title</label>
                        <input type="text" name="milestone_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="milestone_due_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Milestone Description</label>
                    <textarea name="milestone_description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_milestone" class="btn btn-primary btn-sm">
                        <i data-lucide="plus" class="icon-sm"></i>
                        Create Milestone
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleAddMilestoneForm()">
                        <i data-lucide="x" class="icon-sm"></i>
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($milestone = $milestones_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $milestone['milestone_id']; ?></td>
                            <td><?php echo htmlspecialchars($milestone['milestone_title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($milestone['milestone_description'], 0, 50)) . (strlen($milestone['milestone_description']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <?php
                                $due_date = strtotime($milestone['milestone_due_date']);
                                $today = strtotime(date('Y-m-d'));
                                $is_overdue = $due_date < $today;
                                $is_due_soon = $due_date <= strtotime('+7 days') && $due_date >= $today;
                                ?>
                                <span class="<?php echo $is_overdue ? 'text-danger' : ($is_due_soon ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo date('M d, Y', $due_date); ?>
                                    <?php if ($is_overdue): ?>
                                        <i data-lucide="alert-triangle" class="icon-xs"></i>
                                    <?php elseif ($is_due_soon): ?>
                                        <i data-lucide="clock" class="icon-xs"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($milestone['milestone_created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline" onclick="toggleEditForm(<?php echo $milestone['milestone_id']; ?>)">
                                        <i data-lucide="edit" class="icon-sm"></i>
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="toggleDeleteForm(<?php echo $milestone['milestone_id']; ?>)">
                                        <i data-lucide="trash" class="icon-sm"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Milestone Form -->
                        <tr id="edit-form-<?php echo $milestone['milestone_id']; ?>" class="edit-form-row" style="display: none;">
                            <td colspan="6">
                                <div class="inline-form-container">
                                    <h5>Edit Milestone</h5>
                                    <form method="POST" action="" class="inline-form">
                                        <input type="hidden" name="milestone_id" value="<?php echo $milestone['milestone_id']; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Milestone Title</label>
                                                <input type="text" name="milestone_title" class="form-control" value="<?php echo htmlspecialchars($milestone['milestone_title']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Due Date</label>
                                                <input type="date" name="milestone_due_date" class="form-control" value="<?php echo $milestone['milestone_due_date']; ?>" required min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Milestone Description</label>
                                            <textarea name="milestone_description" class="form-control" rows="3" required><?php echo htmlspecialchars($milestone['milestone_description']); ?></textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_milestone" class="btn btn-primary btn-sm">
                                                <i data-lucide="save" class="icon-sm"></i>
                                                Update Milestone
                                            </button>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditForm(<?php echo $milestone['milestone_id']; ?>)">
                                                <i data-lucide="x" class="icon-sm"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Delete Confirmation -->
                        <tr id="delete-form-<?php echo $milestone['milestone_id']; ?>" class="delete-form-row" style="display: none;">
                            <td colspan="6">
                                <div class="delete-confirmation">
                                    <div class="alert alert-warning">
                                        <i data-lucide="alert-triangle" class="icon-sm"></i>
                                        <strong>Confirm Deletion</strong>
                                        <p>Are you sure you want to delete the milestone "<strong><?php echo htmlspecialchars($milestone['milestone_title']); ?></strong>"? This action cannot be undone.</p>
                                        <div class="form-actions">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="milestone_id" value="<?php echo $milestone['milestone_id']; ?>">
                                                <button type="submit" name="delete_milestone" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash" class="icon-sm"></i>
                                                    Yes, Delete Milestone
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleDeleteForm(<?php echo $milestone['milestone_id']; ?>)">
                                                <i data-lucide="x" class="icon-sm"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleAddMilestoneForm() {
    const form = document.getElementById('add-milestone-section');
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleEditForm(milestoneId) {
    const form = document.getElementById('edit-form-' + milestoneId);
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'table-row';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleDeleteForm(milestoneId) {
    const form = document.getElementById('delete-form-' + milestoneId);
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'table-row';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function closeAllForms() {
    // Close add form
    const addForm = document.getElementById('add-milestone-section');
    if (addForm) addForm.style.display = 'none';

    // Close all edit forms
    const editForms = document.querySelectorAll('.edit-form-row');
    editForms.forEach(form => form.style.display = 'none');

    // Close all delete forms
    const deleteForms = document.querySelectorAll('.delete-form-row');
    deleteForms.forEach(form => form.style.display = 'none');
}

// Close forms when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.btn') && !event.target.closest('.inline-form-container') &&
        !event.target.closest('.delete-confirmation') && !event.target.closest('#add-milestone-section')) {
        closeAllForms();
    }
});
</script>
