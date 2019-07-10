<?php
	require_once("includes/connect_db.php");
	session_start();
	$raw_global_msg=$con->query("SELECT * FROM chat WHERE target=0 and `time`=now() - interval 1 second");
	$raw_private_msg=$con->query("SELECT * FROM chat WHERE delivered=0 and (target=".$_SESSION["access"]." ) UNION select * from chat where (sender=".$_SESSION["access"]." and target>0) and `time`=now() - interval 1 second");
    $con->query("UPDATE chat set delivered=1 WHERE delivered=0 and target=".$_SESSION["access"]);
	$response=[];
	$con->query("UPDATE login set last_chat_renewal=now() where id=".$_SESSION["access"]);
	if($con->affected_rows==1){
        update_statistic($_SESSION["access"],["time_spent_in_saloon"=>1]);
    }
	while($global_msg=mysqli_fetch_array($raw_global_msg)){
		$raw_sender_name = $con->query("SELECT name FROM login where id=".$global_msg["sender"]);
		$sender_name=mysqli_fetch_array($raw_sender_name);
		$response[count($response)]=["target"=>$global_msg["target"],"sender"=>$sender_name["name"],"sender_id"=>$global_msg["sender"],"msg"=>$global_msg["message"]];
	}
	while($private_msg=mysqli_fetch_array($raw_private_msg)){
		if($private_msg["sender"]!=$_SESSION["access"]){
			$raw_sender_name = $con->query("SELECT name FROM login where id=".$private_msg["sender"]);

			$sender_name=mysqli_fetch_array($raw_sender_name);
			$response[count($response)]=["target"=>"user","sender"=>$sender_name["name"],"sender_id"=>$private_msg["sender"],"msg"=>$private_msg["message"]];
		}else{
			$raw_target_name = $con->query("SELECT name FROM login where id=".$private_msg["target"]);

			$target_name=mysqli_fetch_array($raw_target_name);
			$response[count($response)]=["target"=>$private_msg["target"],"target_name"=>$target_name["name"],"sender_id"=>"user","msg"=>$private_msg["message"]];
		}

	}

	header("Content-type: application/json");
	echo json_encode($response);
	$con->close();
?>