<?php
// Admin Submissions Management Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle submission creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_submission'])) {
    $submission_milestone_id = $_POST['submission_milestone_id'] ?? '';
    $submission_project_id = $_POST['submission_project_id'] ?? '';
    $submission_uploaded_by = $_POST['submission_uploaded_by'] ?? '';
    $submission_notes = trim($_POST['submission_notes'] ?? '');

    // Validate input
    if (empty($submission_milestone_id) || empty($submission_project_id) || empty($submission_uploaded_by)) {
        $_SESSION['error'] = 'Milestone, Project, and Student are required.';
    } elseif (!is_numeric($submission_milestone_id) || !is_numeric($submission_project_id) || !is_numeric($submission_uploaded_by)) {
        $_SESSION['error'] = 'Invalid IDs provided.';
    } elseif (empty($_FILES['submission_file']['name'])) {
        $_SESSION['error'] = 'Please select a file to upload.';
    } else {
        $file = $_FILES['submission_file'];

        // Validate file
        $allowed_types = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 50 * 1024 * 1024; // 50MB

        if (!in_array($file_extension, $allowed_types)) {
            $_SESSION['error'] = 'Invalid file type. Allowed types: PDF, DOC, DOCX, ZIP, TXT, JPG, PNG.';
        } elseif ($file['size'] > $max_size) {
            $_SESSION['error'] = 'File size too large. Maximum size: 50MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'File upload failed. Please try again.';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/submissions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // First, insert submission record to get submission_id
            $insert_query = "INSERT INTO submissions (
                submission_milestone_id,
                submission_project_id,
                submission_uploaded_by,
                submission_file_path,
                submission_notes,
                submission_upload_date
            ) VALUES (?, ?, ?, '', ?, NOW())";

            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param(
                "iiis",
                $submission_milestone_id,
                $submission_project_id,
                $submission_uploaded_by,
                $submission_notes
            );

            if ($insert_stmt->execute()) {
                // Get the newly inserted submission ID
                $submission_id = $conn->insert_id;

                // Get milestone and student info for filename
                $info_query = "SELECT m.milestone_title, u.user_name FROM milestones m, users u WHERE m.milestone_id = ? AND u.user_id = ?";
                $info_stmt = $conn->prepare($info_query);
                $info_stmt->bind_param("ii", $submission_milestone_id, $submission_uploaded_by);
                $info_stmt->execute();
                $info_result = $info_stmt->get_result();
                $info = $info_result->fetch_assoc();
                $info_stmt->close();

                // Generate new filename: milestoneName_submissionId_student_name(withourspaces)
                $milestone_name = preg_replace('/[^A-Za-z0-9]/', '', $info['milestone_title']); // Remove special chars
                $student_name = preg_replace('/\s+/', '', $info['user_name']); // Remove spaces
                $new_filename = $milestone_name . '_' . $submission_id . '_' . $student_name . '.' . $file_extension;
                $file_path = $upload_dir . $new_filename;

                // Move uploaded file with new name
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Update the database record with the correct file path
                    $update_query = "UPDATE submissions SET submission_file_path = ? WHERE submission_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("si", $file_path, $submission_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    $_SESSION['success'] = 'Submission created successfully!';
                } else {
                    // If file move failed, delete the database record
                    $delete_query = "DELETE FROM submissions WHERE submission_id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bind_param("i", $submission_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();

                    $_SESSION['error'] = 'Failed to save uploaded file.';
                }
            } else {
                $_SESSION['error'] = 'Failed to create submission. Please try again.';
            }
            $insert_stmt->close();
        }
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=submissions";</script>';
    exit();
}

