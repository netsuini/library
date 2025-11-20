<?php
// This file makes sure that when someone visits your main domain
// (e.g. librarymanagement.fwh.is), they are automatically sent to login.php
header("Location: login.php");
exit();
?>