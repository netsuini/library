<?php require_once('connect.php'); ?> 
<!DOCTYPE html>
<html>
<head>
<title>library</title>
<link rel="stylesheet" href="default.css">
</head>
<body>
<div id="wrapper"> 
	<?php include 'header.php'; ?>
	<div id="div_main">
		<div id="div_left">
				
		</div>
		<div id="div_content" class="usergroup">
			<!--%%%%% Main block %%%%-->
			<?php 
				$status = '';
				if(isset($_POST['submit'])) {
					$groupcode = isset($_POST['groupcode']) ? trim($_POST['groupcode']) : '';
					$groupname = isset($_POST['groupname']) ? trim($_POST['groupname']) : '';
					$remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
					$url = isset($_POST['url']) ? trim($_POST['url']) : '';

					$stmt = $mysqli->prepare("INSERT INTO USERGROUP (USERGROUP_CODE, USERGROUP_NAME, USERGROUP_REMARK, USERGROUP_URL) VALUES (?,?,?,?)");
					if($stmt){
						$stmt->bind_param("ssss",$groupcode,$groupname,$remark,$url);
						if($stmt->execute()){
							$status = "User group has been added.";
						}else{
							$status = "Insert failed: ".$stmt->error;
						}
						$stmt->close();
					}else{
						$status = "Prepare failed: ".$mysqli->error;
					}
				}
			?>
			<h2>User Group</h2>			
			<?php if($status !== ''): ?>
				<div class="status"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></div>
			<?php endif; ?>
			<table>
                <col width="10%">
                <col width="20%">
                <col width="30%">
                <col width="30%">
                <col width="5%">
                <col width="5%">

            
			
				
				
				<tr>
                    <th>Group Code</th> 
                    <th>Group Name</th>
                    <th>Remark</th>
                    <th>URL</th>
                    <th>Edit</th>
                    <th>Del</th>
                </tr>
				 <?php
				 	$q="select * from USERGROUP";
					$result=$mysqli->query($q);
					if(!$result){
						echo "Select failed. Error: ".$mysqli->error ;
						return false;
					}
				 while($row=$result->fetch_array()){ ?>
                 <tr>
                    <td><?= htmlspecialchars($row['USERGROUP_CODE'], ENT_QUOTES, 'UTF-8'); ?></td> 
                    <td><?= htmlspecialchars($row['USERGROUP_NAME'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($row['USERGROUP_REMARK'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($row['USERGROUP_URL'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a href="edit_group.php?id=<?=$row['USERGROUP_ID']?>"><img src="images/Modify.png" width="24" height="24" alt="Edit"></a></td>
                    <td><a href='delinfo.php?id=<?=$row['USERGROUP_ID']?>'> <img src="images/Delete.png" width="24" height="24" alt="Delete"></a></td>
                </tr>                               
				<?php } ?>

			<?php 
			// count the no. of entries
				$count = 0;
				if($countResult = $mysqli->query("SELECT COUNT(*) AS total FROM USERGROUP")){
					$row = $countResult->fetch_assoc();
					$count = (int)$row['total'];
					$countResult->free();
				}
			?>
            </table>	
			<p>Total <?= $count ?> record(s)</p>
				
		</div> <!-- end div_content -->
		
	</div> <!-- end div_main -->
	
	<div id="div_footer">  
		
	</div>

</div>
</body>
</html>

