<?php 
require_once 'connect.php';
session_start();

$error = '';

// Check if form was submitted
if (isset($_POST['sub']) || $_SERVER["REQUEST_METHOD"] == "POST") {
    // Support both 'username' (from your form) and 'email' 
    $email = isset($_POST['username']) ? trim($_POST['username']) : '';
    $passwd = isset($_POST['passwd']) ? $_POST['passwd'] : '';

    // Use Stored Procedure from project.sql
    if ($stmt = $mysqli->prepare("CALL login_user(?, ?)")) {
        $stmt->bind_param("ss", $email, $passwd);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            // Login Successful
            $_SESSION['user_id'] = $row['user_id']; 
            $_SESSION['username'] = $row['name'];
            // Normalize role to lowercase for consistent checking
            $_SESSION['user_type'] = strtolower($row['role']); // 'member','librarian','admin'
            $_SESSION['logged_in'] = true;
            
            // =========================================================
            // REDIRECT LOGIC BASED ON ROLE
            // =========================================================
            if ($_SESSION['user_type'] === 'admin') {
                header("Location: admin_website.php");
            } elseif ($_SESSION['user_type'] === 'librarian') {
                header("Location: librarian_website.php");
            } elseif ($_SESSION['user_type'] === 'member' || $_SESSION['user_type'] === 'user') {
                header("Location: user_website.php");
            } else {
                // Fallback if role is unrecognized
                header("Location: user_website.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $mysqli->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 20px 40px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; color: #555; }
        .input-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; margin-top:5px; background-color:pink; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: red; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>
    
    <?php if (!empty($error)) { echo '<p class="error">' . htmlspecialchars($error) . '</p>'; } ?>

    <form action="login.php" method="post">
        <div class="input-group">
            <label for="username">Email</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="input-group">
            <label for="passwd">Password</label>
            <input type="password" id="passwd" name="passwd" required>
        </div>
        <button type="submit" name="sub" class="btn">Login</button>
        <!-- Signup button redirects to add_user.php (Member Signup) -->
        <button type="button" class="btn" style="background-color: #6c757d;" onclick="window.location.href='add_user.php?show_user_header=1'">Signup</button>
    </form>
</div>

</body>
</html>