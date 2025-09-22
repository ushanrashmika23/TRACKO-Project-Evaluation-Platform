<?php
// Supervisor Evaluations Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Handle evaluation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $evaluation_score = $_POST['evaluation_score'] ?? '';
    $evaluation_feedback = $_POST['evaluation_feedback'] ?? '';

    // Validate input
    if (empty($submission_id) || !is_numeric($evaluation_score) || $evaluation_score < 0 || $evaluation_score > 100) {
        $_SESSION['error'] = 'Invalid evaluation data. Please check your input.';
    } else {
        // Check if evaluation already exists
        $check_query = "SELECT evaluation_id FROM evaluations WHERE evaluation_submission_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $submission_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'This submission has already been evaluated.';
        } else {
            // Insert evaluation
            $insert_query = "INSERT INTO evaluations (evaluation_submission_id, evaluation_supervisor_id, evaluation_score, evaluation_feedback, evaluation_eval_date)
                           VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiss", $submission_id, $_SESSION['user_id'], $evaluation_score, $evaluation_feedback);

            if ($insert_stmt->execute()) {
                $_SESSION['success'] = 'Evaluation submitted successfully!';
            } else {
                $_SESSION['error'] = 'Failed to submit evaluation. Please try again.';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }

    // Redirect to refresh the page
    header('Location: layout.php?page=evaluations&' . http_build_query($_GET));
    exit();
}

// Get filter parameters
$filter_project = $_GET['project'] ?? '';
$filter_student = $_GET['student'] ?? '';
$filter_status = $_GET['status'] ?? 'pending'; // pending, evaluated

// Build query for submissions needing evaluation or already evaluated
$where_conditions = [];
$params = [];
$types = '';

if ($filter_project) {
    $where_conditions[] = "p.project_id = ?";
    $params[] = $filter_project;
    $types .= 'i';
}

if ($filter_student) {
    $where_conditions[] = "stu.user_id = ?";
    $params[] = $filter_student;
    $types .= 'i';
}

// Always filter by supervisor
$where_conditions[] = "p.project_supervisor_id = ?";
$params[] = $_SESSION['user_id'];
$types .= 'i';

$where_clause = implode(' AND ', $where_conditions);

// Get submissions based on filter
if ($filter_status === 'pending') {
    // Submissions that haven't been evaluated yet
    $query = "SELECT
        s.submission_id,
        s.submission_file_path,
        s.submission_notes,
        s.submission_upload_date,
        p.project_id,
        p.project_title,
        stu.user_id as student_id,
        stu.user_name as student_name,
        stu.user_email as student_email,
        m.milestone_title,
        m.milestone_due_date
    FROM submissions s
    JOIN projects p ON s.submission_project_id = p.project_id
    JOIN users stu ON s.submission_student_id = stu.user_id
    JOIN milestones m ON s.submission_milestone_id = m.milestone_id
    LEFT JOIN evaluations e ON s.submission_id = e.evaluation_submission_id
    WHERE e.evaluation_id IS NULL
    AND " . $where_clause . "
    ORDER BY s.submission_upload_date DESC";
} else {
    // Already evaluated submissions
    $query = "SELECT
        s.submission_id,
        s.submission_file_path,
        s.submission_notes,
        s.submission_upload_date,
        p.project_id,
        p.project_title,
        stu.user_id as student_id,
        stu.user_name as student_name,
        stu.user_email as student_email,
        m.milestone_title,
        m.milestone_due_date,
        e.evaluation_id,
        e.evaluation_score,
        e.evaluation_feedback,
        e.evaluation_eval_date
    FROM submissions s
    JOIN projects p ON s.submission_project_id = p.project_id
    JOIN users stu ON s.submission_student_id = stu.user_id
    JOIN milestones m ON s.submission_milestone_id = m.milestone_id
    JOIN evaluations e ON s.submission_id = e.evaluation_submission_id
    WHERE e.evaluation_supervisor_id = ?
    AND " . $where_clause . "
    ORDER BY e.evaluation_eval_date DESC";

    // Add the supervisor ID for evaluated submissions
    array_unshift($params, $_SESSION['user_id']);
    $types = 'i' . $types;
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$submissions = [];
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}
$stmt->close();

// Get filter options (projects and students for this supervisor)
$projects_query = "SELECT DISTINCT p.project_id, p.project_title
                   FROM projects p
                   WHERE p.project_supervisor_id = ?
                   ORDER BY p.project_title";
