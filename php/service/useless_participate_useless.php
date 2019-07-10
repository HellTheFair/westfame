<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$checker = $con->query("SELECT * from `duel` WHERE `id`='".$_POST['id']."' and `started`=0");
		$check_data=mysqli_fetch_array($checker);
		$checker1 = $con->query("SELECT * from `duel` WHERE `acceptor`='".$_SESSION['access']."' and `finished`=0 and `started`=1");
		$check1_data=mysqli_fetch_array($checker1);
		if($check_data["creator"]!=$_SESSION["access"]&&$check_data["acceptor"]==0&&!$check1_data[0]){
			$con->query("UPDATE `duel` SET `acceptor`='".$_SESSION['access']."' WHERE `id`='".$_POST['id']."'");
			
			echo 1;
		}else{
			echo 0;
		}
		
	}
	$con->close();
?>