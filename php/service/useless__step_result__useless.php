<?php
	require_once("includes/connect_db.php");
	session_start();
	$raw_data=$con->query("SELECT * from `duel` WHERE (acceptor='".$_SESSION["access"]."' or creator='".$_SESSION["access"]."') and `started`=1 and `finished`=0");
	$data = mysqli_fetch_array($raw_data);
	if($data[0]){
		$raw_duel_data = $con->query("SELECT * FROM `duel_".$data["id"]."` ORDER by step DESC LIMIT 1");
		$duel_data = mysqli_fetch_array($raw_duel_data);
		$raw_prev_step = $con->query("SELECT * FROM `duel_".$data["id"]."` WHERE step='".(+$duel_data["step"]-1)."'");
		$prev_step=mysqli_fetch_array($raw_prev_step);
		header("Content-type: application/json");

		if($_SESSION["access"]==$data["acceptor"]){
			$raw_enemy = $con->query("SELECT * FROM `login` WHERE `id`='".$data["creator"]."'");
			$enemy = mysqli_fetch_array($raw_enemy);
			if($duel_data["step"]!=1){

				echo json_encode(["name"=>$enemy["name"], "player_position"=>$prev_step["a_def"], "enemy_position"=>$prev_step["c_def"], "player_max_hp"=>$prev_step["a_hp"], "player_hp"=>$duel_data["a_hp"], "enemy_hp"=>$duel_data["c_hp"], "enemy_max_hp"=>$prev_step["c_hp"], "ammo"=>$duel_data["a_ammo"],"time"=>((strtotime($duel_data["time"])+13) - strtotime("now")),"player_event"=>$prev_step["a_event"],"enemy_event"=>$prev_step["c_event"]]);

			}else{

				echo json_encode(["name"=>$enemy["name"], "player_position"=>3, "enemy_position"=>3, "player_hp"=>$duel_data["a_hp"], "player_max_hp"=>$duel_data["a_hp"], "enemy_hp"=>$duel_data["c_hp"], "enemy_max_hp"=>$duel_data["c_hp"], "ammo"=>$duel_data["a_ammo"], "time"=>((strtotime($duel_data["time"])+13) - strtotime("now")),"player_event"=>0,"enemy_event"=>0]);
			}


		}elseif($_SESSION["access"]==$data["creator"]){

			$raw_enemy = $con->query("SELECT * FROM `login` WHERE `id`='".$data["acceptor"]."'");
			$enemy = mysqli_fetch_array($raw_enemy);
			if($duel_data["step"]!=1){

				echo json_encode(["name"=>$enemy["name"], "player_position"=>$prev_step["c_def"], "enemy_position"=>$prev_step["a_def"], "player_hp"=>$duel_data["c_hp"], "player_max_hp"=>$prev_step["c_hp"], "enemy_hp"=>$duel_data["a_hp"], "enemy_max_hp"=>$prev_step["a_hp"], "ammo"=>$duel_data["c_ammo"],"time"=>((strtotime($duel_data["time"])+13) - strtotime("now")),"player_event"=>$prev_step["c_event"],"enemy_event"=>$prev_step["a_event"]]);
			}else{

				echo json_encode(["name"=>$enemy["name"], "player_position"=>3, "enemy_position"=>3, "player_max_hp"=>$duel_data["c_hp"], "player_hp"=>$duel_data["c_hp"], "enemy_hp"=>$duel_data["a_hp"], "enemy_max_hp"=>$duel_data["a_hp"], "ammo"=>$duel_data["c_ammo"], "time"=>((strtotime($duel_data["time"])+13) - strtotime("now")),"player_event"=>0,"enemy_event"=>0]);
			}
		}
	}else{
		echo 0;
	}


	$con->close();
?>