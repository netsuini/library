<?php
require_once 'connect.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_type'] !== 'librarian' && $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

$header_to_include = ($_SESSION['user_type'] === 'librarian') ? 'librarian_header.php' : 'header.php';

// Helper Function
function redirectWithMsg($msg, $type) {
    $_SESSION['status_msg'] = $msg;
    $_SESSION['status_type'] = $type;
    header("Location: supplier.php");
    exit();
}

// =================================================================
// ACTION 1: CREATE NEW ACQUISITION (INVOICE)
// =================================================================
if (isset($_POST['create_acq_btn'])) {
    $supplier_id = (int)$_POST['supplier_id'];
    $user_id = $_SESSION['user_id'];
    
    if ($supplier_id > 0) {
        $mysqli->query("SET @new_acq_id = 0");
        $q = "CALL create_acquisition($user_id, $supplier_id, @new_acq_id)";
        if ($mysqli->query($q)) {
            $res = $mysqli->query("SELECT @new_acq_id as id");
            $row = $res->fetch_assoc();
            redirectWithMsg("Success: New Invoice #{$row['id']} created.", "success");
        } else {
            redirectWithMsg("Error: " . $mysqli->error, "error");
        }
    } else {
        redirectWithMsg("Error: Select a supplier.", "error");
    }
}

// =================================================================
// ACTION 2: ADD ACQUISITION ITEM
// =================================================================
if (isset($_POST['add_item_btn'])) {
    $acq_id = isset($_POST['acq_id']) ? (int)$_POST['acq_id'] : 0;
    if ($acq_id === 0) {
        $r = $mysqli->query("SELECT acq_id FROM acquisition ORDER BY acq_id DESC LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) $acq_id = $row['acq_id'];
    }

    $book_id = (int)$_POST['book_id'];
    $qty = (int)$_POST['qty'];
    $price = (int)$_POST['price'];

    if ($acq_id > 0 && $book_id > 0 && $qty > 0 && $price > 0) {
        if ($stmt = $mysqli->prepare("CALL add_acquisition_item(?, ?, ?, ?)")) {
            $stmt->bind_param("iiii", $acq_id, $book_id, $qty, $price);
            if ($stmt->execute()) {
                redirectWithMsg("Success: Added $qty copies.", "success");
            } else {
                if (strpos($stmt->error, 'Duplicate entry') !== false) {
                    redirectWithMsg("Error: Book already in Invoice #$acq_id. Create a new invoice.", "error");
                } else {
                    redirectWithMsg("Error: " . $stmt->error, "error");
                }
            }
            $stmt->close();
        } else {
            redirectWithMsg("DB Error: " . $mysqli->error, "error");
        }
    } else {
        redirectWithMsg("Error: Invalid inputs.", "error");
    }
}

// =================================================================
// ACTION 3: ADD SUPPLIER
// =================================================================
if (isset($_POST['add_supplier_btn'])) {
    $sup_name = trim($_POST['sup_name']);
    $sup_contact = trim($_POST['sup_contact']);
    if (!empty($sup_name)) {
        if ($stmt = $mysqli->prepare("INSERT INTO supplier (name, contact_phone) VALUES (?, ?)")) {
            $stmt->bind_param("ss", $sup_name, $sup_contact);
            if ($stmt->execute()) redirectWithMsg("Supplier added.", "success");
            else redirectWithMsg("Error: " . $stmt->error, "error");
            $stmt->close();
        }
    }
}

// =================================================================
// ACTION 4: REMOVE SUPPLIER
// =================================================================
if (isset($_POST['delete_supplier_btn'])) {
    $del_id = (int)$_POST['supplier_id'];
    if ($stmt = $mysqli->prepare("DELETE FROM supplier WHERE supplier_id = ?")) {
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            redirectWithMsg("Supplier deleted.", "success");
        } else {
            if ($mysqli->errno == 1451) redirectWithMsg("Cannot delete: Supplier is linked to acquisitions.", "error");
            else redirectWithMsg("Error: " . $stmt->error, "error");
        }
        $stmt->close();
    }
}

