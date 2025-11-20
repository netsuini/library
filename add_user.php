<?php 
require_once 'connect.php'; 

// NEW: Start session to access login details
session_start(); 
?>

<?php
// =================================================================
// Header Logic
// =================================================================
$show_user_header = false;

// Condition 1: User came from login.php (Signup flow)
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'login.php') !== false) {
    $show_user_header = true;
}

// Condition 2: User is logged in as a regular Member/User
if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'member' || $_SESSION['user_type'] === 'user')) {
    $show_user_header = true;
}

$status = '';

// =================================================================
// Form Submission Logic
// =================================================================
if (isset($_POST['sub'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $passwd = isset($_POST['passwd']) ? $_POST['passwd'] : '';
    $cpasswd = isset($_POST['cpasswd']) ? $_POST['cpasswd'] : '';
    
    // CHANGED: Role is strictly forced to 'Member'
    // The dropdown has been removed from the form below
    $role = 'Member';

    if ($passwd !== $cpasswd) {
        $status = "Error: Passwords do not match.";
    } else {
        $stmt = $mysqli->prepare("CALL signup_user(?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $passwd, $role);
            
            if ($stmt->execute()) {
                $status = "Success: User account created successfully!";
            } else {
                $error_msg = $stmt->error;
                if (empty($error_msg)) {
                    $error_msg = $mysqli->error;
                }

                if (strpos($error_msg, 'Email already exists') !== false) {
                    $status = "Email is already use"; 
                } elseif (strpos($error_msg, 'Duplicate entry') !== false) {
                    $status = "Email is already use";
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
<title>Add User / Signup</title>
<link rel="stylesheet" href="default.css">
</head>

<body>

<div id="wrapper"> 
    
    <?php 
    // If $show_user_header is true (Signup flow OR Logged in as User), show user_header.php
    if ($show_user_header) {
        include 'user_header.php'; 
    } else {
        include 'header.php';      
    }
    ?>
    
    <div id="div_main">
        <div id="div_left"></div>
        
        <div id="div_content" class="form">
            
            <?php if ($status !== ''): ?>
                <div class="status" style="margin-bottom:15px; color: <?php echo strpos($status, 'Success') !== false ? 'green' : 'red'; ?>; font-weight:bold;">
                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="add_user.php" method="post">
                <h2>User Profile</h2>
                
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
                
                <label>Email</label>
                <input type="text" name="email" required placeholder="user@example.com">
                
                <h2>Account Credentials</h2>
                
                <label>Password</label>
                <input type="password" name="passwd" required>
                
                <label>Confirm Password</label>
                <input type="password" name="cpasswd" required>
                
                <!-- CHANGED: Removed Role Selection. All signups are Members. -->

                <div class="center">
                    <input type="submit" name="sub" value="Submit">
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