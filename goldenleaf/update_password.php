<?php
require_once 'config.php';

// Replace 'password123' with the actual password
$plain_password = 'password123';
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Update the admin user's password
$query = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hashed_password);
$result = $stmt->execute();

if ($result) {
    echo "Password updated successfully. New hash: " . $hashed_password;
} else {
    echo "Failed to update password: " . $conn->error;
}
?>