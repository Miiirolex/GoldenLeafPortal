<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details and leave balance
$user_query = "SELECT first_name, last_name, annual_leave_balance FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_details = $user_result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Input validation and processing (same as previous version)
    $leave_type = filter_input(INPUT_POST, 'leave_type', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
    $total_days = filter_input(INPUT_POST, 'total_days', FILTER_VALIDATE_FLOAT);
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);

    // Validation logic (same as previous version)
    $errors = [];
    if (empty($leave_type)) $errors[] = "Leave type is required.";
    if (empty($start_date)) $errors[] = "Start date is required.";
    if (empty($end_date)) $errors[] = "End date is required.";
    if ($total_days <= 0) $errors[] = "Invalid leave duration.";
    
    if ($leave_type == 'annual' && $total_days > $user_details['annual_leave_balance']) {
        $errors[] = "Insufficient annual leave balance. Available: " . $user_details['annual_leave_balance'] . " days";
    }

    // Submission logic (same as previous version)
    if (empty($errors)) {
        $insert_query = "INSERT INTO leave_applications 
            (user_id, leave_type, start_date, end_date, total_days, reason) 
            VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isssds", 
            $user_id, 
            $leave_type, 
            $start_date, 
            $end_date, 
            $total_days, 
            $reason
        );

        if ($stmt->execute()) {
            if ($leave_type == 'annual') {
                $update_balance_query = "UPDATE users SET annual_leave_balance = annual_leave_balance - ? WHERE id = ?";
                $balance_stmt = $conn->prepare($update_balance_query);
                $balance_stmt->bind_param("di", $total_days, $user_id);
                $balance_stmt->execute();
            }

            $success_message = "Leave application submitted successfully!";
        } else {
            $errors[] = "Error submitting leave application: " . $conn->error;
        }
    }
}

