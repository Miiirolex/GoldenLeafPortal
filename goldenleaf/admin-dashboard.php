<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get admin details
$admin_id = $_SESSION['admin_id'];
$admin_query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin_details = $admin_result->fetch_assoc();

// Handle filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$department_filter = isset($_GET['department']) ? $_GET['department'] : 'all';

// Build the query based on filters
$leave_query = "SELECT la.*, u.first_name, u.last_name, u.department, u.email 
                FROM leave_applications la
                JOIN users u ON la.user_id = u.id
                WHERE 1=1";

// Apply filters
if ($status_filter != 'all') {
    $leave_query .= " AND la.status = ?";
}

if ($date_filter == 'today') {
    $leave_query .= " AND DATE(la.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $leave_query .= " AND la.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($date_filter == 'month') {
    $leave_query .= " AND la.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

if ($department_filter != 'all') {
    $leave_query .= " AND u.department = ?";
}

$leave_query .= " ORDER BY la.created_at DESC";

$stmt = $conn->prepare($leave_query);

// Bind parameters based on applied filters
if ($status_filter != 'all' && $department_filter != 'all') {
    $stmt->bind_param("ss", $status_filter, $department_filter);
} elseif ($status_filter != 'all') {
    $stmt->bind_param("s", $status_filter);
} elseif ($department_filter != 'all') {
    $stmt->bind_param("s", $department_filter);
}

$stmt->execute();
$leave_applications = $stmt->get_result();

// Get pending leave count
$pending_query = "SELECT COUNT(*) as count FROM leave_applications WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['count'];

// Get approved leave count
$approved_query = "SELECT COUNT(*) as count FROM leave_applications WHERE status = 'approved'";
$approved_result = $conn->query($approved_query);
$approved_count = $approved_result->fetch_assoc()['count'];

// Get rejected leave count
$rejected_query = "SELECT COUNT(*) as count FROM leave_applications WHERE status = 'rejected'";
$rejected_result = $conn->query($rejected_query);
$rejected_count = $rejected_result->fetch_assoc()['count'];

// Mark notifications as read
$mark_read_query = "UPDATE admin_notifications SET is_read = TRUE WHERE is_read = FALSE";
$conn->query($mark_read_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: 1rem;
        }
        .admin-sidebar .nav-link:hover {
            color: rgba(255,255,255,1);
            background-color: rgba(255,255,255,.1);
        }
        .admin-sidebar .nav-link.active {
            color: white;
            background-color: #007bff;
        }
        .admin-content {
            padding: 20px;
        }
        .status-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .leave-table {
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 admin-sidebar">
                <div class="d-flex flex-column h-100">
                    <div class="text-center p-4 border-bottom">
                        <h4>Admin Panel</h4>
                        <small>Leave Management</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_employees.php">
                                <i class="bi bi-people me-2"></i> Employees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="bi bi-graph-up me-2"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_settings.php">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>
                    </ul>
                    <div class="mt-auto p-3 border-top">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-circle fs-4 me-2"></i>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($admin_details['full_name']); ?></div>
                                <small><?php echo htmlspecialchars($admin_details['role']); ?></small>
                            </div>
                        </div>
                        <a href="admin-logout.php" class="btn btn-sm btn-outline-light w-100">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Leave Applications</h2>
                    <div>
                        <span class="badge bg-primary rounded-pill p-2">
                            <i class="bi bi-bell me-1"></i> <?php echo $pending_count; ?> Pending
                        </span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="status-card bg-warning text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Pending</h6>
                                    <h2 class="mb-0"><?php echo $pending_count; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-hourglass-split fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="status-card bg-success text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Approved</h6>
                                    <h2 class="mb-0"><?php echo $approved_count; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="status-card bg-danger text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Rejected</h6>
                                    <h2 class="mb-0"><?php echo $rejected_count; ?></h2>
                                </div>
                                <div>
                                    <i class="bi bi-x-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <div class="col-md-3 mb-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="date_range" class="form-label">Date Range</label>
                                <select class="form-select" id="date_range" name="date_range">
                                    <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="all" <?php echo $department_filter == 'all' ? 'selected' : ''; ?>>All Departments</option>
                                    <option value="IT" <?php echo $department_filter == 'IT' ? 'selected' : ''; ?>>IT</option>
                                    <option value="HR" <?php echo $department_filter == 'HR' ? 'selected' : ''; ?>>HR</option>
                                    <option value="Finance" <?php echo $department_filter == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Marketing" <?php echo $department_filter == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end mb-2">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-filter me-1"></i> Filter
                                </button>
                                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Leave Applications Table -->
                <div class="card leave-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Duration</th>
                                        <th>Dates</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($leave_applications->num_rows > 0): ?>
                                        <?php while($leave = $leave_applications->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $leave['id']; ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($leave['department']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $leave['leave_type'] == 'annual' ? 'bg-info' : 
                                                            ($leave['leave_type'] == 'sick' ? 'bg-warning' : 
                                                            ($leave['leave_type'] == 'personal' ? 'bg-secondary' : 'bg-dark'));
                                                    ?>">
                                                        <?php echo ucfirst($leave['leave_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $leave['total_days']; ?> days</td>
                                                <td>
                                                    <div><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></div>
                                                    <small>to</small>
                                                    <div><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></div>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-link" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                                        View Reason
                                                    </button>
                                                </td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $leave['status'] == 'approved' ? 'bg-success' : 
                                                            ($leave['status'] == 'pending' ? 'bg-warning' : 'bg-danger');
                                                    ?>">
                                                        <?php echo ucfirst($leave['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($leave['status'] == 'pending'): ?>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-success approve-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#approveModal" 
                                                                data-id="<?php echo $leave['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>"
                                                                data-type="<?php echo ucfirst($leave['leave_type']); ?>"
                                                                data-days="<?php echo $leave['total_days']; ?>">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger reject-btn" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#rejectModal" 
                                                                data-id="<?php echo $leave['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>"
                                                                data-type="<?php echo ucfirst($leave['leave_type']); ?>"
                                                                data-days="<?php echo $leave['total_days']; ?>">
                                                                <i class="bi bi-x"></i>
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-info view-details-btn"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewDetailsModal"
                                                            data-id="<?php echo $leave['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?>"
                                                            data-type="<?php echo ucfirst($leave['leave_type']); ?>"
                                                            data-days="<?php echo $leave['total_days']; ?>"
                                                            data-reason="<?php echo htmlspecialchars($leave['reason']); ?>"
                                                            data-status="<?php echo ucfirst($leave['status']); ?>"
                                                            data-comments="<?php echo htmlspecialchars($leave['admin_comments'] ?? ''); ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                    No leave applications found matching your filters
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Leave Application</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process-leave-approval.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="leave_id" id="approve_leave_id">
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <p class="mb-0"><strong>Employee:</strong> <span id="approve_employee"></span></p>
                                <p class="mb-0"><strong>Leave Type:</strong> <span id="approve_type"></span></p>
                            </div>
                            <p class="mb-0"><strong>Duration:</strong> <span id="approve_days"></span> days</p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Approving this leave will send an email notification to the employee.
                        </div>
                        
                        <div class="mb-3">
                            <label for="approve_comments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="approve_comments" name="comments" rows="3" placeholder="Add any additional notes for the employee..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Leave Application</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process-leave-approval.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="leave_id" id="reject_leave_id">
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <p class="mb-0"><strong>Employee:</strong> <span id="reject_employee"></span></p>
                                <p class="mb-0"><strong>Leave Type:</strong> <span id="reject_type"></span></p>
                            </div>
                            <p class="mb-0"><strong>Duration:</strong> <span id="reject_days"></span> days</p>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Please provide a reason for rejecting this leave application.
                        </div>
                        
                        <div class="mb-3">
                            <label for="reject_comments" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_comments" name="comments" rows="3" placeholder="Explain why this leave application is being rejected..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Leave Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <span id="details_status" class="badge rounded-pill px-3 py-2"></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Employee:</strong> <span id="details_employee"></span>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Leave Type:</strong> <span id="details_type"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Duration:</strong> <span id="details_days"></span> days
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Reason:</strong>
                        <p id="details_reason" class="mt-1 p-2 bg-light rounded"></p>
                    </div>
                    
                    <div class="mb-3" id="details_comments_section">
                        <strong>Admin Comments:</strong>
                        <p id="details_comments" class="mt-1 p-2 bg-light rounded"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
            
            // Approve modal
            var approveModal = document.getElementById('approveModal');
            approveModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var name = button.getAttribute('data-name');
                var type = button.getAttribute('data-type');
                var days = button.getAttribute('data-days');
                
                document.getElementById('approve_leave_id').value = id;
                document.getElementById('approve_employee').textContent = name;
                document.getElementById('approve_type').textContent = type;
                document.getElementById('approve_days').textContent = days;
            });
            
            // Reject modal
            var rejectModal = document.getElementById('rejectModal');
            rejectModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var name = button.getAttribute('data-name');
                var type = button.getAttribute('data-type');
                var days = button.getAttribute('data-days');
                
                document.getElementById('reject_leave_id').value = id;
                document.getElementById('reject_employee').textContent = name;
                document.getElementById('reject_type').textContent = type;
                document.getElementById('reject_days').textContent = days;
            });
            
            // View details modal
            var viewDetailsModal = document.getElementById('viewDetailsModal');
            viewDetailsModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var name = button.getAttribute('data-name');
                var type = button.getAttribute('data-type');
                var days = button.getAttribute('data-days');
                var reason = button.getAttribute('data-reason');
                var status = button.getAttribute('data-status');
                var comments = button.getAttribute('data-comments');
                
                document.getElementById('details_employee').textContent = name;
                document.getElementById('details_type').textContent = type;
                document.getElementById('details_days').textContent = days;
                document.getElementById('details_reason').textContent = reason;
                
                var statusBadge = document.getElementById('details_status');
                statusBadge.textContent = status;
                
                if (status === 'Approved') {
                    statusBadge.className = 'badge rounded-pill px-3 py-2 bg-success';
                } else if (status === 'Rejected') {
                    statusBadge.className = 'badge rounded-pill px-3 py-2 bg-danger';
                } else {
                    statusBadge.className = 'badge rounded-pill px-3 py-2 bg-warning';
                }
                
                var commentsSection = document.getElementById('details_comments_section');
                var commentsContent = document.getElementById('details_comments');
                
                if (comments && comments !== '') {
                    commentsSection.style.display = 'block';
                    commentsContent.textContent = comments;
                } else {
                    commentsSection.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>