$projects_stmt = $conn->prepare($projects_query);
$projects_stmt->bind_param("i", $_SESSION['user_id']);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
$projects = [];
while ($row = $projects_result->fetch_assoc()) {
    $projects[] = $row;
}
$projects_stmt->close();

$students_query = "SELECT DISTINCT u.user_id, u.user_name, u.user_email
                   FROM users u
                   JOIN projects p ON u.user_id = p.project_student_id
                   WHERE p.project_supervisor_id = ?
                   ORDER BY u.user_name";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $_SESSION['user_id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}
$students_stmt->close();
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>Evaluations</h3>
            </div>
            <div class="card-body">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i data-lucide="check-circle" class="icon-sm"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i data-lucide="alert-circle" class="icon-sm"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" action="layout.php" class="filters-form" id="filterForm">
                        <input type="hidden" name="page" value="evaluations">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status" class="filter-label">Status</label>
                                <select name="status" id="status" class="filter-select">
                                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Evaluation</option>
                                    <option value="evaluated" <?php echo $filter_status === 'evaluated' ? 'selected' : ''; ?>>Evaluated</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="project" class="filter-label">Project</label>
                                <select name="project" id="project" class="filter-select">
                                    <option value="">All Projects</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['project_id']; ?>" <?php echo $filter_project == $project['project_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['project_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="student" class="filter-label">Student</label>
                                <select name="student" id="student" class="filter-select">
                                    <option value="">All Students</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['user_id']; ?>" <?php echo $filter_student == $student['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['user_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary btn-sm" style="align-self: end;">
                                    <i data-lucide="filter" class="icon-sm"></i>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Submissions List -->
                <?php if (!empty($submissions)): ?>
                    <div class="submissions-list">
                        <?php foreach ($submissions as $submission): ?>
                            <div class="submission-card-full">
                                <div class="submission-header-section">
                                    <div class="submission-info">
                                        <div class="submission-title-section">
                                            <h4 class="submission-title">
                                                <?php echo htmlspecialchars($submission['project_title']); ?> -
                                                <?php echo htmlspecialchars($submission['milestone_title']); ?>
                                            </h4>
                                            <div class="submission-meta">
                                                <span class="student-info">
                                                    <i data-lucide="user" class="icon-xs"></i>
                                                    <?php echo htmlspecialchars($submission['student_name']); ?>
                                                </span>
                                                <span class="submission-date">
                                                    <i data-lucide="calendar" class="icon-xs"></i>
                                                    Submitted <?php echo date('M j, Y', strtotime($submission['submission_upload_date'])); ?>
                                                </span>
                                                <?php
                                                $due_date = strtotime($submission['milestone_due_date']);
                                                $submit_date = strtotime($submission['submission_upload_date']);
                                                $is_overdue = $submit_date > $due_date;
                                                ?>
                                                <span class="badge <?php echo $is_overdue ? 'badge-overdue' : 'badge-completed'; ?>">
                                                    <?php echo $is_overdue ? 'Overdue' : 'On Time'; ?>
                                                </span>
                                                <?php if (isset($submission['evaluation_id'])): ?>
                                                    <span class="badge badge-completed">
                                                        Evaluated (<?php echo htmlspecialchars($submission['evaluation_score']); ?>/100)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">
                                                        Pending Evaluation
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="submission-actions">
                                            <button class="btn btn-outline btn-sm" onclick="toggleSubmissionDetails(this)">
                                                <i data-lucide="chevron-down" class="icon-sm toggle-icon"></i>
                                                See Details
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="submission-content-section" style="display: none;">
                                    <div class="submission-details-grid">
                                        <div class="detail-section submission-info-section">
                                            <h5>Submission Information</h5>
                                            <div class="detail-items">
                                                <div class="detail-item">
                                                    <label>Project:</label>
                                                    <span><?php echo htmlspecialchars($submission['project_title']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <label>Milestone:</label>
                                                    <span><?php echo htmlspecialchars($submission['milestone_title']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <label>Due Date:</label>
                                                    <span><?php echo date('M j, Y', strtotime($submission['milestone_due_date'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <label>Student:</label>
                                                    <span><?php echo htmlspecialchars($submission['student_name']); ?> (<?php echo htmlspecialchars($submission['student_email']); ?>)</span>
                                                </div>
                                                <div class="detail-item">
                                                    <label>Submission Date:</label>
                                                    <span><?php echo date('M j, Y \a\t g:i A', strtotime($submission['submission_upload_date'])); ?></span>
                                                </div>
                                            </div>
                                            <?php if ($submission['submission_file_path']): ?>
                                                <div class="detail-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--medium-gray);">
                                                    <a href="<?php echo htmlspecialchars($submission['submission_file_path']); ?>"
                                                       download class="btn btn-primary btn-sm">
                                                        <i data-lucide="download" class="icon-sm"></i>
                                                        Download Submission File
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($submission['submission_notes']): ?>
                                            <!-- <div class="detail-section">
                                                <h5>Submission Notes</h5>
                                                <p><?php echo htmlspecialchars($submission['submission_notes']); ?></p>
                                            </div> -->
                                        <?php endif; ?>

                                        <?php if (!isset($submission['evaluation_id'])): ?>
                                            <div class="detail-section evaluation-form-section">
                                                <h5>Evaluate Submission</h5>
                                                <form class="evaluation-form" method="POST" action="">
                                                    <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                                    <div class="form-group">
                                                        <label for="score_<?php echo $submission['submission_id']; ?>" class="form-label">Score (0-100)</label>
                                                        <input type="number" class="form-control" id="score_<?php echo $submission['submission_id']; ?>"
                                                               name="evaluation_score" min="0" max="100" required>
                                                        <small class="form-help">Enter a score between 0 and 100</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="feedback_<?php echo $submission['submission_id']; ?>" class="form-label">Feedback</label>
                                                        <textarea class="form-control" id="feedback_<?php echo $submission['submission_id']; ?>"
                                                                  name="evaluation_feedback" rows="3"
                                                                  placeholder="Provide constructive feedback for the student..."></textarea>
                                                    </div>
                                                    <button type="submit" name="submit_evaluation" class="btn btn-primary btn-sm">
                                                        <i data-lucide="clipboard-check" class="icon-sm"></i>
                                                        Submit Evaluation
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($submission['evaluation_id'])): ?>
                                            <div class="detail-section evaluation-section">
                                                <h5>Evaluation Details</h5>
                                                <div class="evaluation-summary">
                                                    <div class="evaluation-score">
                                                        <span class="score-value"><?php echo htmlspecialchars($submission['evaluation_score']); ?>/100</span>
                                                        <span class="score-label">Final Score</span>
                                                    </div>
                                                    <div class="evaluation-meta">
                                                        <span>Evaluated on <?php echo date('M j, Y', strtotime($submission['evaluation_eval_date'])); ?></span>
                                                    </div>
                                                </div>
                                                <?php if ($submission['evaluation_feedback']): ?>
                                                    <div class="evaluation-feedback">
                                                        <h6>Feedback</h6>
                                                        <p><?php echo htmlspecialchars($submission['evaluation_feedback']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- <div class="submission-file-section">
                                        <a href="<?php echo htmlspecialchars($submission['submission_file_path']); ?>"
                                               download class="btn btn-outline-danger btn-sm">
                                                <i data-lucide="clipboard-check" class="icon-sm"></i>
                                                Evaluate Submission File
                                            </a>
                                    </div> -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-submissions">
                        <div class="no-submissions-icon">
                            <i data-lucide="clipboard-x" class="icon-lg"></i>
                        </div>
                        <h4><?php echo $filter_status === 'pending' ? 'No Pending Evaluations' : 'No Evaluated Submissions'; ?></h4>
                        <p><?php echo $filter_status === 'pending' ? 'All submissions have been evaluated.' : 'No submissions have been evaluated yet.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function toggleSubmissionDetails(button) {
        const card = button.closest('.submission-card-full');
        const contentSection = card.querySelector('.submission-content-section');
        const toggleIcon = button.querySelector('.toggle-icon');

        if (contentSection.style.display === 'none' || contentSection.style.display === '') {
            contentSection.style.display = 'block';
            toggleIcon.setAttribute('data-lucide', 'chevron-up');
            button.innerHTML = '<i data-lucide="chevron-up" class="icon-sm toggle-icon"></i> Hide Details';
        } else {
            contentSection.style.display = 'none';
            toggleIcon.setAttribute('data-lucide', 'chevron-down');
            button.innerHTML = '<i data-lucide="chevron-down" class="icon-sm toggle-icon"></i> See Details';
        }
        lucide.createIcons();
    }
</script>

<?php $conn->close(); ?>