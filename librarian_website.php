<?php
require_once 'connect.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'librarian') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = ''; 

// =================================================================
// HANDLE ACTIONS
// =================================================================

// A. Remove Book
if (isset($_POST['delete_book_btn'])) {
    $target_book_id = (int)$_POST['book_id'];
    $del_q = "DELETE FROM book WHERE book_id = ?";
    if ($stmt = $mysqli->prepare($del_q)) {
        $stmt->bind_param("i", $target_book_id);
        if ($stmt->execute()) { $message = "Book title deleted."; $msg_type = 'success'; }
        else {
            if ($mysqli->errno == 1451) $message = "Cannot delete: Remove all copies first.";
            else $message = "Error: " . $mysqli->error;
            $msg_type = 'error';
        }
        $stmt->close();
    }
}

// B. Remove Copy
if (isset($_POST['remove_copy_btn'])) {
    $target_copy_id = (int)$_POST['copy_id'];
    $reason = 'Retired'; 
    if ($proc = $mysqli->prepare("CALL remove_copy(?, ?, ?)")) {
        $proc->bind_param("iis", $target_copy_id, $user_id, $reason);
        if ($proc->execute()) { $message = "Copy #$target_copy_id retired."; $msg_type = 'success'; }
        else { $message = "Error: " . $proc->error; $msg_type = 'error'; }
        $proc->close();
    }
}

