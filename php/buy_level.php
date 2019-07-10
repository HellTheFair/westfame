<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION["access"]){
		echo 0;
	}else{
		$raw_user=$con->query("SELECT level,max_hp from login where id=".$_SESSION["access"]);
		$user_data=mysqli_fetch_array($raw_user);
		$hp=($user_data['max_hp']/($start_hp+$hp_coef*($user_data["level"]-1)))*($start_hp+$hp_coef*$user_data["level"]);
		if($user_data["level"]<$max_level){
			$answer = $con->query("UPDATE login set max_hp=".$hp.",level=level+1, hp=max_hp, gold=gold-".($default_reward*$gold_coef*$user_data["level"]+$default_reward)*ceil($user_data["level"]/$level_coef)." where id=".$_SESSION["access"]." and gold>=".($default_reward*$gold_coef*$user_data["level"]+$default_reward)*ceil($user_data["level"]/$level_coef));
			if($con->affected_rows>0){
                update_statistic($_SESSION["access"],["gold_spent"=>($default_reward*$gold_coef*$user_data["level"]+$default_reward)*ceil($user_data["level"]/$level_coef)]);
			    echo 1;
            }else{
                echo "You're too poor";
            }
		}else{
			echo "Max Level";
		}
	}
	$con->close();

?>