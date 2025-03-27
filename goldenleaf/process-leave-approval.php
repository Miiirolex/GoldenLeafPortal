<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$response = ['success' => false, 'message' => ''];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $leave_id = filter_input(INPUT_POST, 'leave_id', FILTER_VALIDATE_INT);
    $comments = filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_STRING);
    
    // Validate inputs
    if (!$leave_id) {
        $_SESSION['error'] = "Invalid leave application ID";
        header('Location: admin-dashboard.php');
        exit();
    }
    
    // Get leave application details
    $leave_query = "SELECT la.*, u.first_name, u.last_name, u.email, u.annual_leave_balance 
                    FROM leave_applications la
                    JOIN users u ON la.user_id = u.id
                    WHERE la.id = ?";
    $stmt = $conn->prepare($leave_query);
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $_SESSION['error'] = "Leave application not found";
        header('Location: admin-dashboard.php');
        exit();
    }
    
    $leave_application = $result->fetch_assoc();
    
    // Check if the leave application is already processed
    if ($leave_application['status'] !== 'pending') {
        $_SESSION['error'] = "This leave application has already been processed";
        header('Location: admin-dashboard.php');
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Update leave application status
        $update_query = "UPDATE leave_applications 
                         SET status = ?, admin_comments = ?, reviewed_by = ?, reviewed_at = NOW() 
                         WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssis", $new_status, $comments, $admin_id, $leave_id);
        $stmt->execute();
        
        // Record in leave history
        $history_query = "INSERT INTO leave_history 
                          (leave_application_id, status_from, status_to, changed_by, comments) 
                          VALUES (?, 'pending', ?, ?, ?)";
        $stmt = $conn->prepare($history_query);
        $stmt->bind_param("isis", $leave_id, $new_status, $admin_id, $comments);
        $stmt->execute();
        
        // If leave is rejected and it's annual leave, restore the balance
        if ($action === 'reject' && $leave_application['leave_type'] === 'annual') {
            $restore_balance_query = "UPDATE users 
                                      SET annual_leave_balance = annual_leave_balance + ? 
                                      WHERE id = ?";
            $stmt = $conn->prepare($restore_balance_query);
            $stmt->bind_param("di", $leave_application['total_days'], $leave_application['user_id']);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Send email notification
        $employee_name = $leave_application['first_name'] . ' ' . $leave_application['last_name'];
        $employee_email = $leave_application['email'];
        $leave_type = ucfirst($leave_application['leave_type']);
        $start_date = date('M d, Y', strtotime($leave_application['start_date']));
        $end_date = date('M d, Y', strtotime($leave_application['end_date']));
        
        $subject = "Leave Application " . ucfirst($new_status);
        $message = "Dear $employee_name,\n\n";
        $message .= "Your $leave_type leave application from $start_date to $end_date has been $new_status.\n\n";
        
        if (!empty($comments)) {
            $message .= "Comments: $comments\n\n";
        }
        
        $message .= "If you have any questions, please contact HR.\n\n";
        $message .= "Regards,\nHR Department";
        
        $headers = "From: hr@company.com";
        
        // Send email (uncomment in production)
        // mail($employee_email, $subject, $message, $headers);
        
        $_SESSION['success'] = "Leave application has been " . ucfirst($new_status) . " successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    }
    
    // Redirect back to dashboard
    header('Location: admin-dashboard.php');
    exit();
} else {
    // Invalid request method
    header('Location: admin-dashboard.php');
    exit();
}
?>