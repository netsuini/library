<?php
	$id = $_GET['id'];
	$userid = $_GET['userid'];
	require_once('connect.php');
	if (isset($id)) {
		$q="DELETE FROM USERGROUP where USERGROUP_ID=$id";
			if(!$mysqli->query($q)){
				echo "DELETE failed. Error: ".$mysqli->error ;
		   }
		   $mysqli->close();
		   //redirect
		   header("Location: group.php");
	}
	elseif(isset($userid)) {
		$q="DELETE FROM USER where USER_ID=$userid";
			if(!$mysqli->query($q)){
				echo "DELETE failed. Error: ".$mysqli->error ;
		   }
		   $mysqli->close();
		   //redirect
		   header("Location: user.php");
	}
?>
