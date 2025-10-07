<?php
// Admin Projects Management Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle project creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    $project_title = trim($_POST['project_title'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $project_student_id = $_POST['project_student_id'] ?? '';
    $project_supervisor_id = $_POST['project_supervisor_id'] ?? '';
    $project_status = $_POST['project_status'] ?? 'pending';

    // Validate input
    if (empty($project_title) || empty($project_description) || empty($project_student_id) || empty($project_supervisor_id)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!in_array($project_status, ['pending', 'in_progress', 'completed'])) {
        $_SESSION['error'] = 'Invalid project status.';
    } else {
        // Check if student already has an active project
        $check_query = "SELECT project_id FROM projects WHERE project_student_id = ? AND project_status IN ('pending', 'in_progress')";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $project_student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Student already has an active project. Please complete or cancel existing project first.';
        } else {
            // Insert project
            $insert_query = "INSERT INTO projects (project_title, project_description, project_student_id, project_supervisor_id, project_status)
                           VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssiss", $project_title, $project_description, $project_student_id, $project_supervisor_id, $project_status);

            if ($insert_stmt->execute()) {
                $_SESSION['success'] = 'Project created successfully!';
            } else {
                $_SESSION['error'] = 'Failed to create project. Please try again.';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=projects";</script>';
    exit();
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $project_id = $_POST['project_id'] ?? 0;
    $project_title = trim($_POST['project_title'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $project_student_id = $_POST['project_student_id'] ?? '';
    $project_supervisor_id = $_POST['project_supervisor_id'] ?? '';
    $project_status = $_POST['project_status'] ?? 'pending';

    // Validate input
    if (empty($project_title) || empty($project_description) || empty($project_student_id) || empty($project_supervisor_id) || !is_numeric($project_id)) {
        $_SESSION['error'] = 'All fields are required.';
    } elseif (!in_array($project_status, ['pending', 'in_progress', 'completed'])) {
        $_SESSION['error'] = 'Invalid project status.';
    } else {
        // Check if student already has another active project (excluding current)
        $check_query = "SELECT project_id FROM projects WHERE project_student_id = ? AND project_status IN ('pending', 'in_progress') AND project_id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $project_student_id, $project_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Student already has another active project. Please complete or cancel existing project first.';
        } else {
            // Update project
            $update_query = "UPDATE projects SET project_title = ?, project_description = ?, project_student_id = ?, project_supervisor_id = ?, project_status = ? WHERE project_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssissi", $project_title, $project_description, $project_student_id, $project_supervisor_id, $project_status, $project_id);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'Project updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update project. Please try again.';
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=projects";</script>';
    exit();
}// Handle project deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_id = $_POST['project_id'] ?? 0;

    if (!is_numeric($project_id)) {
        $_SESSION['error'] = 'Invalid project ID.';
    } else {
        // Check if project has submissions or evaluations
        $check_submissions = "SELECT COUNT(*) as count FROM submissions WHERE submission_project_id = ?";
        $check_stmt = $conn->prepare($check_submissions);
        $check_stmt->bind_param("i", $project_id);
        $check_stmt->execute();
        $submissions_result = $check_stmt->get_result()->fetch_assoc();

        $check_evaluations = "SELECT COUNT(*) as count FROM evaluations WHERE evaluations_submission_id IN (SELECT submissions_id FROM submissions WHERE submission_project_id = ?)";
        $check_stmt = $conn->prepare($check_evaluations);
        $check_stmt->bind_param("i", $project_id);
        $check_stmt->execute();
        $evaluations_result = $check_stmt->get_result()->fetch_assoc();

        if ($submissions_result['count'] > 0 || $evaluations_result['count'] > 0) {
            $_SESSION['error'] = 'Cannot delete project with associated submissions or evaluations. Please remove dependencies first.';
        } else {
            // Delete project
            $delete_query = "DELETE FROM projects WHERE project_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $project_id);

            if ($delete_stmt->execute()) {
                $_SESSION['success'] = 'Project deleted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to delete project. Please try again.';
            }
            $delete_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page using JavaScript
    echo '<script>window.location.href = "layout.php?page=projects";</script>';
    exit();
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_student = $_GET['student'] ?? '';
$filter_supervisor = $_GET['supervisor'] ?? '';

// Build query with filters
$query_parts = [];
$params = [];
$types = '';

if (!empty($filter_status)) {
    $query_parts[] = "p.project_status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_student)) {
    $query_parts[] = "p.project_student_id = ?";
    $params[] = $filter_student;
    $types .= 'i';
}

if (!empty($filter_supervisor)) {
    $query_parts[] = "p.project_supervisor_id = ?";
    $params[] = $filter_supervisor;
    $types .= 'i';
}

$where_clause = !empty($query_parts) ? "WHERE " . implode(" AND ", $query_parts) : "";

// Get all projects with user details
$projects_query = "SELECT
    p.project_id,
    p.project_title,
    p.project_description,
    p.project_status,
    p.project_created_at,
    p.project_student_id,
    p.project_supervisor_id,

    -- Student details
    stu.user_name AS student_name,
    stu.user_email AS student_email,

    -- Supervisor details
    sup.user_name AS supervisor_name,
    sup.user_email AS supervisor_email

FROM projects p
JOIN users stu ON p.project_student_id = stu.user_id
LEFT JOIN users sup ON p.project_supervisor_id = sup.user_id
$where_clause
ORDER BY p.project_created_at DESC";

if (!empty($params)) {
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param($types, ...$params);
    $projects_stmt->execute();
    $projects_result = $projects_stmt->get_result();
} else {
    $projects_result = $conn->query($projects_query);
}

// Get students and supervisors for dropdowns
$students_query = "SELECT user_id, user_name, user_email FROM users WHERE user_role = 'student' ORDER BY user_name";
$students_result = $conn->query($students_query);

$supervisors_query = "SELECT user_id, user_name, user_email FROM users WHERE user_role = 'supervisor' ORDER BY user_name";
$supervisors_result = $conn->query($supervisors_query);

// Get project statistics
$project_stats_query = "SELECT
    COUNT(CASE WHEN project_status = 'pending' THEN 1 END) as total_pending,
    COUNT(CASE WHEN project_status = 'in_progress' THEN 1 END) as total_in_progress,
    COUNT(CASE WHEN project_status = 'completed' THEN 1 END) as total_completed,
    COUNT(*) as total_projects
    FROM projects";
$project_stats_result = $conn->query($project_stats_query);
$project_stats = $project_stats_result->fetch_assoc();

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
    <h2>Projects Management</h2>
    <p>Oversee all projects and assignments in the system.</p>
</div>

<!-- Project Statistics -->
<div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="folder-open" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $project_stats['total_projects']; ?></h4>
            <p>Total Projects</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="clock" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $project_stats['total_pending']; ?></h4>
            <p>Pending</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="play-circle" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $project_stats['total_in_progress']; ?></h4>
            <p>In Progress</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <i data-lucide="check-circle" class="stat-icon-svg"></i>
        </div>
        <div class="stat-content">
            <h4><?php echo $project_stats['total_completed']; ?></h4>
            <p>Completed</p>
        </div>
    </div>
</div>

<!-- Project Filters -->
<div class="filters-section">
    <form method="GET" action="layout.php" class="filters-form">
        <input type="hidden" name="page" value="projects">
        <div class="filter-row">
            <div class="filter-group">
                <label for="status" class="filter-label">Filter by Status</label>
                <select name="status" id="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo ($filter_status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="student" class="filter-label">Filter by Student</label>
                <select name="student" id="student" class="filter-select">
                    <option value="">All Students</option>
                    <?php
                    $students_result->data_seek(0); // Reset result pointer
                    while ($student = $students_result->fetch_assoc()):
                    ?>
                        <option value="<?php echo $student['user_id']; ?>" <?php echo ($filter_student == $student['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($student['user_name']); ?> (<?php echo htmlspecialchars($student['user_email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="supervisor" class="filter-label">Filter by Supervisor</label>
                <select name="supervisor" id="supervisor" class="filter-select">
                    <option value="">All Supervisors</option>
                    <?php
                    $supervisors_result->data_seek(0); // Reset result pointer
                    while ($supervisor = $supervisors_result->fetch_assoc()):
                    ?>
                        <option value="<?php echo $supervisor['user_id']; ?>" <?php echo ($filter_supervisor == $supervisor['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supervisor['user_name']); ?> (<?php echo htmlspecialchars($supervisor['user_email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary btn-sm" style="align-self: end;">
                    <i data-lucide="filter" class="icon-sm"></i>
                    Apply Filters
                </button>
                <?php if (!empty($filter_status) || !empty($filter_student) || !empty($filter_supervisor)): ?>
                    <a href="layout.php?page=projects" class="btn btn-outline btn-sm" style="align-self: end; margin-left: 0.5rem;">
                        <i data-lucide="x" class="icon-sm"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Projects Table -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>All Projects <?php
            $project_count = $projects_result->num_rows;
            if (!empty($filter_status) || !empty($filter_student) || !empty($filter_supervisor)) {
                echo "<small class='text-muted'>($project_count filtered)</small>";
            } else {
                echo "<small class='text-muted'>($project_count total)</small>";
            }
        ?></h3>
        <button class="btn btn-primary btn-md" onclick="toggleAddProjectForm()">
            <i data-lucide="folder-plus" class="icon-sm"></i>
            Add New Project
        </button>
    </div>

    <div class="card-body">
        <!-- Add New Project Form -->
        <div id="add-project-section" class="add-project-section mb-5" style="display: none;">
            <h4>Add New Project</h4>
            <form method="POST" action="" class="inline-form mt-3">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project Title</label>
                        <input type="text" name="project_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student</label>
                        <select name="project_student_id" class="form-control" required>
                            <option value="">Select Student</option>
                            <?php while ($student = $students_result->fetch_assoc()): ?>
                                <option value="<?php echo $student['user_id']; ?>">
                                    <?php echo htmlspecialchars($student['user_name']) . ' (' . htmlspecialchars($student['user_email']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Supervisor</label>
                        <select name="project_supervisor_id" class="form-control" required>
                            <option value="">Select Supervisor</option>
                            <?php while ($supervisor = $supervisors_result->fetch_assoc()): ?>
                                <option value="<?php echo $supervisor['user_id']; ?>">
                                    <?php echo htmlspecialchars($supervisor['user_name']) . ' (' . htmlspecialchars($supervisor['user_email']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="project_status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Project Description</label>
                    <textarea name="project_description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_project" class="btn btn-primary btn-sm">
                        <i data-lucide="plus" class="icon-sm"></i>
                        Create Project
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleAddProjectForm()">
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
                        <th>Student</th>
                        <th>Supervisor</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($project = $projects_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $project['project_id']; ?></td>
                            <td><?php echo htmlspecialchars($project['project_title']); ?></td>
                            <td><?php echo htmlspecialchars($project['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['supervisor_name'] ?? 'Not Assigned'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo str_replace('_', '-', $project['project_status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['project_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($project['project_created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline" onclick="toggleEditForm(<?php echo $project['project_id']; ?>)">
                                        <i data-lucide="edit" class="icon-sm"></i>
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="toggleDeleteForm(<?php echo $project['project_id']; ?>)">
                                        <i data-lucide="trash" class="icon-sm"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Project Form -->
                        <tr id="edit-form-<?php echo $project['project_id']; ?>" class="edit-form-row" style="display: none;">
                            <td colspan="7">
                                <div class="inline-form-container">
                                    <h5>Edit Project</h5>
                                    <form method="POST" action="" class="inline-form">
                                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Project Title</label>
                                                <input type="text" name="project_title" class="form-control" value="<?php echo htmlspecialchars($project['project_title']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Student</label>
                                                <select name="project_student_id" class="form-control" required>
                                                    <option value="">Select Student</option>
                                                    <?php
                                                    $students_result->data_seek(0); // Reset result pointer
                                                    while ($student = $students_result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo $student['user_id']; ?>" <?php echo ($student['user_id'] == $project['project_student_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($student['user_name']) . ' (' . htmlspecialchars($student['user_email']) . ')'; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Supervisor</label>
                                                <select name="project_supervisor_id" class="form-control" required>
                                                    <option value="">Select Supervisor</option>
                                                    <?php
                                                    $supervisors_result->data_seek(0); // Reset result pointer
                                                    while ($supervisor = $supervisors_result->fetch_assoc()):
                                                    ?>
                                                        <option value="<?php echo $supervisor['user_id']; ?>" <?php echo ($supervisor['user_id'] == $project['project_supervisor_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($supervisor['user_name']) . ' (' . htmlspecialchars($supervisor['user_email']) . ')'; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Status</label>
                                                <select name="project_status" class="form-control" required>
                                                    <option value="pending" <?php echo ($project['project_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo ($project['project_status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="completed" <?php echo ($project['project_status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Project Description</label>
                                            <textarea name="project_description" class="form-control" rows="3" required><?php echo htmlspecialchars($project['project_description']); ?></textarea>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_project" class="btn btn-primary btn-sm">
                                                <i data-lucide="save" class="icon-sm"></i>
                                                Update Project
                                            </button>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleEditForm(<?php echo $project['project_id']; ?>)">
                                                <i data-lucide="x" class="icon-sm"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Delete Confirmation -->
                        <tr id="delete-form-<?php echo $project['project_id']; ?>" class="delete-form-row" style="display: none;">
                            <td colspan="7">
                                <div class="delete-confirmation">
                                    <div class="alert alert-warning">
                                        <i data-lucide="alert-triangle" class="icon-sm"></i>
                                        <strong>Confirm Deletion</strong>
                                        <p>Are you sure you want to delete the project "<strong><?php echo htmlspecialchars($project['project_title']); ?></strong>"? This action cannot be undone.</p>
                                        <div class="form-actions">
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                                <button type="submit" name="delete_project" class="btn btn-outline-danger btn-sm">
                                                    <i data-lucide="trash" class="icon-sm"></i>
                                                    Yes, Delete Project
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="toggleDeleteForm(<?php echo $project['project_id']; ?>)">
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
function toggleAddProjectForm() {
    const form = document.getElementById('add-project-section');
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleEditForm(projectId) {
    const form = document.getElementById('edit-form-' + projectId);
    const isVisible = form.style.display !== 'none';

    // Close all other forms first
    closeAllForms();

    if (!isVisible) {
        form.style.display = 'table-row';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function toggleDeleteForm(projectId) {
    const form = document.getElementById('delete-form-' + projectId);
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
    const addForm = document.getElementById('add-project-section');
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
        !event.target.closest('.delete-confirmation') && !event.target.closest('#add-project-section')) {
        closeAllForms();
    }
});
</script>