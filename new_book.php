<?php require_once 'connect.php'; 
session_start();

$header_to_include = 'header.php';
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'librarian') {
    $header_to_include = 'librarian_header.php';
}

$statusMessage = '';

if (isset($_POST['sub'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $publisher = isset($_POST['publisher']) ? trim($_POST['publisher']) : '';
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    
    if (empty($title) || empty($author) || empty($isbn)) {
        $statusMessage = "Title, Author, and ISBN are required fields.";
    } else {
        // USE STORED PROCEDURE: add_book
        // PROCEDURE add_book (b_title, b_author, b_isbn, b_publisher, b_year, b_category)
        $stmt = $mysqli->prepare("CALL add_book(?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            // Note: Param order matches the Procedure definition, NOT the Form order
            $stmt->bind_param("ssssis", $title, $author, $isbn, $publisher, $year, $category);
            
            if ($stmt->execute()) {
                $statusMessage = "Book has been added successfully.";
            } else {
                $statusMessage = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $statusMessage = "Prepare failed: " . $mysqli->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add New Book</title>
<link rel="stylesheet" href="default.css">
<style>
    .book-form { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
    .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
    .btn-primary { background: #4CAF50; color: white; }
    .btn-warning { background: #ffc107; color: black; }
</style>
</head>

<body>
<div id="wrapper"> 
    <?php include $header_to_include; ?>
    <div id="div_main">
        <div id="div_left"></div>
        <div id="div_content" class="usergroup">
            
            <?php if ($statusMessage !== ''): ?>
                <div class="status" style="color: <?= strpos($statusMessage, 'failed') ? 'red' : 'green' ?>; padding:10px; border:1px solid #ccc; margin-bottom:10px;">
                    <?= htmlspecialchars($statusMessage); ?>
                </div>
            <?php endif; ?>
            
            <h2>Add New Book</h2>
            
            <div class="book-form">
                <form action="new_book.php" method="post">
                    <div class="form-group">
                        <label>Title*</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Author*</label>
                        <input type="text" name="author" required>
                    </div>
                    <div class="form-group">
                        <label>ISBN*</label>
                        <input type="text" name="isbn" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category">
                    </div>
                    <div class="form-group">
                        <label>Publisher</label>
                        <input type="text" name="publisher">
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <input type="number" name="year" min="1900" max="2099">
                    </div>
                    <div>
                        <button type="submit" name="sub" class="btn btn-primary">Add Book</button>
                        <button type="reset" class="btn btn-warning">Clear</button>
                    </div>
                </form>
            </div>

        </div> 
    </div>
    <div id="div_footer"></div>
</div>
</body>
</html>