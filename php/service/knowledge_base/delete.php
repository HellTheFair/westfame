<?php
	error_reporting(0);
	session_start();
	if($_SESSION['access']){
		require_once("../php/includes/connect_db.php");	
		$status=mysqli_fetch_assoc($con->query("SELECT status from login where id=".$_SESSION["access"]))["status"];
		if($status>=1){
			if($_POST["item"]){
				require_once("../php/includes/connect_db.php");

				$item=mysqli_escape_string($con,$_POST["item"]);
				$con->query("DELETE FROM knowledge_base where id=".$item);
				echo "done";
			}
		}else{
			echo "access denied";
		}
	}else{
		echo "access denied";
	}
	
?>