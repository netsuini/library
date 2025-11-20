<?php
require_once 'connect.php';
session_start();

// 1. Security Check: Only Admins allowed
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Header Logic
$header_to_include = 'header.php'; 

?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="default.css">
<style>
    /* --- MODERN LAYOUT OVERRIDES --- */
    
    body {
        font-family: 'Segoe UI', 'Roboto', Helvetica, Arial, sans-serif;
        background-color: #f4f7f6; /* Light modern background */
        margin: 0;
        padding: 0;
        color: #333;
    }

    /* Center the main wrapper and make it look like a card */
    #wrapper {
        width: 90%;
        max-width: 1200px;
        margin: 40px auto; /* Center horizontally with top/bottom space */
        background-color: #ffffff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05); /* Soft shadow */
        border-radius: 12px; /* Rounded corners */
        overflow: hidden;
        border: none;
    }

    /* Adjust header styling if needed to fit new width */
    #div_header {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    /* Main content area layout */
    #div_main {
        display: flex;
        min-height: 600px;
    }

    /* Hide the old left sidebar to give more space/center content */
    #div_left {
        display: none; 
    }
    
    /* Expand content area and add inner spacing (Margin Left/Right/Top/Bottom) */
    #div_content {
        width: 100%;
        margin: 0;
        padding: 40px; /* This adds the "margin left side a little bit" inside the box */
        box-sizing: border-box;
    }

    /* --- TYPOGRAPHY & COMPONENTS --- */

    .book-management { 
        width: 100%; 
    }
    
    .section-title { 
        color: #2c3e50; 
        font-size: 28px; 
        font-weight: 600;
        margin: 0 0 25px 0;
        padding-bottom: 15px;
        border-bottom: 3px solid #4CAF50; /* Green underline accent */
        display: inline-block;
    }

    /* Modern Table Styling */
    .books-table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02); 
        border-radius: 8px; 
        overflow: hidden; 
        border: 1px solid #eee;
    }
    
    .books-table th { 
        background: #4CAF50; /* Primary Green */
        color: white; 
        text-align: left; 
        padding: 16px 20px; 
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }
    
    .books-table td { 
        padding: 16px 20px; 
        border-bottom: 1px solid #f0f0f0; 
        color: #555;
        vertical-align: middle;
    }

    .books-table tr:last-child td {
        border-bottom: none;
    }
    
    .books-table tr:hover {
        background-color: #f8fcf8; /* Very subtle green tint on hover */
    }
    

    /* Header Section Container */
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
</style>
</head>

<body>

<div id="wrapper"> 
    <?php include $header_to_include; ?>
    
    <div id="div_main">
        <!-- Left sidebar hidden via CSS for cleaner look -->
        <div id="div_left"></div>
        
        <div id="div_content" class="book-management">
            
            <!-- SECTION: Library Catalog (Read-Only for Admin) -->
            <div class="header-section">
                <h2 class="section-title"></h2>
            </div>

            <table class="books-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Publisher</th>
                        <th>Category</th>
                        <th>Year</th>
                        <th>Availability</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $catalog_q = "SELECT b.book_id, b.title, b.author, b.isbn, b.publisher, b.category, b.pub_year,
                                    (SELECT COUNT(*) FROM copy c WHERE c.book_id = b.book_id AND c.status = 'Available') as avail_count
                                    FROM book b
                                    ORDER BY b.title ASC";
                    
                    $cat_res = $mysqli->query($catalog_q);
                    
                    if ($cat_res && $cat_res->num_rows > 0) {
                        while ($book = $cat_res->fetch_assoc()) {
                            $is_available = $book['avail_count'] > 0;
                            ?>
                            <tr>
                                <td>#<?= htmlspecialchars($book['book_id']) ?></td>
                                <td style="font-weight:500; color:#333;"><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td style="font-family:monospace; color:#666;"><?= htmlspecialchars($book['isbn']) ?></td>
                                <td><?= htmlspecialchars($book['publisher']) ?></td>
                                <td><span class="category-badge"><?= htmlspecialchars($book['category']) ?></span></td>
                                <td><?= htmlspecialchars($book['pub_year']) ?></td>
                                <td>
                                    <?php if ($is_available): ?>
                                        <span class="badge badge-available"><?= $book['avail_count'] ?> Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-out">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center; padding: 40px; color:#888;'>No books found in the library.</td></tr>";
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