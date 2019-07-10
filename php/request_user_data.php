<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$next_level=0;
		$gold_to_level=0;
		$user_data=mysqli_fetch_assoc($con->query("SELECT level,gold,fame,hp,max_hp,tutorial from `login` where `id`='".$_SESSION['access']."'"));

		$next_level_hp=$user_data['max_hp'];
        $next_skills=0;

		if(+$user_data["level"]<$max_level){
			$next_level=+$user_data["level"] + 1;
			$gold_to_level=($default_reward*$gold_coef*$user_data["level"]+$default_reward)*ceil($user_data["level"]/$level_coef);
			$next_level_hp=($user_data['max_hp']/($start_hp+$hp_coef*($user_data["level"]-1)))*($start_hp+$hp_coef*$user_data["level"]);
			$next_skills=mysqli_fetch_row($con->query("SELECT count(*) from skill where level=".$next_level))[0];
		}

		$protocol=mysqli_fetch_assoc($con->query("SELECT id,sheriff_text from protocols where id=".$user_data["tutorial"]." and level <=".$user_data["level"]." and type='tutorial' ORDER BY id asc"));
		$available_skills=$con->query("SELECT `id`,`name`,`cd`,`description`,`picture`,`passive` from `skill` Where level between 1 and ".$user_data["level"])->fetch_All(MYSQLI_ASSOC);

		$response=['fame'=>$user_data["fame"],'protocol'=>$protocol,'level'=>$user_data["level"],'next_level'=>$next_level,'next_level_hp'=>$next_level_hp,'max_hp'=>$user_data['max_hp'],'hp'=>$user_data['hp'],'next_skills'=>$next_skills,'gold_to_level'=>$gold_to_level,'gold'=>$user_data['gold'],"available_skills"=>$available_skills];
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>