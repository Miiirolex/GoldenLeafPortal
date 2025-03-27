<?php
require_once 'config.php';

// Get all users
$sql = "SELECT id, password FROM users";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $plain_password = $row['password'];
        
        // Hash the password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        
        // Update the user record
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo "Updated password for user ID: " . $id . "<br>";
    }
} else {
    echo "No users found";
}

mysqli_close($conn);
echo "Password update complete";
?>