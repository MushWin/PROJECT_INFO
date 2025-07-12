<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
if (!$isLocalhost) {
    die('This tool is only available from the local machine for security reasons.');
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $message = 'All fields are required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else if (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } else if ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
       
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($update_stmt->execute()) {
                logActivity($conn, $user['id'], 'Password reset by admin tool');
                $message = "Password for user '{$user['username']}' has been reset successfully.";
                $success = true;
            } else {
                $message = 'Error updating password: ' . $conn->error;
            }
            
            $update_stmt->close();
        } else {
            $message = 'No account found with that email address.';
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset Tool</title>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-content">
            <div class="admin-card">
                <h1><i class="fa fa-key"></i> Admin Password Reset Tool</h1>
                <div class="admin-warning">
                    <p><strong>Warning:</strong> This tool is for emergency password resets only. Use with caution.</p>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="<?php echo $success ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="reset_password.php">
                    <div class="form-group">
                        <label for="email">Account Email:</label>
                        <input type="email" name="email" id="email" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        <small>Enter the email address associated with your account</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" id="new_password" required>
                        <small>Minimum 8 characters recommended</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-refresh"></i> Reset Password
                        </button>
                    </div>
                </form>
                
                <div class="admin-back-link">
                    <a href="../login.php"><i class="fa fa-arrow-left"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
