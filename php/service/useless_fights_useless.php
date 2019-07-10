<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$checker = $con->query("SELECT `id`,`creator`,`added` from `duel` WHERE `finished`=0 and `started`=0");
		
		$i=0;
		$response=[];
		while($data=mysqli_fetch_array($checker)){
			$name= $con->query("SELECT `name` FROM `login` WHERE `id`='".$data['creator']."'");
			$response[$i]=["id"=>$data['id'],"name"=>mysqli_fetch_row($name)[0],"time"=>$data["added"]];
			$i++;
		}
		
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>