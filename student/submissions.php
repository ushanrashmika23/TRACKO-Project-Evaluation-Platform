<?php
// Student Submissions Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Get the next eligible milestone for submission
$next_milestone_query = "SELECT
    u.user_id AS student_id,
    u.user_name AS student_name,
    p.project_id,
    p.project_title,
    m.milestone_id,
    m.milestone_title,
    m.milestone_description,
    m.milestone_due_date
FROM users u
JOIN projects p ON p.project_student_id = u.user_id
JOIN milestones m
WHERE u.user_role = 'student' AND u.user_id = ?
  AND m.milestone_id NOT IN (
      SELECT DISTINCT s.submission_milestone_id
      FROM submissions s
      JOIN evaluations e ON e.evaluation_submission_id = s.submission_id
      WHERE s.submission_student_id = ?
        AND s.submission_project_id = p.project_id
        AND e.evaluation_score >= $failMark
  )
ORDER BY m.milestone_due_date ASC
LIMIT 1";

// Execute next milestone query before submission logic
$stmt = $conn->prepare($next_milestone_query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$next_milestone_result = $stmt->get_result();
$next_milestone = $next_milestone_result->fetch_assoc();
$stmt->close();

// Handle submission upload and withdrawal
$toastMessage = '';
$toastType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_submission'])) {
        if (!isset($_SESSION['user_id'])) {
            $toastMessage = 'You must be logged in to submit.';
            $toastType = 'error';
        } elseif (!$next_milestone) {
            print_r($next_milestone);
            $toastMessage = 'No eligible milestone found for submission.';
            $toastType = 'error';
        } elseif (empty($_FILES['submission_file']['name'])) {
            $toastMessage = 'Please select a file to upload.';
            $toastType = 'error';
        } else {
            $file = $_FILES['submission_file'];

            // Validate file
            $allowed_types = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $max_size = 50 * 1024 * 1024; // 50MB

            if (!in_array($file_extension, $allowed_types)) {
                $toastMessage = 'Invalid file type. Allowed types: PDF, DOC, DOCX, ZIP, TXT, JPG, PNG.';
                $toastType = 'error';
            } elseif ($file['size'] > $max_size) {
                $toastMessage = 'File size too large. Maximum size: 50MB.';
                $toastType = 'error';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $toastMessage = 'File upload failed. Please try again.';
                $toastType = 'error';
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = '../uploads/submissions/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // First, insert submission record to get submission_id
                $submission_notes = $_POST['submission_notes'] ?? '';

                $insert_query = "INSERT INTO submissions (
                    submission_milestone_id,
                    submission_project_id,
                    submission_student_id,
                    submission_uploaded_by,
                    submission_file_path,
                    submission_notes,
                    submission_upload_date
                ) VALUES (?, ?, ?, ?, '', ?, NOW())";

                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param(
                    "iiiis",
                    $next_milestone['milestone_id'],
                    $next_milestone['project_id'],
                    $next_milestone['student_id'],
                    $_SESSION['user_id'],
                    $submission_notes
                );

                if ($insert_stmt->execute()) {
                    // Get the newly inserted submission ID
                    $submission_id = $conn->insert_id;

                    // Generate new filename: milestoneName_submissionId_student_name(withourspaces)
                    $milestone_name = preg_replace('/[^A-Za-z0-9]/', '', $next_milestone['milestone_title']); // Remove special chars
                    $student_name = preg_replace('/\s+/', '', $next_milestone['student_name']); // Remove spaces
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

                        $toastMessage = 'Submission uploaded successfully!';
                        $toastType = 'success';

                        // Re-execute the next milestone query to get updated data
                        $stmt = $conn->prepare($next_milestone_query);
                        $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
                        $stmt->execute();
                        $next_milestone_result = $stmt->get_result();
                        $next_milestone = $next_milestone_result->fetch_assoc();
                        $stmt->close();

                        // Set toast flag instead of redirecting
                        $show_toast = true;
                    } else {
                        // If file move failed, delete the database record
                        $delete_query = "DELETE FROM submissions WHERE submission_id = ?";
                        $delete_stmt = $conn->prepare($delete_query);
                        $delete_stmt->bind_param("i", $submission_id);
                        $delete_stmt->execute();
                        $delete_stmt->close();

                        $toastMessage = 'Failed to save uploaded file.';
                        $toastType = 'error';
                        // Set toast flag for upload failure
                        $show_toast = true;
                    }
                } else {
                    $toastMessage = 'Failed to save submission to database.';
                    $toastType = 'error';
                }
                $insert_stmt->close();
            }
        }
    } elseif (isset($_POST['withdraw_submission'])) {
        // Handle submission withdrawal
        $submission_id = $_POST['submission_id'] ?? 0;

        if (!$submission_id) {
            $toastMessage = 'Invalid submission ID.';
            $toastType = 'error';
        } else {
            // First, get the submission details to check ownership and get file path
            $check_query = "SELECT submission_file_path, submission_student_id FROM submissions WHERE submission_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $submission_result = $stmt->get_result();
            $submission_data = $submission_result->fetch_assoc();
            $stmt->close();

            if (!$submission_data) {
                $toastMessage = 'Submission not found.';
                $toastType = 'error';
            } elseif ($submission_data['submission_student_id'] != $_SESSION['user_id']) {
                $toastMessage = 'You can only withdraw your own submissions.';
                $toastType = 'error';
            } else {
                // Delete the submission
                $delete_query = "DELETE FROM submissions WHERE submission_id = ? AND submission_student_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("ii", $submission_id, $_SESSION['user_id']);

                if ($delete_stmt->execute()) {
                    $toastMessage = 'Submission withdrawn successfully!';
                    $toastType = 'success';

                    // Delete the uploaded file if it exists
                    if ($submission_data['submission_file_path'] && file_exists($submission_data['submission_file_path'])) {
                        unlink($submission_data['submission_file_path']);
                    }

                    // Re-execute the next milestone query to get updated data
                    $stmt = $conn->prepare($next_milestone_query);
                    $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
                    $stmt->execute();
                    $next_milestone_result = $stmt->get_result();
                    $next_milestone = $next_milestone_result->fetch_assoc();
                    $stmt->close();
                } else {
                    $toastMessage = 'Failed to withdraw submission.';
                    $toastType = 'error';
                }
                $delete_stmt->close();
            }
        }
    }
}

