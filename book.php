<?php 
require_once('connect.php'); 
session_start();

// =================================================================
// Header Logic: Check Session Role
// =================================================================
$header_to_include = 'header.php'; // Default for Admins or Guests

if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'librarian') {
        $header_to_include = 'librarian_header.php';
    } elseif ($_SESSION['user_type'] === 'user' || $_SESSION['user_type'] === 'member') {
        $header_to_include = 'user_header.php';
    }
}
?> 
<!DOCTYPE html>
<html>
<head>
<title>Library - Books</title>
<link rel="stylesheet" href="default.css">
<style>
    .book-management { padding: 20px; }
    .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
    .add-book-btn { background: #4CAF50; color: white; padding: 10px 20px; border-radius: 20px; text-decoration: none; }
    .books-table { width: 100%; border-collapse: collapse; }
    .books-table th { background: #4CAF50; color: white; padding: 10px; text-align: left; }
    .books-table td { padding: 10px; border-bottom: 1px solid #ddd; }
    
    /* Card View Styles (Hidden by default) */
    .book-card { display: none; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
    .book-card.active { display: grid; }
    .book-item { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .book-cover { height: 150px; background: linear-gradient(135deg, #8BC6EC 0%, #9599E2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; padding: 10px; text-align: center; }
    .book-info { padding: 15px; }
    
    /* Toggles */
    .view-toggle { display: flex; justify-content: flex-end; margin-bottom: 15px; }
    .toggle-btn { background: #f0f0f0; border: none; padding: 8px 12px; margin-left: 5px; cursor: pointer; border-radius: 5px 5px 0 0; }
    .toggle-btn.active { background: #4CAF50; color: white; }
</style>
</head>
<body>
<div id="wrapper"> 
    
    <?php include $header_to_include; ?>
    
    <div id="div_main">
        <div id="div_left"></div>
        <div id="div_content" class="book-management">
            <div class="header-section">
                <h2>Book Management</h2>
                <a href="new_book.php" class="add-book-btn">+ Add New Book</a>
            </div>
            
            <div class="view-toggle">
                <button class="toggle-btn active" onclick="showView('table')">Table View</button>
                <button class="toggle-btn" onclick="showView('card')">Card View</button>
            </div>
            
            <div class="table-view" id="tableView">
                <table class="books-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $q="select * from book";
                            $result=$mysqli->query($q);
                            if(!$result){
                                echo "<tr><td colspan='6'>Select failed. Error: ".$mysqli->error."</td></tr>";
                            } else {
                                while($row=$result->fetch_array()){ ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td> 
                                        <td><?= htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($row['isbn'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($row['pub_year'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_book.php?id=<?=$row['book_id']?>"><img src="images/Modify.png" width="18" height="18" alt="Edit"></a>
                                                <a href='delbook.php?id=<?=$row['book_id']?>' onclick="return confirm('Delete this book?')"><img src="images/Delete.png" width="18" height="18" alt="Delete"></a>
                                            </div>
                                        </td>
                                    </tr>                               
                            <?php } 
                            } ?>
                    </tbody>
                </table>
            </div>

            <div class="book-card" id="cardView">
                <?php
                    // Reset pointer to reuse result set
                    if ($result) $result->data_seek(0);
                    if ($result) {
                        while($row=$result->fetch_array()){ ?>
                        <div class="book-item">
                            <div class="book-cover">📚 <?= htmlspecialchars($row['title']) ?></div>
                            <div class="book-info">
                                <div style="font-weight:bold;"><?= htmlspecialchars($row['title']) ?></div>
                                <div style="color:#666; font-size:13px;">by <?= htmlspecialchars($row['author']) ?></div>
                                <div style="margin-top:10px; font-size:12px; color:#888;">
                                    ISBN: <?= htmlspecialchars($row['isbn']) ?>
                                </div>
                            </div>
                        </div>
                <?php } } ?>
            </div>
            
            <?php 
                $count = 0;
                if($countResult = $mysqli->query("SELECT COUNT(*) AS total FROM book")){
                    $row = $countResult->fetch_assoc();
                    $count = (int)$row['total'];
                }
            ?>
            <div class="book-count" style="margin-top:10px; text-align:right;">Total <?= $count ?> book(s)</div>
        </div> 
    </div>
    <div id="div_footer"></div>
</div>

<script>
    function showView(viewType) {
        const tableView = document.getElementById('tableView');
        const cardView = document.getElementById('cardView');
        const buttons = document.querySelectorAll('.toggle-btn');
        
        buttons.forEach(btn => btn.classList.remove('active'));
        
        if (viewType === 'table') {
            tableView.style.display = 'block';
            cardView.classList.remove('active');
            buttons[0].classList.add('active');
        } else {
            tableView.style.display = 'none';
            cardView.classList.add('active');
            buttons[1].classList.add('active');
        }
    }
</script>
</body>
</html>