// C. Return Book
if (isset($_POST['return_btn'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $condition = isset($_POST['condition']) ? $_POST['condition'] : 'Good'; 
    if ($proc = $mysqli->prepare("CALL return_book(?, ?, ?)")) {
        $proc->bind_param("iis", $borrowing_id, $user_id, $condition);
        if ($proc->execute()) { $message = "Book returned as '$condition'."; $msg_type = 'success'; }
        else { $message = "Error: " . $proc->error; $msg_type = 'error'; }
        $proc->close();
    }
}

// D. PLACE COPY
if (isset($_POST['place_copy_btn'])) {
    $copy_id = (int)$_POST['copy_id'];
    $shelf = trim($_POST['shelf']);
    
    if (!empty($shelf)) {
        if ($proc = $mysqli->prepare("CALL place_copy(?, ?, ?)")) {
            $proc->bind_param("iis", $copy_id, $user_id, $shelf);
            if ($proc->execute()) {
                $message = "Copy #$copy_id placed on shelf '$shelf'.";
                $msg_type = 'success';
            } else {
                $message = "Error placing copy: " . $proc->error;
                $msg_type = 'error';
            }
            $proc->close();
        }
    } else {
        $message = "Please enter a shelf location.";
        $msg_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Librarian Dashboard</title>
<link rel="stylesheet" href="default.css">
<style>
    .book-management { font-family: 'Segoe UI', sans-serif; color: #333; max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section-title { color: #2c3e50; font-size: 24px; border-left: 5px solid #4CAF50; padding-left: 15px; margin: 0; }
    .books-table { width: 100%; border-collapse: collapse; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: white; margin-bottom: 20px; }
    .books-table th { background: #4CAF50; color: white; text-align: left; padding: 12px; }
    .books-table td { padding: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    
    /* Buttons */
    .btn-delete { background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-manage { background: #2196F3; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px; text-decoration:none; display:inline-block;}
    .btn-return { background: #673AB7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-add { background: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 25px; font-weight: bold; font-size: 14px; }
    .btn-place { background: #009688; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }
    
    .condition-select { padding: 5px; border-radius: 4px; border: 1px solid #ccc; margin-right: 8px; font-size: 12px; }
    .shelf-input { padding: 5px; border-radius: 4px; border: 1px solid #ccc; width: 80px; font-size: 11px; margin-right: 5px; }

    /* Badges */
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .badge-info { background: #e3f2fd; color: #1565c0; }
    .badge-avail { background: #e8f5e9; color: #2e7d32; }
    .badge-retired { background: #ffebee; color: #c62828; }
    .badge-due { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }

    .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
    .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }

    .copy-manager { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
</style>
</head>

<body>

<div id="wrapper"> 
    <?php include 'librarian_header.php'; ?>
    <div id="div_main">
        <div id="div_left"></div>
        
        <div id="div_content" class="book-management">
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 1: ACTIVE LOANS -->
            <h2 class="section-title" style="margin-bottom: 15px;">Active Loans</h2>
            <table class="books-table">
                <thead>
                    <tr><th>Title</th><th>Copy ID</th><th>Copy Code</th><th>Borrower</th><th>Due</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php
                    $loan_q = "SELECT bo.borrowing_id, b.title, c.copy_id, c.copy_code, u.name, bo.due_date FROM borrowing bo JOIN copy c ON bo.copy_id = c.copy_id JOIN book b ON c.book_id = b.book_id JOIN user u ON bo.customer_id = u.user_id WHERE bo.status = 'Active' ORDER BY bo.due_date ASC";
                    $loan_res = $mysqli->query($loan_q);
                    if ($loan_res && $loan_res->num_rows > 0) {
                        while ($loan = $loan_res->fetch_assoc()) {
                            echo "<tr><td>{$loan['title']}</td><td>#{$loan['copy_id']}</td><td style='font-family:monospace;color:#666;'>{$loan['copy_code']}</td><td>{$loan['name']}</td><td><span class='badge badge-due'>{$loan['due_date']}</span></td><td><form method='POST' style='display:flex;align-items:center;'><input type='hidden' name='borrowing_id' value='{$loan['borrowing_id']}'><select name='condition' class='condition-select'><option value='Good'>Good</option><option value='Damaged'>Damaged</option></select><button type='submit' name='return_btn' class='btn-return'>Return</button></form></td></tr>";
                        }
                    } else { echo "<tr><td colspan='6' style='text-align:center;color:#777;'>No active loans.</td></tr>"; }
                    ?>
                </tbody>
            </table>

            <!-- SECTION: COPY MANAGEMENT -->
            <?php 
            if (isset($_GET['manage_id'])) {
                $manage_book_id = (int)$_GET['manage_id'];
                $title_res = $mysqli->query("SELECT title FROM book WHERE book_id = $manage_book_id");
                if ($title_res && $t_row = $title_res->fetch_assoc()) {
                    $book_title = $t_row['title'];
            ?>
            <div class="copy-manager" id="copySection">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h3 style="margin:0;">Managing: <em><?= htmlspecialchars($book_title) ?></em></h3>
                    <a href="librarian_website.php" style="color:#666; text-decoration:none;">✖ Close</a>
                </div>
                <table class="books-table" style="margin-bottom:0;">
                    <thead><tr style="background:#607d8b;"><th>ID</th><th>Code</th><th>Status</th><th>Location</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $copy_q = "SELECT c.copy_id, c.copy_code, c.status, 
                                   (SELECT shelf FROM placement p WHERE p.copy_id = c.copy_id ORDER BY place_at DESC LIMIT 1) as shelf
                                   FROM copy c WHERE c.book_id = $manage_book_id ORDER BY c.copy_id ASC";
                        $copy_res = $mysqli->query($copy_q);
                        if ($copy_res) {
                            while ($copy = $copy_res->fetch_assoc()) {
                                $status = $copy['status'];
                                $badge_class = ($status == 'Available') ? 'badge-avail' : (($status == 'Retired') ? 'badge-retired' : 'badge-info');
                                $current_shelf = $copy['shelf'] ? $copy['shelf'] : '<em style="color:#999">Not Placed</em>';
                                
                                echo "<tr>
                                        <td>#{$copy['copy_id']}</td>
                                        <td style='font-family:monospace;'>{$copy['copy_code']}</td>
                                        <td><span class='badge $badge_class'>$status</span></td>
                                        <td>$current_shelf</td>
                                        <td style='display:flex; align-items:center;'>";
                                        
                                if ($status != 'Retired' && $status != 'Lost') {
                                    echo "<form method='POST' action='librarian_website.php?manage_id=$manage_book_id' style='margin-right:10px;'>
                                            <input type='hidden' name='copy_id' value='{$copy['copy_id']}'>
                                            <input type='text' name='shelf' class='shelf-input' placeholder='Shelf...'>
                                            <button type='submit' name='place_copy_btn' class='btn-place'>Place</button>
                                          </form>";
                                    echo "<form method='POST' action='librarian_website.php?manage_id=$manage_book_id'>
                                            <input type='hidden' name='copy_id' value='{$copy['copy_id']}'>
                                            <button type='submit' name='remove_copy_btn' class='btn-delete' onclick=\"return confirm('Retire this copy?')\">X</button>
                                          </form>";
                                } else { echo "<span style='color:#999;font-size:11px;'>Archived</span>"; }
                                echo "</td></tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php } } ?>

            <!-- SECTION: STOCK MANAGEMENT -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; margin-bottom: 20px;">
                <h2 class="section-title">Copy Management</h2>
                <a href="new_book.php" class="btn-add">+ Add New Book</a>
            </div>
            
            <div class="table-view">
                <table class="books-table">
                    <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Stock</th><th style="width:200px;">Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $q = "SELECT b.book_id, b.title, b.author, b.isbn, (SELECT COUNT(*) FROM copy c WHERE c.book_id = b.book_id) as total, (SELECT COUNT(*) FROM copy c WHERE c.book_id = b.book_id AND c.status = 'Available') as avail FROM book b ORDER BY b.title ASC";
                        $result = $mysqli->query($q);
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['title']}</td><td>{$row['author']}</td><td>{$row['isbn']}</td>
                                        <td><span class='badge badge-info'>Tot: {$row['total']}</span> <span class='badge badge-avail'>Avl: {$row['avail']}</span></td>
                                        <td>
                                            <!-- REMOVED: + Stock Button -->
                                            <a href='librarian_website.php?manage_id={$row['book_id']}#copySection' class='btn-manage'>Mng Copies</a>
                                            <form method='POST' style='display:inline;'><input type='hidden' name='book_id' value='{$row['book_id']}'><button type='submit' name='delete_book_btn' class='btn-delete' onclick=\"return confirm('Delete Book?');\">Del</button></form>
                                        </td>
                                      </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div> 
    </div> 
    <div id="div_footer"></div>
</div>

</body>
</html>