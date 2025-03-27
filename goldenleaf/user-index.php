<?php

session_start();

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect or show login needed message
    header("Location: login.php");
    exit();
}

// Check if user is logged in
requireLogin();

// Get user data
$userData = getUserData($_SESSION['user_id']);
$leaveBalance = getUserLeaveBalance($_SESSION['user_id']);

// Get upcoming pay date
$sql = "SELECT issue_date FROM payslips WHERE user_id = ? ORDER BY issue_date DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$upcomingPayDate = "N/A";
$daysRemaining = 0;

if ($row = mysqli_fetch_assoc($result)) {
    // For demonstration purposes, next pay date is one month after last issue date
    $lastPayDate = new DateTime($row['issue_date']);
    $nextPayDate = clone $lastPayDate;
    $nextPayDate->modify('+1 month');
    
    $upcomingPayDate = $nextPayDate->format('M d, Y');
    
    $today = new DateTime();
    $interval = $today->diff($nextPayDate);
    $daysRemaining = $interval->days;
}

// Get pending leave requests count
$sql = "SELECT COUNT(*) as count FROM leave_applications WHERE user_id = ? AND status = 'pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pendingLeaveCount = 0;

if ($row = mysqli_fetch_assoc($result)) {
    $pendingLeaveCount = $row['count'];
}

// Get recent activities
$sql = "SELECT * FROM activity_log WHERE user_id = ? ORDER BY activity_date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$recentActivities = mysqli_stmt_get_result($stmt);

// Get payslips
$sql = "SELECT pay_period, issue_date, net_pay FROM payslips WHERE user_id = ? ORDER BY issue_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$payslips = mysqli_stmt_get_result($stmt);

// Get leave applications
$sql = "SELECT * FROM leave_applications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$leaveApplications = mysqli_stmt_get_result($stmt);