// Get all milestones for display 
$milestones_query = "SELECT * FROM milestones ORDER BY milestone_due_date ASC";
$milestones_result = $conn->query($milestones_query);

// Get submissions data for current student
$submissions_query = "
    SELECT
        u.user_id AS student_id,
        u.user_name AS student_name,
        p.project_id,
        p.project_title,
        m.milestone_id,
        m.milestone_title,
        m.milestone_description,
        m.milestone_due_date,
        s.submission_id,
        s.submission_file_path,
        s.submission_notes,
        s.submission_upload_date,
        e.evaluation_id,
        e.evaluation_score,
        e.evaluation_feedback,
        e.evaluation_eval_date,
        eval_sup.user_name AS evaluator_name
    FROM users u
    JOIN projects p ON p.project_student_id = u.user_id
    JOIN milestones m
    LEFT JOIN submissions s ON s.submission_milestone_id = m.milestone_id
        AND s.submission_project_id = p.project_id
        AND s.submission_student_id = u.user_id
    LEFT JOIN evaluations e ON e.evaluation_submission_id = s.submission_id
    LEFT JOIN users eval_sup ON e.evaluation_supervisor_id = eval_sup.user_id
    WHERE u.user_role = 'student' AND u.user_id = ?
    ORDER BY u.user_id, p.project_id, m.milestone_id";

$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$submissions_result = $stmt->get_result();

// Organize submissions by milestone
$submissions_by_milestone = [];
while ($row = $submissions_result->fetch_assoc()) {
    $milestone_id = $row['milestone_id'];
    if (!isset($submissions_by_milestone[$milestone_id])) {
        $submissions_by_milestone[$milestone_id] = [
            'milestone' => $row,
            'submissions' => []
        ];
    }
    if ($row['submission_id']) {
        $submissions_by_milestone[$milestone_id]['submissions'][] = $row;
    }
}
$stmt->close();