// Handle submission update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submission'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $submission_milestone_id = $_POST['submission_milestone_id'] ?? '';
    $submission_project_id = $_POST['submission_project_id'] ?? '';
    $submission_uploaded_by = $_POST['submission_uploaded_by'] ?? '';
    $submission_notes = trim($_POST['submission_notes'] ?? '');

    // Validate input
    if (empty($submission_milestone_id) || empty($submission_project_id) || empty($submission_uploaded_by) || !is_numeric($submission_id)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!is_numeric($submission_milestone_id) || !is_numeric($submission_project_id) || !is_numeric($submission_uploaded_by)) {
        $_SESSION['error'] = 'Invalid IDs provided.';
    } else {
        $file_path = null;

        // Handle file upload if a new file is provided
        if (!empty($_FILES['submission_file']['name'])) {
            $file = $_FILES['submission_file'];

            // Validate file
            $allowed_types = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $max_size = 50 * 1024 * 1024; // 50MB

            if (!in_array($file_extension, $allowed_types)) {
                $_SESSION['error'] = 'Invalid file type. Allowed types: PDF, DOC, DOCX, ZIP, TXT, JPG, PNG.';
            } elseif ($file['size'] > $max_size) {
                $_SESSION['error'] = 'File size too large. Maximum size: 50MB.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'File upload failed. Please try again.';
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/submissions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Get milestone and student info for filename
                $info_query = "SELECT m.milestone_title, u.user_name FROM milestones m, users u WHERE m.milestone_id = ? AND u.user_id = ?";
                $info_stmt = $conn->prepare($info_query);
                $info_stmt->bind_param("ii", $submission_milestone_id, $submission_uploaded_by);
                $info_stmt->execute();
                $info_result = $info_stmt->get_result();
                $info = $info_result->fetch_assoc();
                $info_stmt->close();

                // Generate new filename: milestoneName_submissionId_student_name(withourspaces)
                $milestone_name = preg_replace('/[^A-Za-z0-9]/', '', $info['milestone_title']); // Remove special chars
                $student_name = preg_replace('/\s+/', '', $info['user_name']); // Remove spaces
                $new_filename = $milestone_name . '_' . $submission_id . '_' . $student_name . '.' . $file_extension;
                $file_path = $upload_dir . $new_filename;

                // Move uploaded file with new name
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['error'] = 'Failed to save uploaded file.';
                    echo '<script>window.location.href = "layout.php?page=submissions";</script>';
                    exit();
                }
            }
        }

        // Update submission
        if ($file_path) {
            // Update with new file
            $update_query = "UPDATE submissions SET submission_milestone_id = ?, submission_project_id = ?, submission_uploaded_by = ?, submission_file_path = ?, submission_notes = ? WHERE submission_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("iiissi", $submission_milestone_id, $submission_project_id, $submission_uploaded_by, $file_path, $submission_notes, $submission_id);
        } else {
            // Update without changing file
            $update_query = "UPDATE submissions SET submission_milestone_id = ?, submission_project_id = ?, submission_uploaded_by = ?, submission_notes = ? WHERE submission_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("iiisi", $submission_milestone_id, $submission_project_id, $submission_uploaded_by, $submission_notes, $submission_id);
        }

        if ($update_stmt->execute()) {
            $_SESSION['success'] = 'Submission updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update submission. Please try again.';
        }
        $update_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=submissions";</script>';
    exit();
}

