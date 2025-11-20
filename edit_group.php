<?php
require_once('connect.php');
$status = '';
if (isset($_POST['su'])) {
    $groupcode = isset($_POST['groupcode']) ? trim($_POST['groupcode']) : '';
    $groupname = isset($_POST['groupname']) ? trim($_POST['groupname']) : '';
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    $groupurl = isset($_POST['groupurl']) ? trim($_POST['groupurl']) : '';
    $uid = isset($_POST['uid']) ? (int)$_POST['uid'] : 0;

    if ($uid > 0) {
        $stmt = $mysqli->prepare("UPDATE USERGROUP SET USERGROUP_CODE=?, USERGROUP_NAME=?, USERGROUP_REMARK=?, USERGROUP_URL=? WHERE USERGROUP_ID=?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $groupcode, $groupname, $remark, $groupurl, $uid);
            if ($stmt->execute()) {
                header("Location: group.php");
                exit;
            } else {
                $status = "Update failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $status = "Prepare failed: " . $mysqli->error;
        }
    } else {
        $status = "Invalid group ID.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>CSS326 Sample</title>
    <link rel="stylesheet" href="default.css">
</head>

<body>

    <div id="wrapper">
        <?php include 'header.php'; ?>
        <div id="div_main">
            <div id="div_left">

            </div>
            <div id="div_content" class="form">
                <h2>Edit User Group</h2>
                <?php
                if ($status !== '') {
                    echo "<div class='status'>" . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "</div>";
                }
                $uid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                if ($uid <= 0) {
                    echo "<p>Group not found.</p>";
                } else {

                    $q = "SELECT * FROM USERGROUP where USERGROUP_ID=$uid";
                    $result = $mysqli->query($q);
                    if(!$result || $result->num_rows === 0){
                        echo "<p>Group not found.</p>";
                    } else {
                        $row = $result->fetch_assoc();
                        echo "<form action='edit_group.php' method='post'>";
                        echo "<label>Group Code</label>";
                        echo "<input type='text' name='groupcode' value='" . htmlspecialchars($row['USERGROUP_CODE'], ENT_QUOTES, 'UTF-8') . "'>";

                        echo "<label>Group Name</label>";
                        echo "<input type='text' name='groupname' value='" . htmlspecialchars($row['USERGROUP_NAME'], ENT_QUOTES, 'UTF-8') . "'>";

                        echo "<label>Remark</label>";
                        echo "<textarea name='remark'>" . htmlspecialchars($row['USERGROUP_REMARK'], ENT_QUOTES, 'UTF-8') . "</textarea>";
                        echo "<input type='hidden' name='uid' value='" . (int)$row['USERGROUP_ID'] . "'>";

                        echo "<label>Url</label>";
                        echo "<input type='text' name='groupurl' value='" . htmlspecialchars($row['USERGROUP_URL'], ENT_QUOTES, 'UTF-8') . "'>";
                        echo "<div class='center'>";
                        echo "<input type='submit' name='su' value='Submit'>";
                        echo "<input type='reset' value='Cancel'>";
                        echo "</div>";
                        echo "</form>";
                    }
                }

                ?>

            </div> <!-- end div_content -->

        </div> <!-- end div_main -->

        <div id="div_footer">

        </div>

    </div>
</body>

</html>
