<?php
	error_reporting(0);
	session_start();
	if($_SESSION['access']){
		require_once("../php/includes/connect_db.php");	
		$status=mysqli_fetch_assoc($con->query("SELECT status from login where id=".$_SESSION["access"]))["status"];
		if($status>=1){
			if($_POST["id"]){
				$item=[];
				foreach($_POST as $key => $value){
					$item[$key] = mysqli_escape_string($con,$_POST[$key]);
				}
				$text="";
				foreach($item as $key => $value){
					if($text!="")$text.=", ";
					$text.=$key."='".$value."'";
				}
				
				$con->query("UPDATE knowledge_base set ".$text." where id=".$item["id"]);
				echo 1;
			}
		}else{
			echo "access denied";
		}
	}else{
		echo "access denied";
	}
	

?>