// Handle submission deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    $submission_id = $_POST['submission_id'] ?? 0;

    if (!is_numeric($submission_id)) {
        $_SESSION['error'] = 'Invalid submission ID.';
    } else {
        // Check if submission has evaluations
        $check_evaluations = "SELECT COUNT(*) as count FROM evaluations WHERE evaluation_submission_id = ?";
        $check_stmt = $conn->prepare($check_evaluations);
        $check_stmt->bind_param("i", $submission_id);
        $check_stmt->execute();
        $evaluations_result = $check_stmt->get_result()->fetch_assoc();

        if ($evaluations_result['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete submission with associated evaluations. Please remove evaluations first.';
        } else {
            // Delete submission
            $delete_query = "DELETE FROM submissions WHERE submission_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $submission_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success'] = 'Submission deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete submission. Please try again.';
            }
            $delete_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=submissions";</script>';
    exit();
}

// Get all submissions with related data
$submissions_query = "SELECT
    s.submission_id,
    s.submission_milestone_id,
    s.submission_project_id,
    s.submission_uploaded_by,
    s.submission_file_path,
    s.submission_notes,
    s.submission_upload_date,

    -- Milestone info
    m.milestone_title,

    -- Project info
    p.project_title,

    -- User info
    u.user_name,
    u.user_email,

    -- Evaluation status
    CASE WHEN e.evaluation_id IS NOT NULL THEN 1 ELSE 0 END as is_evaluated,
    e.evaluation_score,
    e.evaluation_eval_date

FROM submissions s
LEFT JOIN milestones m ON s.submission_milestone_id = m.milestone_id
LEFT JOIN projects p ON s.submission_project_id = p.project_id
LEFT JOIN users u ON s.submission_uploaded_by = u.user_id
LEFT JOIN evaluations e ON s.submission_id = e.evaluation_submission_id
ORDER BY s.submission_upload_date DESC";

$submissions_result = $conn->query($submissions_query);

// Get dropdown data
$milestones_query = "SELECT milestone_id, milestone_title FROM milestones ORDER BY milestone_title";
$milestones_result = $conn->query($milestones_query);

$projects_query = "SELECT project_id, project_title, project_student_id FROM projects ORDER BY project_title";
$projects_result = $conn->query($projects_query);

$users_query = "SELECT u.user_id, u.user_name, u.user_email, p.project_id as user_project_id 
                FROM users u 
                LEFT JOIN projects p ON u.user_id = p.project_student_id AND p.project_status IN ('pending', 'in_progress', 'completed')
                WHERE u.user_role = 'student' 
                ORDER BY u.user_name";
$users_result = $conn->query($users_query);

// Get submission statistics
$submission_stats_query = "SELECT COUNT(*) as total_submissions FROM submissions";
$submission_stats_result = $conn->query($submission_stats_query);
$submission_stats = $submission_stats_result->fetch_assoc();

// Get recent submissions (last 7 days)
$recent_subs_query = "SELECT COUNT(*) as recent_submissions FROM submissions WHERE submission_upload_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$recent_subs_result = $conn->query($recent_subs_query);
$recent_subs_stats = $recent_subs_result->fetch_assoc();

// Get submissions with evaluations
$eval_subs_query = "SELECT COUNT(DISTINCT s.submission_id) as evaluated_submissions FROM submissions s INNER JOIN evaluations e ON s.submission_id = e.evaluation_submission_id";
$eval_subs_result = $conn->query($eval_subs_query);
$eval_subs_stats = $eval_subs_result->fetch_assoc();

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
    <h2>Submissions Management</h2>
    <p>Manage student submissions and uploaded files.</p>
</div>

<!-- Submission Statistics -->
<div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="file-text" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $submission_stats['total_submissions']; ?></h4>
            <p>Total Submissions</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="trending-up" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $recent_subs_stats['recent_submissions']; ?></h4>
            <p>This Week</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="clipboard-check" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $eval_subs_stats['evaluated_submissions']; ?></h4>
            <p>Evaluated</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="clock" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $submission_stats['total_submissions'] - $eval_subs_stats['evaluated_submissions']; ?></h4>
            <p>Pending Review</p>
        </div>
    </div>
</div>

<!-- Submissions Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>All Submissions</h3>
        <button class="btn btn-primary btn-md" onclick="toggleAddSubmissionForm()" disabled>
            <i data-lucide="file-plus" class="icon-sm"></i>
            Add New Submission
        </button>
    </div>

    <div class="card-body">
        <!-- Add New Submission Form -->
        <div id="add-submission-section" class="add-submission-section mb-5" style="display: none;">
            <h4>Add New Submission</h4>
            <form method="POST" action="" enctype="multipart/form-data" class="inline-form mt-3">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Milestone</label>
                        <select name="submission_milestone_id" class="form-control" required>
                            <option value="">Select Milestone</option>
                            <?php while ($milestone = $milestones_result->fetch_assoc()): ?>
                                <option value="<?php echo $milestone['milestone_id']; ?>">
                                    <?php echo htmlspecialchars($milestone['milestone_title']); ?> (ID: <?php echo $milestone['milestone_id']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select name="submission_project_id" id="submission_project_id" class="form-control" required>
                            <option value="">Select Project</option>
                            <?php while ($project = $projects_result->fetch_assoc()): ?>
                                <option value="<?php echo $project['project_id']; ?>" data-student-id="<?php echo $project['project_student_id']; ?>">
                                    <?php echo htmlspecialchars($project['project_title']); ?> (ID: <?php echo $project['project_id']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student</label>
                        <select name="submission_uploaded_by" id="submission_uploaded_by" class="form-control" required>
                            <option value="">Select Student</option>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <option value="<?php echo $user['user_id']; ?>" data-project-id="<?php echo $user['user_project_id'] ?? ''; ?>">
                                    <?php echo htmlspecialchars($user['user_name']); ?> (<?php echo htmlspecialchars($user['user_email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">File Upload</label>
                                                                <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip,.txt,.jpg,.png" class="form-control">
                        <small class="form-text">Allowed types: PDF, DOC, DOCX, ZIP, TXT, JPG, PNG. Max size: 50MB</small>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="submission_notes" class="form-control" rows="3" placeholder="Optional notes about the submission"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_submission" class="btn btn-primary btn-sm">
                        <i data-lucide="plus" class="icon-sm"></i>
                        Create Submission
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleAddSubmissionForm()">
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
                        <th>Milestone</th>
                        <th>Project</th>
                        <th>Student</th>
                        <th class="file-column">File</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($submission = $submissions_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $submission['submission_id']; ?></td>
                            <td><?php echo htmlspecialchars($submission['milestone_title'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($submission['project_title'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($submission['user_name'] ?? 'N/A'); ?></td>
                            <td class="file-column">
                                <a href="<?php echo htmlspecialchars($submission['submission_file_path']); ?>" target="_blank" class="file-link">
                                    <i data-lucide="file" class="icon-sm"></i>
                                    <?php echo basename($submission['submission_file_path']); ?>
                                </a>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($submission['submission_upload_date'])); ?></td>
                            <td>
                                <?php if ($submission['is_evaluated']): ?>
                                    <span class="badge badge-completed">
                                        Evaluated
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-pending">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline" onclick="toggleEditForm(<?php echo $submission['submission_id']; ?>)">
                                        <i data-lucide="edit" class="icon-sm"></i>
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="toggleDeleteForm(<?php echo $submission['submission_id']; ?>)">
                                        <i data-lucide="trash" class="icon-sm"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Submission Form -->
                        <tr id="edit-form-<?php echo $submission['submission_id']; ?>" class="edit-form-row" style="display: none;">
                            <td colspan="8">
                                <div class="inline-form-container">
                                    <h5>Edit Submission</h5>
                                    <form method="POST" action="" enctype="multipart/form-data" class="inline-form">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Milestone</label>
                                                <select name="submission_milestone_id" class="form-control" required>
                                                    <option value="">Select Milestone</option>
                                                    <?php
                                                    $milestones_result->data_seek(0); // Reset result pointer
                                                    while ($milestone = $milestones_result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo $milestone['milestone_id']; ?>" <?php echo ($milestone['milestone_id'] == $submission['submission_milestone_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($milestone['milestone_title']); ?> (ID: <?php echo $milestone['milestone_id']; ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Project</label>
                                                <select name="submission_project_id" class="form-control" required>
                                                    <option value="">Select Project</option>
                                                    <?php
                                                    $projects_result->data_seek(0); // Reset result pointer
                                                    while ($project = $projects_result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo $project['project_id']; ?>" <?php echo ($project['project_id'] == $submission['submission_project_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($project['project_title']); ?> (ID: <?php echo $project['project_id']; ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Student</label>
                                                <select name="submission_uploaded_by" class="form-control" required>
                                                    <option value="">Select Student</option>
                                                    <?php
                                                    $users_result->data_seek(0); // Reset result pointer
                                                    while ($user = $users_result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($user['user_id'] == $submission['submission_uploaded_by']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($user['user_name']); ?> (<?php echo htmlspecialchars($user['user_email']); ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Upload New File (Optional)</label>
                                                <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.zip,.txt,.jpg,.png" class="form-control">
                                                <small class="form-hint">Leave empty to keep current file. Current: <?php echo htmlspecialchars(basename($submission['submission_file_path'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Notes</label>
                                            <textarea name="submission_notes" class="form-control" rows="3" required><?php echo htmlspecialchars($submission['submission_notes'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_submission" class="btn btn-primary btn-sm">
                                                <i data-lucide="save" class="icon-sm"></i>
                                                Update Submission
                                            </button>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditForm(<?php echo $submission['submission_id']; ?>)">
                                                <i data-lucide="x" class="icon-sm"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Delete Confirmation -->
                        <tr id="delete-form-<?php echo $submission['submission_id']; ?>" class="delete-form-row" style="display: none;">
                            <td colspan="8">
                                <div class="delete-confirmation">
                                    <div class="alert alert-warning">
                                        <i data-lucide="alert-triangle" class="icon-sm"></i>
                                        <strong>Confirm Deletion</strong>
                                        <p>Are you sure you want to delete this submission? This action cannot be undone.</p>
                                        <div class="form-actions">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                                <button type="submit" name="delete_submission" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash" class="icon-sm"></i>
                                                    Yes, Delete Submission
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleDeleteForm(<?php echo $submission['submission_id']; ?>)">
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
function toggleAddSubmissionForm() {
    const form = document.getElementById('add-submission-section');
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleEditForm(submissionId) {
    const form = document.getElementById('edit-form-' + submissionId);
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'table-row';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleDeleteForm(submissionId) {
    const form = document.getElementById('delete-form-' + submissionId);
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
    const addForm = document.getElementById('add-submission-section');
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
        !event.target.closest('.delete-confirmation') && !event.target.closest('#add-submission-section')) {
        closeAllForms();
    }
});

// Auto-select related fields in add submission form
document.addEventListener('DOMContentLoaded', function() {
    const projectSelect = document.getElementById('submission_project_id');
    const studentSelect = document.getElementById('submission_uploaded_by');

    if (projectSelect && studentSelect) {
        // When project is selected, auto-select the student
        projectSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const studentId = selectedOption.getAttribute('data-student-id');

            if (studentId) {
                studentSelect.value = studentId;
            }
        });

        // When student is selected, auto-select their project
        studentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const projectId = selectedOption.getAttribute('data-project-id');

            if (projectId) {
                projectSelect.value = projectId;
            }
        });
    }
});
</script>
