<?php
// connect.php
// Updated to point to the 'project' database
$mysqli = new mysqli('localhost','root','root','project');
if($mysqli->connect_errno){
   echo $mysqli->connect_errno.": ".$mysqli->connect_error;
}
?>