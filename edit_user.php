<?php
require_once('connect.php');
$status = '';

if (isset($_POST['sub'])) {
	$userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
	$title = isset($_POST['title']) ? (int)$_POST['title'] : 0;
	$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
	$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
	$gender = isset($_POST['gender']) ? (int)$_POST['gender'] : 0;
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	$username = isset($_POST['username']) ? trim($_POST['username']) : '';
	$passwd = isset($_POST['passwd']) ? $_POST['passwd'] : '';
	$cpasswd = isset($_POST['cpasswd']) ? $_POST['cpasswd'] : '';
	$usergroup = isset($_POST['usergroup']) ? (int)$_POST['usergroup'] : 0;
	$disabled = isset($_POST['disabled']) ? 1 : 0;

	if ($userid <= 0) {
		$status = "Invalid user ID.";
	} elseif ($cpasswd !== '' && $passwd !== $cpasswd) {
		$status = "Password confirmation does not match.";
	} else {
		$stmt = $mysqli->prepare("UPDATE USER SET USER_TITLE=?,USER_FNAME=?,USER_LNAME=?,USER_GENDER=?,USER_EMAIL=?,USER_NAME=?,USER_PASSWD=?,USER_GROUPID=?,DISABLE=? where USER_ID=?");
		if ($stmt) {
			$stmt->bind_param(
				"ississsiii",
				$title,
				$firstname,
				$lastname,
				$gender,
				$email,
				$username,
				$passwd,
				$usergroup,
				$disabled,
				$userid
			);
			if ($stmt->execute()) {
				header("Location: user.php");
				exit;
			} else {
				$status = "Update failed. Error: " . $stmt->error;
			}
			$stmt->close();
		} else {
			$status = "Prepare failed. Error: " . $mysqli->error;
		}
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
				<!--%%%%% Main block %%%%-->
				<!--Form -->


				<h2>Edit User Profile</h2>
				<?php
				if ($status !== '') {
					echo "<div class='status'>" . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "</div>";
				}
				$userid = isset($_GET['userid']) ? (int)$_GET['userid'] : 0;
				if ($userid <= 0) {
					echo "<p>User not found.</p>";
				} else {
					$q = "SELECT * FROM USER where USER_ID = $userid";
					$result = $mysqli->query($q);
					if(!$result || $result->num_rows === 0){
						echo "<p>User not found.</p>";
					} else {
						$row = $result->fetch_assoc();
				?>
				<form action="edit_user.php" method="post">
					<label>Title</label>
					<select name="title">
						<?php
						$q1 = "select TITLE_ID,TITLE_NAME from TITLE";
						if ($result1 = $mysqli->query($q1)) {
							while ($row1 = $result1->fetch_assoc()) {
								$selected = ($row1['TITLE_ID'] == $row['USER_TITLE']) ? "selected='selected'" : '';
								echo "<option value='{$row1['TITLE_ID']}' $selected>{$row1['TITLE_NAME']}</option>";
							}
							$result1->free();
						} else {
							echo '<option disabled>Error loading titles</option>';
						}
						?>
					</select>

					<label>First name</label>
					<input type="text" name="firstname" value="<?= htmlspecialchars($row['USER_FNAME'], ENT_QUOTES, 'UTF-8'); ?>">

					<label>Last name</label>
					<input type="text" name="lastname" value="<?= htmlspecialchars($row['USER_LNAME'], ENT_QUOTES, 'UTF-8'); ?>">

					<label>Gender</label>
					<?php
					$q2 = 'select GENDER_ID, GENDER_NAME from GENDER;';
					if ($result2 = $mysqli->query($q2)) {
						while ($row2 = $result2->fetch_assoc()) {
							$checked = ($row2['GENDER_ID'] == $row['USER_GENDER']) ? "checked='checked'" : '';
							echo "<label><input type='radio' name='gender' value='{$row2['GENDER_ID']}' $checked> {$row2['GENDER_NAME']}</label>";
						}
						$result2->free();
					} else {
						echo 'Query error: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
					}
					?>
					<div></div>

					<label>Email</label>
					<input type="text" name="email" value="<?= htmlspecialchars($row['USER_EMAIL'], ENT_QUOTES, 'UTF-8'); ?>">

					<h2> Account Profile</h2>

					<label>Username</label>
					<input type="text" name="username" value="<?= htmlspecialchars($row['USER_NAME'], ENT_QUOTES, 'UTF-8'); ?>">

					<label>Password</label>
					<input type="password" name="passwd" value="<?= htmlspecialchars($row['USER_PASSWD'], ENT_QUOTES, 'UTF-8'); ?>">

					<label>Confirmed password</label>
					<input type="password" name="cpasswd">

					<label>User group</label>
					<select name="usergroup">
						<?php
						$q3 = 'select USERGROUP_ID, USERGROUP_NAME from USERGROUP;';
						if ($result3 = $mysqli->query($q3)) {
							while ($row3 = $result3->fetch_assoc()) {
								$selected = ($row3['USERGROUP_ID'] == $row['USER_GROUPID']) ? "selected='selected'" : '';
								echo "<option value='{$row3['USERGROUP_ID']}' $selected>{$row3['USERGROUP_NAME']}</option>";
							}
							$result3->free();
						} else {
							echo '<option disabled>Error loading user groups</option>';
						}
						?>
					</select>

					<label>Disabled</label>
					<input type="checkbox" name="disabled" <?php if ($row['DISABLE'] == 1) echo "checked='checked'"; ?>>

					<input type="hidden" name="userid" value="<?= (int)$row['USER_ID']; ?>">
					<div class="center">
						<input type="submit" name="sub" value="Submit">
						<input type="reset" value="Cancel">
					</div>
				</form>
				<?php
						$result->free();
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