// Get selected payslip details if requested
$payslipDetails = null;
if (isset($_GET['payslip_id']) && is_numeric($_GET['payslip_id'])) {
    $sql = "SELECT * FROM payslips WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $_GET['payslip_id'], $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $payslipDetails = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --light: #f8fafc;
            --dark: #0f172a;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f1f5f9;
            color: var(--dark);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: var(--dark);
            color: white;
            padding: 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 0.375rem;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary);
        }
        
        .sidebar-menu i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main content styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.75rem;
        }
        
        /* Dashboard styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .dashboard-card h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: var(--secondary);
        }
        
        .dashboard-stat {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-label {
            color: var(--secondary);
            font-size: 0.875rem;
        }
        
        /* Tab styles */
        .tab-container {
            margin-bottom: 2rem;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--secondary);
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Table styles */
        .table-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background-color: #f8fafc;
            font-weight: 600;
            color: var(--secondary);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        /* Form styles */
        .form-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: #e2e8f0;
            color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }
        
        /* Payslip styles */
        .payslip {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .payslip-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .company-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .payslip-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .payslip-section {
            margin-bottom: 1.5rem;
        }
        
        .payslip-section h3 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.payslip-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.payslip-row .label {
    font-weight: 500;
}

.payslip-total {
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.payslip-footer {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    font-size: 0.875rem;
    color: var(--secondary);
}

/* Activity log styles */
.activity-item {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-date {
    font-size: 0.75rem;
    color: var(--secondary);
    margin-bottom: 0.25rem;
}

.activity-description {
    font-size: 0.875rem;
}

/* Responsive styles */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
        padding: 1rem 0.5rem;
    }
    
    .sidebar-header {
        padding: 0.5rem 0;
    }
    
    .sidebar-header h2, .sidebar-menu span {
        display: none;
    }
    
    .sidebar-menu a {
        padding: 0.75rem;
        text-align: center;
    }
    
    .sidebar-menu i {
        margin-right: 0;
        width: auto;
    }
    
    .main-content {
        margin-left: 70px;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
        gap: 1.5rem;
    }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>HR Portal</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="user-index.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a></li>
                <li><a href="leave.php"><i class="fas fa-calendar-alt"></i> <span>Leave Applications</span></a></li>
                <li><a href="payslips.php"><i class="fas fa-file-invoice-dollar"></i> <span>Payslips</span></a></li>
                <li><a href="benefits.php"><i class="fas fa-gift"></i> <span>Benefits</span></a></li>
                <li><a href="documents.php"><i class="fas fa-folder"></i> <span>Documents</span></a></li>
                <li><a href="training.php"><i class="fas fa-graduation-cap"></i> <span>Training</span></a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Staff Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($userData['first_name'], 0, 1); ?>
                    </div>
                    <div>
                        <div><?php echo $userData['first_name'] . ' ' . $userData['last_name']; ?></div>
                        <div class="dashboard-label"><?php echo $userData['position']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Upcoming Pay</h3>
                    <div class="dashboard-stat"><?php echo $upcomingPayDate; ?></div>
                    <div class="dashboard-label"><?php echo $daysRemaining; ?> days remaining</div>
                </div>
                <div class="dashboard-card">
                    <h3>Leave Balance</h3>
                    <div class="dashboard-stat"><?php echo $leaveBalance; ?> days</div>
                    <div class="dashboard-label"><?php echo $pendingLeaveCount; ?> pending requests</div>
                </div>
                <div class="dashboard-card">
                    <h3>Quick Actions</h3>
                    <div style="margin-top: 0.5rem;">
                        <a href="leave.php?action=apply" class="btn btn-primary" style="margin-right: 0.5rem;">Apply for Leave</a>
                        <a href="payslips.php" class="btn btn-secondary">View Payslips</a>
                    </div>
                </div>
            </div>
            
            <!-- Tab Container -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="activities">Recent Activities</button>
                    <button class="tab-button" data-tab="payslips">Payslips</button>
                    <button class="tab-button" data-tab="leave">Leave Applications</button>
                </div>
                
                <!-- Activities Tab -->
                <div class="tab-content active" id="activities-tab">
                    <div class="table-container">
                        <?php if (mysqli_num_rows($recentActivities) > 0): ?>
                            <div>
                                <?php while ($activity = mysqli_fetch_assoc($recentActivities)): ?>
                                    <div class="activity-item">
                                        <div class="activity-date"><?php echo date('M d, Y h:i A', strtotime($activity['activity_date'])); ?></div>
                                        <div class="activity-description"><?php echo $activity['description']; ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="padding: 1rem;">No recent activities found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payslips Tab -->
                <div class="tab-content" id="payslips-tab">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pay Period</th>
                                    <th>Issue Date</th>
                                    <th>Net Pay</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($payslips) > 0): ?>
                                    <?php while ($payslip = mysqli_fetch_assoc($payslips)): ?>
                                        <tr>
                                            <td><?php echo $payslip['pay_period']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($payslip['issue_date'])); ?></td>
                                            <td>$<?php echo number_format($payslip['net_pay'], 2); ?></td>
                                            <td>
                                                <a href="?payslip_id=<?php echo $payslip['id']; ?>" class="btn btn-secondary">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No payslips found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($payslipDetails): ?>
                        <div class="payslip">
                            <div class="payslip-header">
                                <div class="company-info">
                                    <h2>YourCompany Inc.</h2>
                                    <p>123 Business Street, Suite 100</p>
                                    <p>Business City, State 12345</p>
                                </div>
                                <div>
                                    <p><strong>Pay Date:</strong> <?php echo date('M d, Y', strtotime($payslipDetails['issue_date'])); ?></p>
                                    <p><strong>Pay Period:</strong> <?php echo $payslipDetails['pay_period']; ?></p>
                                </div>
                            </div>
                            
                            <div class="payslip-title">Payslip</div>
                            
                            <div class="payslip-section">
                                <h3>Employee Information</h3>
                                <div class="payslip-row">
                                    <div class="label">Name:</div>
                                    <div><?php echo $userData['first_name'] . ' ' . $userData['last_name']; ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Employee ID:</div>
                                    <div><?php echo $userData['employee_id']; ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Position:</div>
                                    <div><?php echo $userData['position']; ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Department:</div>
                                    <div><?php echo $userData['department']; ?></div>
                                </div>
                            </div>
                            
                            <div class="payslip-section">
                                <h3>Earnings</h3>
                                <div class="payslip-row">
                                    <div class="label">Basic Salary:</div>
                                    <div>$<?php echo number_format($payslipDetails['basic_salary'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Allowances:</div>
                                    <div>$<?php echo number_format($payslipDetails['allowances'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Overtime:</div>
                                    <div>$<?php echo number_format($payslipDetails['overtime'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Bonus:</div>
                                    <div>$<?php echo number_format($payslipDetails['bonus'], 2); ?></div>
                                </div>
                                <div class="payslip-row payslip-total">
                                    <div class="label">Gross Pay:</div>
                                    <div>$<?php echo number_format($payslipDetails['gross_pay'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div class="payslip-section">
                                <h3>Deductions</h3>
                                <div class="payslip-row">
                                    <div class="label">Tax:</div>
                                    <div>$<?php echo number_format($payslipDetails['tax'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Insurance:</div>
                                    <div>$<?php echo number_format($payslipDetails['insurance'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Retirement:</div>
                                    <div>$<?php echo number_format($payslipDetails['retirement'], 2); ?></div>
                                </div>
                                <div class="payslip-row">
                                    <div class="label">Other:</div>
                                    <div>$<?php echo number_format($payslipDetails['other_deductions'], 2); ?></div>
                                </div>
                                <div class="payslip-row payslip-total">
                                    <div class="label">Total Deductions:</div>
                                    <div>$<?php echo number_format($payslipDetails['total_deductions'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div class="payslip-section">
                                <div class="payslip-row payslip-total">
                                    <div class="label">Net Pay:</div>
                                    <div>$<?php echo number_format($payslipDetails['net_pay'], 2); ?></div>
                                </div>
                            </div>
                            
                            <div class="payslip-footer">
                                <p>This is a computer-generated document and does not require a signature.</p>
                                <p>For any queries, please contact the HR department.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Leave Applications Tab -->
                <div class="tab-content" id="leave-tab">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($leaveApplications) > 0): ?>
                                    <?php while ($leave = mysqli_fetch_assoc($leaveApplications)): ?>
                                        <tr>
                                            <td><?php echo $leave['leave_type']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                            <td><?php echo $leave['total_days']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($leave['status']); ?>">
                                                    <?php echo ucfirst($leave['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $leave['admin_comments']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No leave applications found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to current tab
                    this.classList.add('active');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });
    </script>
</body>
</html>	