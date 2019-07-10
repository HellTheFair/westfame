<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$raw_user_data = $con->query("SELECT level,gold from `login` where `id`='".$_SESSION['access']."'");
		$user_data=mysqli_fetch_assoc($raw_user_data);
        $achieves=$con->query("SELECT id,name,description from achievement_list where id = any (SELECT achievement from achievement_log where user=".$_SESSION["access"].")")->fetch_All(MYSQLI_ASSOC);
        $titles=$con->query("SELECT id,name,description from knowledge_base where  id=any(SELECT item from inventory where type='title' and user=".$_SESSION["access"].")")->fetch_All(MYSQLI_ASSOC);
		header("Content-type: application/json");
		echo json_encode(["achieves"=>$achieves,"titles"=>$titles]);
	}
	$con->close();

?>