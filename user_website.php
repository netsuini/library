<?php
require_once 'connect.php';
session_start();

// 1. Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Session Info
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
$user_id  = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// 3. Header Logic
$header_to_include = 'header.php';
if ($userType === 'librarian') {
    $header_to_include = 'librarian_header.php';
} else if ($userType === 'user' || $userType === 'regular' || $userType === 'member') {
    $header_to_include = 'user_header.php';
}

// 4. Handle "Borrow Book" Action
$message = '';
$msg_type = ''; 

if (isset($_POST['borrow_btn']) && $user_id > 0) {
    $target_book_id = (int)$_POST['book_id'];

    // Step A: Find an 'Available' copy
    $find_copy_q = "SELECT copy_id FROM copy WHERE book_id = ? AND status = 'Available' LIMIT 1";
    
    if ($stmt = $mysqli->prepare($find_copy_q)) {
        $stmt->bind_param("i", $target_book_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $copy_id = $row['copy_id'];
            $stmt->close();

            // Step B: Borrow the copy
            if ($proc = $mysqli->prepare("CALL borrow_book(?, ?)")) {
                $proc->bind_param("ii", $copy_id, $user_id);
                if ($proc->execute()) {
                    $message = "Success! You have borrowed the book.";
                    $msg_type = 'success';
                } else {
                    $message = "Error: " . $proc->error;
                    $msg_type = 'error';
                }
                $proc->close();
            } else {
                $message = "Database Procedure Error: " . $mysqli->error;
                $msg_type = 'error';
            }

        } else {
            $message = "Sorry, all copies of this book are currently borrowed.";
            $msg_type = 'error';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>My Library</title>
<link rel="stylesheet" href="default.css">
<style>
    /* Modern Styles */
    .book-management {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .section-title {
        color: #2c3e50;
        font-size: 24px;
        border-left: 5px solid #4CAF50;
        padding-left: 15px;
        margin: 0; 
    }

    .books-table {
        width: 100%;
        border-collapse: collapse;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
        background: white;
        margin-bottom: 20px;
    }
    
    .books-table th {
        background: #4CAF50;
        color: white;
        text-align: left;
        padding: 12px 15px;
        font-weight: 600;
    }
    
    .books-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .btn-borrow {
        background: #2196F3;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 13px;
    }
    .btn-borrow:hover { background: #1976D2; }
    
    .btn-disabled {
        background: #ccc;
        color: #666;
        cursor: not-allowed;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 13px;
    }

    .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .badge-available { background: #e8f5e9; color: #2e7d32; }
    .badge-out { background: #ffebee; color: #c62828; }
    .badge-active { background: #e3f2fd; color: #1565c0; }

    .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
    .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }

</style>
</head>

<body>

<div id="wrapper"> 
    <?php include $header_to_include; ?>
    <div id="div_main">
        <div id="div_left"></div>
        
        <div id="div_content" class="book-management">
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $msg_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 1: My Borrowed Books -->
            <div class="header-section" style="margin-bottom: 20px;">
                <h2 class="section-title">My Borrowed Books</h2>
            </div>

            <table class="books-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Borrowed Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $my_books_q = "SELECT b.title, b.author, bo.borrow_date, bo.due_date, bo.status 
                                    FROM borrowing bo
                                    JOIN copy c ON bo.copy_id = c.copy_id
                                    JOIN book b ON c.book_id = b.book_id
                                    WHERE bo.customer_id = ? AND bo.status = 'Active'
                                    ORDER BY bo.due_date ASC";
                    
                    $stmt = $mysqli->prepare($my_books_q);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['borrow_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                            echo "<td><span class='badge badge-active'>" . htmlspecialchars($row['status']) . "</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; color:#777;'>You haven't borrowed any books yet. Check the catalog below!</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>

            <!-- SECTION 2: Library Catalog -->
            <!-- Removed Add Book Button for Regular Users -->
            <div class="header-section" style="margin-top: 30px; margin-bottom: 20px;">
                <h2 class="section-title">Library Catalog</h2>
            </div>

            <table class="books-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>Availability</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $catalog_q = "SELECT b.book_id, b.title, b.author, b.category, b.pub_year,
                                    (SELECT COUNT(*) FROM copy c WHERE c.book_id = b.book_id AND c.status = 'Available') as avail_count
                                    FROM book b
                                    ORDER BY b.title ASC";
                    
                    $cat_res = $mysqli->query($catalog_q);
                    
                    if ($cat_res && $cat_res->num_rows > 0) {
                        while ($book = $cat_res->fetch_assoc()) {
                            $is_available = $book['avail_count'] > 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['category']) ?></td>
                                <td><?= htmlspecialchars($book['pub_year']) ?></td>
                                <td>
                                    <?php if ($is_available): ?>
                                        <span class="badge badge-available"><?= $book['avail_count'] ?> Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-out">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_available): ?>
                                        <form method="POST" action="user_website.php">
                                            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                                            <button type="submit" name="borrow_btn" class="btn-borrow">Borrow</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-disabled" disabled>Unavailable</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No books found in the library.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        </div> <!-- end div_content -->
    </div> <!-- end div_main -->
    <div id="div_footer"></div>
</div>
</body>
</html>