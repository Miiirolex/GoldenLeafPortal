<?php
session_start();
require_once 'config.php';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin-dashboard.php');
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Use trim to remove whitespace
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // Add a delay to prevent brute force attacks
        usleep(random_int(100000, 300000)); // 0.1 to 0.3 seconds
        
        // Get admin details from database
        $query = "SELECT * FROM admin_users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            // First check if we need to rehash from plain text to proper bcrypt
            if (strlen($admin['password']) < 40) {
                // This is likely a plain text password - we'll migrate it
                if ($password === $admin['password']) {
                    // Plain text matched, let's update to a proper hash
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE admin_users SET password = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("si", $new_hash, $admin['id']);
                    $update_stmt->execute();
                    
                    // Continue with login success
                    loginSuccess($admin, $conn);
                } else {
                    $error = "Invalid username or password";
                }
            } else {
                // Regular password verification for properly hashed passwords
                if (password_verify($password, $admin['password'])) {
                    // Check if we need to rehash due to algorithm changes
                    if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE admin_users SET password = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->bind_param("si", $new_hash, $admin['id']);
                        $update_stmt->execute();
                    }
                    
                    // Login success
                    loginSuccess($admin, $conn);
                } else {
                    $error = "Invalid username or password";
                }
            }
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Helper function for successful login
function loginSuccess($admin, $conn) {
    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];
    
    // Set session expiration
    $_SESSION['last_activity'] = time();
    
    // Update last login time
    $update_query = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $admin['id']);
    $update_stmt->execute();
    
    // Redirect to dashboard
    header('Location: admin-dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Leave Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #343a40;
            color: #fff;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3>Admin Panel</h3>
                <p class="mb-0">Leave Management System</p>
            </div>
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Enter your username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>