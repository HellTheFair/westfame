<?php 
	//error_reporting(0);
	session_start();
	if($_SESSION['access']){
		require_once("../php/includes/connect_db.php");	
		$status=mysqli_fetch_assoc($con->query("SELECT status from login where id=".$_SESSION["access"]))["status"];
		echo $status;
		if($status>=1){
			echo 1;
		}else{
			echo 0;
		}
	}else{
		echo 0;
	}	

?>