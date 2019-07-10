<?php
	require_once("includes/connect_db.php");
	session_start();

	if(isset($_SESSION["access"])){
		$raw_data=$con->query("SELECT id FROM `duel` WHERE (`creator`='".$_SESSION["access"]."' or `acceptor`='".$_SESSION["access"]."') and finished=0");
		$data=mysqli_fetch_row($raw_data);
		if($data[0]){
			echo 1;
		}else{
			echo 0;
		}
	}else{
		echo 0;
	}
	$con->close();
?>