// =================================================================
// ACTION 5: DELETE ACQUISITION ITEM (NEW)
// =================================================================
if (isset($_POST['delete_item_btn'])) {
    $item_id = (int)$_POST['item_id'];
    
    // 1. Get details to subtract cost from Invoice Total
    $check_q = "SELECT acq_id, (qty * unit_price) as subtotal FROM acquisitionitem WHERE accquisition_item_id = $item_id";
    $res = $mysqli->query($check_q);
    
    if ($res && $row = $res->fetch_assoc()) {
        $acq_id = $row['acq_id'];
        $deduct_amount = $row['subtotal'];
        
        // 2. Delete the Item
        $del_stmt = $mysqli->prepare("DELETE FROM acquisitionitem WHERE accquisition_item_id = ?");
        $del_stmt->bind_param("i", $item_id);
        
        if ($del_stmt->execute()) {
            // 3. Update the Main Invoice Total
            $mysqli->query("UPDATE acquisition SET total_amount = total_amount - $deduct_amount WHERE acq_id = $acq_id");
            
            redirectWithMsg("Item deleted. Invoice total updated. (Note: Physical copies remain in stock)", "success");
        } else {
            redirectWithMsg("Error deleting item: " . $del_stmt->error, "error");
        }
        $del_stmt->close();
    } else {
        redirectWithMsg("Item not found.", "error");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Supplier Management</title>
<link rel="stylesheet" href="default.css">
<style>
    .book-management { font-family: 'Segoe UI', sans-serif; color: #333; max-width: 1200px; margin: 0 auto; padding: 20px; }
    .section-title { color: #2c3e50; font-size: 24px; border-left: 5px solid #4CAF50; padding-left: 15px; margin: 30px 0 15px 0; }
    
    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .data-table th { background: #4CAF50; color: white; text-align: left; padding: 12px; }
    .data-table td { padding: 12px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle;}
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: #e3f2fd; color: #1565c0; }

    /* Alerts */
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
    .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }

    /* Forms */
    .form-card { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px; }
    .form-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .form-group { display: flex; flex-direction: column; flex: 1; min-width: 150px; }
    .form-group.small { flex: 0 0 100px; }
    .form-group label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555; }
    .form-group select, .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
    
    /* Buttons */
    .btn-add { background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 37px; }
    .btn-add:hover { background: #45a049; }
    .btn-blue { background: #2196F3; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 37px; }
    .btn-blue:hover { background: #1976D2; }
    .btn-delete { background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-delete:hover { background: #d32f2f; }
    .btn-purple { background: #9C27B0; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; height: 37px; }
    .btn-purple:hover { background: #7B1FA2; }

    .auto-info { font-size: 13px; color: #666; margin-bottom: 15px; border-bottom: 1px dashed #ccc; padding-bottom: 10px; font-style: italic; }
</style>
</head>

<body>

<div id="wrapper"> 
    <?php include $header_to_include; ?>
    <div id="div_main">
        <div id="div_left"></div>
        
        <div id="div_content" class="book-management">
            
            <?php if (isset($_SESSION['status_msg'])): ?>
                <div class="alert alert-<?= $_SESSION['status_type'] ?>">
                    <?= htmlspecialchars($_SESSION['status_msg']) ?>
                </div>
                <?php unset($_SESSION['status_msg']); unset($_SESSION['status_type']); ?>
            <?php endif; ?>

            <!-- 1. SUPPLIER MANAGEMENT -->
            <h2 class="section-title">Supplier Management</h2>
            
            <div class="form-card" style="background: #e3f2fd; border-color: #90caf9;">
                <form method="POST" action="supplier.php">
                    <div class="form-row">
                        <div class="form-group"><label>Name</label><input type="text" name="sup_name" required></div>
                        <div class="form-group"><label>Contact</label><input type="text" name="sup_contact" required></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit" name="add_supplier_btn" class="btn-blue">Add Supplier</button></div>
                    </div>
                </form>
            </div>

            <table class="data-table">
                <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Action</th></tr></thead>
                <tbody>
                    <?php
                    $res = $mysqli->query("SELECT * FROM supplier ORDER BY supplier_id ASC");
                    if ($res && $res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            echo "<tr><td>#{$row['supplier_id']}</td><td>{$row['name']}</td><td>{$row['contact_phone']}</td>
                                  <td><form method='POST' style='margin:0;'><input type='hidden' name='supplier_id' value='{$row['supplier_id']}'><button type='submit' name='delete_supplier_btn' class='btn-delete' onclick=\"return confirm('Delete?');\">Remove</button></form></td></tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <!-- 2. ACQUISITION MANAGEMENT -->
            <h2 class="section-title">Acquisition (Invoice) Management</h2>
            
            <div class="form-card" style="background: #f3e5f5; border-color: #ce93d8;">
                <h3 style="margin-top:0; font-size:16px; color:#6a1b9a;">Step 1: Start New Invoice</h3>
                <form method="POST" action="supplier.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Select Supplier</label>
                            <select name="supplier_id" required>
                                <option value="">-- Choose Supplier --</option>
                                <?php
                                $res = $mysqli->query("SELECT supplier_id, name FROM supplier ORDER BY name ASC");
                                while ($s = $res->fetch_assoc()) {
                                    echo "<option value='{$s['supplier_id']}'>{$s['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit" name="create_acq_btn" class="btn-purple">Create Invoice</button></div>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <h3 style="margin-top:0; font-size:16px; color:#2e7d32;">Step 2: Add Items to Invoice</h3>
                <?php
                while($mysqli->more_results()){ $mysqli->next_result(); } // Clear connection
                $latest_id = 0;
                $res = $mysqli->query("SELECT acq_id FROM acquisition ORDER BY acq_id DESC LIMIT 1");
                if ($res && $row = $res->fetch_assoc()) {
                    $latest_id = $row['acq_id'];
                    echo "<div class='auto-info'>Active Invoice: <strong>#{$latest_id}</strong></div>";
                } else {
                    echo "<div class='auto-info' style='color:red;'>No Active Invoice. Please create one above.</div>";
                }
                ?>
                <form method="POST" action="supplier.php">
                    <input type="hidden" name="acq_id" value="<?= $latest_id ?>">
                    <div class="form-row">
                        <div class="form-group" style="flex:2;">
                            <label>Select Book</label>
                            <select name="book_id" required>
                                <option value="">-- Select Book --</option>
                                <?php
                                $res = $mysqli->query("SELECT book_id, title FROM book ORDER BY title ASC");
                                while ($b = $res->fetch_assoc()) {
                                    echo "<option value='{$b['book_id']}'>{$b['title']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group small"><label>Qty</label><input type="number" name="qty" min="1" value="1" required></div>
                        <div class="form-group small"><label>Price ($)</label><input type="number" name="price" min="0" required></div>
                        <div class="form-group" style="flex:0;"><label>&nbsp;</label><button type="submit" name="add_item_btn" class="btn-add" <?= ($latest_id == 0 ? 'disabled' : '') ?>>+ Add Item</button></div>
                    </div>
                </form>
            </div>

            <!-- 3. HISTORY TABLES -->
            <h2 class="section-title">History & Logs</h2>
            
            <h4 style="margin-bottom:5px;">Recent Invoices</h4>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Date</th><th>Librarian</th><th>Supplier</th><th>Total</th></tr></thead>
                <tbody>
                    <?php
                    $q = "SELECT a.acq_id, a.acq_date, a.total_amount, u.name as lib, s.name as sup FROM acquisition a JOIN user u ON a.librarian_id = u.user_id JOIN supplier s ON a.supplier_id = s.supplier_id ORDER BY a.acq_date DESC LIMIT 5";
                    $res = $mysqli->query($q);
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr><td>#{$row['acq_id']}</td><td>{$row['acq_date']}</td><td>{$row['lib']}</td><td>{$row['sup']}</td><td>$".number_format($row['total_amount'],2)."</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <h2 style="margin-bottom:5px;">Stock</h2>
            <table class="data-table">
                <thead>
                    <tr><th>Item</th><th>Inv #</th><th>Book</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT ai.accquisition_item_id, ai.acq_id, b.title, ai.qty, ai.unit_price FROM acquisitionitem ai JOIN book b ON ai.book_id = b.book_id ORDER BY ai.accquisition_item_id DESC LIMIT 10";
                    $res = $mysqli->query($q);
                    while ($row = $res->fetch_assoc()) {
                        $t = $row['qty'] * $row['unit_price'];
                        echo "<tr>
                                <td>#{$row['accquisition_item_id']}</td>
                                <td><span class='badge'>#{$row['acq_id']}</span></td>
                                <td>{$row['title']}</td>
                                <td>{$row['qty']}</td>
                                <td>\${$row['unit_price']}</td>
                                <td><strong>$".number_format($t,2)."</strong></td>
                                <td>
                                    <form method='POST' style='margin:0;'>
                                        <input type='hidden' name='item_id' value='{$row['accquisition_item_id']}'>
                                        <button type='submit' name='delete_item_btn' class='btn-delete' onclick=\"return confirm('Delete item? Invoice total will decrease.');\">Delete</button>
                                    </form>
                                </td>
                              </tr>";
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