// Get milestones data
$milestones = [];
while ($milestone = $milestones_result->fetch_assoc()) {
    $milestones[$milestone['milestone_id']] = $milestone;
}
$milestones_result->close();
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>My Submissions</h3>
            </div>
            <div class="card-body">
                <?php if ($next_milestone): ?>
                    <!-- New Submission Form -->
                    <form method="POST" enctype="multipart/form-data" class="new-submission-form mb-5">
                        <div class="milestone-form-header">
                            <div class="milestone-form-title">
                                <h4 class="milestone-title">
                                    <?php echo htmlspecialchars($next_milestone['milestone_title']); ?> Submission
                                </h4>
                            </div>
                            <div class="milestone-form-meta">
                                <?php
                                // Check if milestone is overdue or active
                                $current_date = date('Y-m-d');
                                $due_date = $next_milestone['milestone_due_date'];
                                $is_overdue = strtotime($due_date) < strtotime($current_date);
                                ?>
                                <span class="badge badge-<?php echo $is_overdue ? 'overdue' : 'in-progress'; ?>">
                                    <?php echo $is_overdue ? 'Overdue' : 'Active'; ?>
                                </span>
                                <span class="due-date">Due:
                                    <?php echo date('M j, Y', strtotime($next_milestone['milestone_due_date'])); ?></span>
                            </div>
                        </div>
                        <div class="milestone-form-description">
                            <p><?php echo htmlspecialchars($next_milestone['milestone_description'] ?? 'Submit your work for this milestone.'); ?>
                            </p>
                        </div>
                        <!-- <hr class="form-divider"> -->
                        <div class="upload-section">
                            <div class="upload-box">
                                <input type="file" name="submission_file" id="submission-file" class="file-input"
                                    accept=".pdf,.doc,.docx,.zip,.txt,.jpg,.jpeg,.png" required>
                                <label for="submission-file" class="file-label">
                                    <span class="upload-text">Choose file or drag and drop</span>
                                    <span class="upload-hint">PDF, DOC, DOCX, ZIP, TXT, JPG, PNG (Max: 50MB)</span>
                                </label>
                            </div>
                        </div>
                        <!-- <hr class="form-divider"> -->
                        <div class="form-group">
                            <label for="submission_notes" class="form-label">Submission Notes (Optional)</label>
                            <textarea name="submission_notes" id="submission_notes" class="form-control" rows="3"
                                placeholder="Add any notes about your submission..."></textarea>
                        </div>
                        <!-- <hr class="form-divider"> -->
                        <div class="form-actions">
                            <button type="submit" name="submit_submission" class="btn btn-primary btn-md upload-btn">
                                <i data-lucide="upload" class="icon-sm"></i>
                                Upload Submission
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Completion Message -->
                    <div class="completion-message mb-5">
                        <div class="completion-card">
                            <div class="completion-icon">
                                <i data-lucide="check-circle" class="icon-lg"></i>
                            </div>
                            <h4 class="completion-title">All Caught Up!</h4>
                            <p class="completion-text">You have completed all current milestones. Great work!</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($milestones as $milestone): ?>
                    <?php
                    $milestone_data = $submissions_by_milestone[$milestone['milestone_id']] ?? ['milestone' => $milestone, 'submissions' => []];
                    $submissions = $milestone_data['submissions'];
                    $has_submissions = !empty($submissions);
                    $latest_submission = $has_submissions ? end($submissions) : null;

                    // Determine milestone status
                    $current_date = date('Y-m-d');
                    $due_date = $milestone['milestone_due_date'];
                    if ($has_submissions) {
                        $status = 'completed';
                        $status_text = 'Completed';
                    } elseif (strtotime($due_date) < strtotime($current_date)) {
                        $status = 'overdue';
                        $status_text = 'Overdue';
                    } else {
                        $status = 'in-progress';
                        $status_text = 'Active';
                    }
                    ?>

                    <!-- Milestone Card -->
                    <div class="milestone-card">
                        <div class="milestone-header">
                            <div class="milestone-info">
                                <div class="milestone-main">
                                    <h4 class="milestone-title">
                                        <?php echo htmlspecialchars($milestone['milestone_title']); ?>
                                    </h4>
                                    <p class="milestone-description">
                                        <?php echo htmlspecialchars($milestone['milestone_description'] ?? 'No description available.'); ?>
                                    </p>
                                </div>
                                <div class="milestone-meta">
                                    <span class="badge badge-<?php echo $status; ?>"><?php echo $status_text; ?></span>
                                    <span class="due-date">Due: <?php echo date('M j, Y', strtotime($due_date)); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="milestone-body">
                            <?php if ($has_submissions): ?>
                                <?php foreach ($submissions as $submission): ?>
                                    <div class="submission-card">
                                        <div class="submission-header">
                                            <div class="submission-file">
                                                <i data-lucide="file-text" class="file-icon"></i>
                                                <span><?php echo htmlspecialchars(basename($submission['submission_file_path'] ?? 'No file')); ?></span>
                                            </div>
                                            <div class="submission-status">
                                                <?php if ($submission['evaluation_id']): ?>
                                                    <span class="badge badge-approved">Evaluated</span>
                                                <?php else: ?>
                                                    <span class="badge badge-pending">Pending Review</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="submission-details">
                                            <div class="detail-row">
                                                <label>Submission Note:</label>
                                                <span><?php echo htmlspecialchars($submission['submission_notes'] ?? 'No notes provided.'); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <label>Submitted Date:</label>
                                                <span><?php echo $submission['submission_upload_date'] ? date('M j, Y', strtotime($submission['submission_upload_date'])) : 'N/A'; ?></span>
                                            </div>
                                            <?php if ($submission['evaluation_id']): ?>
                                                <div class="detail-row">
                                                    <label>Evaluation Supervisor:</label>
                                                    <span><?php echo htmlspecialchars($submission['evaluator_name'] ?? 'Unknown'); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <label>Evaluation Score:</label>
                                                    <span class="score-display">
                                                        <span
                                                            class="score-value"><?php echo htmlspecialchars($submission['evaluation_score'] ?? '0'); ?>/100</span>
                                                        <span
                                                            class="badge badge-<?php echo ($submission['evaluation_score'] >= $failMark) ? 'approved' : 'rejected'; ?>">
                                                            <?php echo ($submission['evaluation_score'] >= $failMark) ? 'Pass' : 'Fail'; ?>
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="detail-row">
                                                    <label>Evaluation Feedback:</label>
                                                    <span><?php echo htmlspecialchars($submission['evaluation_feedback'] ?? 'No feedback provided.'); ?></span>
                                                </div>
                                                <div class="detail-row">
                                                    <label>Evaluated Date:</label>
                                                    <span><?php echo $submission['evaluation_eval_date'] ? date('M j, Y', strtotime($submission['evaluation_eval_date'])) : 'N/A'; ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="submission-actions">
                                            <div class="action-buttons">
                                                <?php if ($submission['submission_file_path']): ?>
                                                    <a href="<?php echo htmlspecialchars($submission['submission_file_path']); ?>"
                                                        download class="btn btn-outline btn-sm">
                                                        <i data-lucide="download" class="icon-sm"></i>
                                                        Download Submission
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!$submission['evaluation_id']): ?>
                                                    <form method="POST" style="display: inline;"
                                                        onsubmit="return confirm('Are you sure you want to withdraw this submission? This will permanently delete the submission and all associated files.')">
                                                        <input type="hidden" name="submission_id"
                                                            value="<?php echo $submission['submission_id']; ?>">
                                                        <input type="hidden" name="withdraw_submission" value="1">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i data-lucide="x-circle" class="icon-sm"></i>
                                                            Withdraw Submission
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-submission">
                                    <p>No submissions yet for this milestone.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<?php if (!empty($toastMessage)): ?>
    <div id="toast" class="toast show toast-<?php echo $toastType; ?>">
        <div class="toast-body">
            <i data-lucide="<?php echo $toastType === 'error' ? 'alert-circle' : 'check-circle'; ?>" class="icon-sm"></i>
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

    // File upload handling - wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('submission-file');
        const fileLabel = document.querySelector('.file-label');
        const uploadText = document.querySelector('.upload-text');
        const uploadHint = document.querySelector('.upload-hint');
        const uploadBtn = document.querySelector('.upload-btn');

        let selectedFile = null;

        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (50MB)
                    const maxSize = 50 * 1024 * 1024;
                    if (file.size > maxSize) {
                        alert('File size too large. Maximum size: 50MB.');
                        resetFileInput();
                        return;
                    }

                    // Validate file type
                    const allowedTypes = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    if (!allowedTypes.includes(fileExtension)) {
                        alert('Invalid file type. Allowed types: PDF, DOC, DOCX, ZIP, TXT, JPG, PNG.');
                        resetFileInput();
                        return;
                    }

                    // Update UI
                    selectedFile = file;
                    if (uploadText) uploadText.textContent = file.name;
                    if (uploadHint) uploadHint.textContent = `Size: ${(file.size / 1024 / 1024).toFixed(2)} MB`;
                    if (uploadBtn) {
                        uploadBtn.innerHTML = '<i data-lucide="upload" class="icon-sm"></i> Upload Submission';
                        lucide.createIcons();
                    }
                } else {
                    resetFileInput();
                }
            });
        }

        function resetFileInput() {
            selectedFile = null;
            if (fileInput) fileInput.value = '';
            if (uploadText) uploadText.textContent = 'Choose file or drag and drop';
            if (uploadHint) uploadHint.textContent = 'PDF, DOC, DOCX, ZIP, TXT, JPG, PNG (Max: 50MB)';
            if (uploadBtn) {
                uploadBtn.innerHTML = '<i data-lucide="upload" class="icon-sm"></i> Upload Submission';
                lucide.createIcons();
            }
        }

    // Upload button click handler - removed since we now use form submission
    // uploadBtn.addEventListener('click', function () {
    //     if (!selectedFile) {
    //         alert('Please select a file to upload.');
    //         return;
    //     }
    //     // Here you would typically submit the form
    //     // For now, just show a success message
    //     alert('File "' + selectedFile.name + '" would be uploaded here.');
    // });

        // Drag and drop functionality
        const uploadBox = document.querySelector('.upload-box');
        if (uploadBox) {
            uploadBox.addEventListener('dragover', function (e) {
                e.preventDefault();
                uploadBox.style.borderColor = 'var(--primary-blue)';
                uploadBox.style.backgroundColor = 'var(--primary-blue-light)';
            });

            uploadBox.addEventListener('dragleave', function (e) {
                e.preventDefault();
                uploadBox.style.borderColor = 'var(--medium-gray)';
                uploadBox.style.backgroundColor = 'var(--white)';
            });

            uploadBox.addEventListener('drop', function (e) {
                e.preventDefault();
                uploadBox.style.borderColor = 'var(--medium-gray)';
                uploadBox.style.backgroundColor = 'var(--white)';

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    if (fileInput) {
                        fileInput.files = files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                }
            });
        }
    });

    // Custom withdrawal confirmation dialog
    let currentSubmissionId = null;

    function showWithdrawDialog(submissionId) {
        currentSubmissionId = submissionId;
        document.getElementById('withdraw-dialog').style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        lucide.createIcons(); // Re-render icons
    }

    function hideWithdrawDialog() {
        document.getElementById('withdraw-dialog').style.display = 'none';
        document.body.style.overflow = ''; // Restore scrolling
        currentSubmissionId = null;
    }

    function confirmWithdraw() {
        if (currentSubmissionId) {
            document.getElementById('withdraw-submission-id').value = currentSubmissionId;
            document.getElementById('withdraw-form').submit();
        }
    }

    // Close dialog when clicking outside
    document.getElementById('withdraw-dialog').addEventListener('click', function (e) {
        if (e.target === this) {
            hideWithdrawDialog();
        }
    });

    // Close dialog with Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('withdraw-dialog').style.display === 'flex') {
            hideWithdrawDialog();
        }
    });

    // Toast notification handling
    const toast = document.getElementById('toast');
    if (toast && toast.classList.contains('show')) {
        // Auto-hide toast after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 5000);

        // Allow manual dismissal by clicking
        toast.addEventListener('click', () => {
            toast.classList.remove('show');
        });
    }
</script>