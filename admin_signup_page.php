<?php 
require_once 'connect.php'; 
session_start(); 

// Security Check: Only Admins can access this page
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<?php
// Header Logic (Simplified for Admin context, but keeping structure similar)
$header_to_include = 'header.php'; // Admins always see the main header
$status = '';

// =================================================================
// Form Submission Logic
// =================================================================
if (isset($_POST['sub'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $passwd = isset($_POST['passwd']) ? $_POST['passwd'] : '';
    $cpasswd = isset($_POST['cpasswd']) ? $_POST['cpasswd'] : '';
    
    // CHANGED: Get Role from the form selection
    $role = isset($_POST['role']) ? $_POST['role'] : 'Member';

    if ($passwd !== $cpasswd) {
        $status = "Error: Passwords do not match.";
    } else {
        // Call Stored Procedure
        $stmt = $mysqli->prepare("CALL signup_user(?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $passwd, $role);
            
            if ($stmt->execute()) {
                $status = "Success: New $role account created successfully!";
            } else {
                $error_msg = $stmt->error;
                if (empty($error_msg)) {
                    $error_msg = $mysqli->error;
                }

                if (strpos($error_msg, 'Email already exists') !== false) {
                    $status = "Email is already in use"; 
                } elseif (strpos($error_msg, 'Duplicate entry') !== false) {
                    $status = "Email is already in use";
                } else {
                    $status = "Error: " . $error_msg;
                }
            }
            $stmt->close();
        } else {
            $status = "Database error: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin - Add User</title>
<link rel="stylesheet" href="default.css">
</head>

<body>

<div id="wrapper"> 
    
    <?php include $header_to_include; ?>
    
    <div id="div_main">
        <div id="div_left"></div>
        
        <div id="div_content" class="form">
            
            <?php if ($status !== ''): ?>
                <div class="status" style="margin-bottom:15px; color: <?php echo strpos($status, 'Success') !== false ? 'green' : 'red'; ?>; font-weight:bold;">
                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="admin_signup_page.php" method="post">
                <h2>Create New User</h2>
                
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
                
                <label>Email</label>
                <input type="text" name="email" required placeholder="user@example.com">
                
                <h2>Password</h2>
                
                <label>Password</label>
                <input type="password" name="passwd" required>
                
                <label>Confirm Password</label>
                <input type="password" name="cpasswd" required>
                
                <!-- CHANGED: Added Role Selection Dropdown -->
                <label>Role</label>
                <select name="role">
                    <option value="Member">Member (Regular User)</option>
                    <option value="Librarian">Librarian</option>
                    <option value="Admin">Admin</option>
                </select>

                <div class="center">
                    <input type="submit" name="sub" value="Create User">
                    <input type="reset" value="Clear">
                </div>
            </form>
            
        </div> 
    </div> 
    <div id="div_footer"></div>
</div>

<!-- Pop-up Alert Script -->
<script>
    <?php if ($status !== ''): ?>
        alert(<?php echo json_encode($status); ?>);
    <?php endif; ?>
</script>

</body>
</html>