// Fetch recent leave applications
$recent_leaves_query = "SELECT * FROM leave_applications 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5";
$stmt = $conn->prepare($recent_leaves_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_leaves_result = $stmt->get_result();
?>
<?php

// Check for leave status updates
if (isset($_GET['status_update']) && $_GET['status_update'] == 'true') {
    // Get the user's most recent leave application status
    $status_query = "SELECT status, admin_comments FROM leave_applications 
                     WHERE user_id = ? ORDER BY reviewed_at DESC LIMIT 1";
    $stmt = $conn->prepare($status_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $status_data = $status_result->fetch_assoc();
        if ($status_data['status'] == 'approved') {
            $status_message = "Your recent leave application has been approved!";
            $status_class = "success";
        } elseif ($status_data['status'] == 'rejected') {
            $status_message = "Your recent leave application has been rejected. Reason: " . $status_data['admin_comments'];
            $status_class = "danger";
        }
    }
}

// If there's a status update, display it before the form
if (isset($status_message)): ?>
    <div class="alert alert-<?php echo $status_class; ?> alert-dismissible fade show" role="alert">
        <strong><?php echo $status_message; ?></strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application - Staff Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f7fa;
            --text-color: #333;
            --border-radius: 12px;
        }

        body {
            background-color: var(--secondary-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .leave-container {
            max-width: 650px;
            margin: 2rem auto;
            padding: 0 15px;
        }

        .leave-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .leave-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--secondary-color);
            padding-bottom: 1rem;
        }

        .leave-header-icon {
            background-color: rgba(74, 144, 226, 0.1);
            color: var(--primary-color);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .leave-header-text h2 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .leave-balance {
            background-color: rgba(74, 144, 226, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: #555;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem;
            border-color: #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #3a7bd5;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(58, 123, 213, 0.2);
        }

        .recent-leaves {
            margin-top: 2rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(74, 144, 226, 0.05);
        }
    </style>
</head>
<body>
    <div class="leave-container">
        <div class="leave-card">
            <div class="leave-header">
                <div class="leave-header-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="leave-header-text">
                    <h2>Leave Application</h2>
                    <p class="text-muted mb-0">Welcome, <?php echo htmlspecialchars($user_details['first_name'] . ' ' . $user_details['last_name']); ?></p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="leave-balance">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Annual Leave Remaining</span>
                    <strong class="text-primary"><?php echo $user_details['annual_leave_balance']; ?> days</strong>
                </div>
            </div>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="leave_type" class="form-label">Leave Type</label>
                        <select class="form-select" id="leave_type" name="leave_type" required>
                            <option value="">Select Leave Type</option>
                            <option value="annual">Annual Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="personal">Personal Leave</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="total_days" class="form-label">Total Days</label>
                        <input type="number" step="0.5" class="form-control" id="total_days" name="total_days" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for Leave</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Provide a brief explanation for your leave"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Submit Leave Application</button>
            </form>

           <div class="recent-leaves mt-4">
    <h5 class="mb-3 text-primary">Recent Leave Applications</h5>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Days</th>
                    <th>Status</th>
                    <th>Comments</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if($recent_leaves_result->num_rows > 0): ?>
                    <?php while($leave = $recent_leaves_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo ucfirst($leave['leave_type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                            <td><?php echo $leave['total_days']; ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $leave['status'] == 'approved' ? 'bg-success' : 
                                         ($leave['status'] == 'pending' ? 'bg-warning' : 'bg-danger');
                                ?>">
                                    <?php echo ucfirst($leave['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!empty($leave['admin_comments'])): ?>
                                    <button type="button" class="btn btn-sm btn-link" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($leave['admin_comments']); ?>">
                                        <i class="bi bi-chat-text"></i> View
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-leave-details" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#leaveDetailsModal"
                                    data-id="<?php echo $leave['id']; ?>"
                                    data-type="<?php echo ucfirst($leave['leave_type']); ?>"
                                    data-start="<?php echo date('M d, Y', strtotime($leave['start_date'])); ?>"
                                    data-end="<?php echo date('M d, Y', strtotime($leave['end_date'])); ?>"
                                    data-days="<?php echo $leave['total_days']; ?>"
                                    data-reason="<?php echo htmlspecialchars($leave['reason']); ?>"
                                    data-status="<?php echo ucfirst($leave['status']); ?>"
                                    data-comments="<?php echo htmlspecialchars($leave['admin_comments'] ?? ''); ?>"
                                    data-date="<?php echo date('M d, Y', strtotime($leave['created_at'])); ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-3">
                            <i class="bi bi-calendar-x text-muted fs-3"></i>
                            <p class="text-muted mb-0 mt-2">No recent leave applications</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Leave Details Modal -->
<div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Leave Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <span id="modal-status" class="badge rounded-pill px-3 py-2"></span>
                    <p class="text-muted mb-0 mt-2">Application Date: <span id="modal-date"></span></p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Leave Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Type:</div>
                            <div class="col-md-8" id="modal-type"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Period:</div>
                            <div class="col-md-8">
                                <span id="modal-start"></span> to <span id="modal-end"></span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-4 text-muted">Duration:</div>
                            <div class="col-md-8"><span id="modal-days"></span> days</div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong>Reason for Leave</strong>
                    </div>
                    <div class="card-body">
                        <p id="modal-reason" class="mb-0"></p>
                    </div>
                </div>
                
                <div class="card" id="admin-feedback-card">
                    <div class="card-header bg-light">
                        <strong>Admin Feedback</strong>
                    </div>
                    <div class="card-body">
                        <p id="modal-comments" class="mb-0"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Leave details modal
    var leaveDetailsModal = document.getElementById('leaveDetailsModal');
    leaveDetailsModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // Extract data
        var type = button.getAttribute('data-type');
        var start = button.getAttribute('data-start');
        var end = button.getAttribute('data-end');
        var days = button.getAttribute('data-days');
        var reason = button.getAttribute('data-reason');
        var status = button.getAttribute('data-status');
        var comments = button.getAttribute('data-comments');
        var date = button.getAttribute('data-date');
        
        // Update modal content
        document.getElementById('modal-type').textContent = type;
        document.getElementById('modal-start').textContent = start;
        document.getElementById('modal-end').textContent = end;
        document.getElementById('modal-days').textContent = days;
        document.getElementById('modal-reason').textContent = reason;
        document.getElementById('modal-date').textContent = date;
        
        // Set status badge
        var statusBadge = document.getElementById('modal-status');
        statusBadge.textContent = status;
        
        if (status === 'Approved') {
            statusBadge.className = 'badge rounded-pill px-3 py-2 bg-success';
        } else if (status === 'Rejected') {
            statusBadge.className = 'badge rounded-pill px-3 py-2 bg-danger';
        } else {
            statusBadge.className = 'badge rounded-pill px-3 py-2 bg-warning';
        }
        
        // Show/hide admin feedback section
        var adminFeedbackCard = document.getElementById('admin-feedback-card');
        var modalComments = document.getElementById('modal-comments');
        
        if (comments && comments !== '') {
            adminFeedbackCard.style.display = 'block';
            modalComments.textContent = comments;
        } else {
            adminFeedbackCard.style.display = 'none';
        }
    });
});
</script>
</body>
</html>