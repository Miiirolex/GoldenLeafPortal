<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is logged in
requireLogin();

// Get user data
$userData = getUserData($_SESSION['user_id']);

// Handle different profile update scenarios
$updateMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Determine which form was submitted
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Personal Information Update
            case 'update_personal_info':
                $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
                $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                $address = mysqli_real_escape_string($conn, $_POST['address']);

                $sql = "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        address = ? 
                        WHERE id = ?";
                
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssssi", 
                    $firstName, $lastName, $email, $phone, $address, $_SESSION['user_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    $updateMessage = "Personal information updated successfully!";
                } else {
                    $errorMessage = "Error updating personal information: " . mysqli_error($conn);
                }
                break;

            // Username Update
            case 'update_username':
                $newUsername = mysqli_real_escape_string($conn, $_POST['new_username']);
                
                // Check if username already exists
                $checkSql = "SELECT id FROM users WHERE username = ?";
                $checkStmt = mysqli_prepare($conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "s", $newUsername);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);

                if (mysqli_num_rows($checkResult) > 0) {
                    $errorMessage = "Username already exists. Please choose another.";
                } else {
                    $sql = "UPDATE users SET username = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $newUsername, $_SESSION['user_id']);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $updateMessage = "Username updated successfully!";
                    } else {
                        $errorMessage = "Error updating username: " . mysqli_error($conn);
                    }
                }
                break;

            // Password Update
            case 'update_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];

                // Verify current password
                $sql = "SELECT password FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);

                if (!password_verify($currentPassword, $user['password'])) {
                    $errorMessage = "Current password is incorrect.";
                } elseif ($newPassword !== $confirmPassword) {
                    $errorMessage = "New passwords do not match.";
                } elseif (strlen($newPassword) < 8) {
                    $errorMessage = "New password must be at least 8 characters long.";
                } else {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $updateSql = "UPDATE users SET password = ? WHERE id = ?";
                    $updateStmt = mysqli_prepare($conn, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $_SESSION['user_id']);
                    
                    if (mysqli_stmt_execute($updateStmt)) {
                        $updateMessage = "Password updated successfully!";
                    } else {
                        $errorMessage = "Error updating password: " . mysqli_error($conn);
                    }
                }
                break;

            // Profile Picture Update
            case 'update_profile_picture':
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $maxFileSize = 5 * 1024 * 1024; // 5MB

                    if (in_array($_FILES['profile_picture']['type'], $allowedTypes) && 
                        $_FILES['profile_picture']['size'] <= $maxFileSize) {
                        
                        // Create uploads directory if it doesn't exist
                        $uploadDir = 'uploads/profile_pictures/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        // Generate unique filename
                        $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                        $uploadPath = $uploadDir . $filename;

                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                            // Update profile picture in database
                            $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "si", $uploadPath, $_SESSION['user_id']);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $updateMessage = "Profile picture updated successfully!";
                            } else {
                                $errorMessage = "Error saving profile picture to database.";
                            }
                        } else {
                            $errorMessage = "Error uploading profile picture.";
                        }
                    } else {
                        $errorMessage = "Invalid file type or size. Max 5MB, allowed types: JPEG, PNG, GIF.";
                    }
                }
                break;
        }

        // Refresh user data after update
        $userData = getUserData($_SESSION['user_id']);
    }
}

// Get employment details
$employmentDetailsSql = "SELECT * FROM employment_details WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $employmentDetailsSql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employmentDetails = mysqli_fetch_assoc($result);

// If no employment details found, set default values
if (!$employmentDetails) {
    $employmentDetails = [
        'employee_id' => 'N/A',
        'job_title' => 'N/A',
        'department' => 'N/A',
        'employment_type' => 'N/A',
        'hire_date' => date('Y-m-d')
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Staff Portal</title>
	    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            
        /* Additional profile-specific styles */
        .profile-picture-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
    </style>
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
            <header class="header">
                <h1>My Profile</h1>
				<div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($userData['first_name'], 0, 1); ?>
                    </div>
                    <div>
                        <div><?php echo $userData['first_name'] . ' ' . $userData['last_name']; ?></div>
                        <div class="dashboard-label"><?php echo $userData['position']; ?></div>
                    </div>
                </div>
            </header>

            <!-- Alert Messages -->
            <?php if (!empty($updateMessage)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($updateMessage); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <!-- Profile Tabs -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="personal">Personal Info</button>
                    <button class="tab-button" data-tab="account">Account Settings</button>
                    <button class="tab-button" data-tab="security">Security</button>
                </div>

                <!-- Personal Information Tab -->
                <div class="tab-content active" id="personal">
                    <div class="form-card">
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_personal_info">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($userData['phone']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" class="form-control" 
                                       value="<?php echo htmlspecialchars($userData['address']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Personal Info</button>
                        </form>
                    </div>
                </div>

                <!-- Account Settings Tab -->
                <div class="tab-content" id="account">
                    <div class="form-card">
                        <!-- Username Update -->
                        <form method="POST" action="profile.php" class="mb-4">
                            <input type="hidden" name="action" value="update_username">
                            <div class="form-group">
                                <label>Current Username</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>New Username</label>
                                <input type="text" name="new_username" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Username</button>
                        </form>

                        <!-- Profile Picture Update -->
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile_picture">
                            <div class="form-group">
                                <label>Profile Picture</label>
                                <?php if (!empty($userData['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                                         alt="Profile Picture" class="profile-picture-preview mb-3">
                                <?php endif; ?>
                                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile Picture</button>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-content" id="security">
                    <div class="form-card">
                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" 
                                       minlength="8" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" 
                                       minlength="8" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons and tabs
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

                // Add active class to clicked button and corresponding tab
                button.classList.add('active');
                document.getElementById(button.dataset.tab).classList.add('active');
            });
        });
    </script>
</body>
</html>