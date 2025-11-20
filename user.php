<?php
require_once 'connect.php';
session_start();

// Access Control: Admin Only
if (!isset($_SESSION['logged_in']) || (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'admin')) {
    // Redirect non-admins
    header("Location: login.php");
    exit();
}

$statusMessage = '';

// Handle Deletion using Stored Procedure 'delete_user'
if (isset($_GET['del_id'])) {
    $del_id = (int)$_GET['del_id'];
    $admin_id = $_SESSION['user_id'];
    
    // CALL delete_user(target_id, admin_id)
    $stmt = $mysqli->prepare("CALL delete_user(?, ?)");
    $stmt->bind_param("ii", $del_id, $admin_id);
    
    if ($stmt->execute()) {
        $statusMessage = "User deleted successfully.";
    } else {
        $statusMessage = "Delete failed: " . $mysqli->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>User Management</title>
<link rel="stylesheet" href="default.css">
</head>

<body>
<div id="wrapper"> 
    <?php include 'header.php'; ?>
    <div id="div_main">
        <div id="div_left"></div>
        <div id="div_content" class="usergroup">
            <?php if ($statusMessage !== ''): ?>
                <div class="status"><?= htmlspecialchars($statusMessage); ?></div>
            <?php endif; ?>
            
            <h2>User Management</h2>
            <table>
                <col width="5%">
                <col width="20%">
                <col width="25%">
                <col width="30%"> <!-- Width for Password -->
                <col width="10%">
                <col width="10%">

                <tr>
                    <th>ID</th> 
                    <th>Name</th>
                    <th>Email (Decrypted)</th>
                    <th>Password (Hashed)</th> <!-- Added Column -->
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php 
                    // Select query specific to project.sql structure
                    // Decrypts email using the key 'my_secret_key'
                    // Added 'password' to the SELECT list
                    $q = "SELECT user_id, name, AES_DECRYPT(email, 'my_secret_key') as real_email, password, role FROM user ORDER BY user_id";
                    $result = $mysqli->query($q);
                    
                    if($result) {
                        while($row = $result->fetch_assoc()){ ?>
                        <tr>
                            <td><?= htmlspecialchars($row['user_id']); ?></td> 
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['real_email']); ?></td>
                            <!-- Added Password Data Cell -->
                            <td style="word-break: break-all; font-size: 12px;"><?= htmlspecialchars($row['password']); ?></td>
                            <td><?= htmlspecialchars($row['role']); ?></td>
                            <td>
                                <a href="user.php?del_id=<?=$row['user_id']?>" onclick="return confirm('Are you sure? This cannot be undone.')"> 
                                    <img src="images/Delete.png" width="24" height="24" alt="Delete">
                                </a>
                            </td>
                        </tr>                               
                <?php 
                        }
                        $result->free();
                    } else {
                        echo "<tr><td colspan='6'>Error: " . $mysqli->error . "</td></tr>";
                    }
                ?>
            </table>
            
            <p>
                <?php 
                $countResult = $mysqli->query("SELECT COUNT(*) AS total FROM user");
                $row = $countResult->fetch_assoc();
                echo "Total " . $row['total'] . " user(s)";
                ?>
            </p>
        </div> 
    </div> 
    <div id="div_footer"></div>
</div>
</body>
</html>