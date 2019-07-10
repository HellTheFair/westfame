<?php
	require_once("includes/connect_db.php");
	session_start();
	$raw_data=$con->query("SELECT `id`,`acceptor`,`creator` from `duel` WHERE (acceptor='".$_SESSION["access"]."' or creator='".$_SESSION["access"]."') and `started`=1 and `finished`=0 ORDER BY `id` DESC");
	$data = mysqli_fetch_array($raw_data);

	if($data[0]){
		$raw_duel_data = $con->query("SELECT * FROM `duel_".$data["id"]."` ORDER by step DESC LIMIT 1");
		$duel_data = mysqli_fetch_array($raw_duel_data);
		$raw_prev_step = $con->query("SELECT `a_def`,`c_def`,`a_attack`,`c_attack` FROM `duel_".$data["id"]."` WHERE step='".(+$duel_data["step"]-1)."'");
		$raw_c_data = $con->query("SELECT * FROM login WHERE id=".$data["creator"]);
		$raw_a_data = $con->query("SELECT * FROM login WHERE id=".$data["acceptor"]);
		$c_data = mysqli_fetch_array($raw_c_data);
		$a_data = mysqli_fetch_array($raw_a_data);
		$prev_step=mysqli_fetch_array($raw_prev_step);
		$skill_cd=[];

		header("Content-type: application/json");

		if($_SESSION["access"]==$data["acceptor"]){
			$raw_enemy = $con->query("SELECT `login`.`name`,`knowledge_base`.`picture`,`knowledge_base`.`twohand` FROM `login`,`knowledge_base` WHERE `login`.`id`='".$data["creator"]."' and `knowledge_base`.`id`=`login`.`skin`");
			$enemy = mysqli_fetch_assoc($raw_enemy);

			for($z=1;$z<=$skills_amount;$z++){
				if($a_data["skill".$z]!=0){
					$raw_skill_data = $con->query("SELECT `id`,`cd` from `skill` WHERE id=".$a_data["skill".$z]);
					$skill_data=mysqli_fetch_array($raw_skill_data);
					$raw_cd_check = $con->query("SELECT `step` from `duel_".$data["id"]."` where a_skill='".$skill_data['id']."' and not step=".$duel_data["step"]." ORDER BY step DESC LIMIT 1");
					$cd_check = mysqli_fetch_array($raw_cd_check);
					if(!$cd_check||$duel_data["step"]-$cd_check["step"]>$skill_data["cd"]){
						$skill_cd[]=0;
					}else{
						$skill_cd[]=$skill_data["cd"]-($duel_data["step"]-$cd_check["step"])+1;
					}
				}
				
			}

			if($duel_data["step"]!=1){

				echo json_encode(["name"=>$enemy["name"],"skin"=>$enemy["twohand"]."/".$enemy["picture"], "player_position"=>$prev_step["a_def"], "enemy_position"=>$prev_step["c_def"], "player_attack"=>$prev_step["a_attack"], "enemy_attack"=>$prev_step["c_attack"], "player_max_hp"=>$a_data["max_hp"], "player_hp"=>$duel_data["a_hp"], "enemy_hp"=>$duel_data["c_hp"], "enemy_max_hp"=>$c_data["max_hp"], "ammo"=>$duel_data["a_ammo"],"time"=>((strtotime($duel_data["time"])) - strtotime("now")),"player_event"=>$duel_data["a_event"],"enemy_event"=>$duel_data["c_event"],"skill_cd"=>$skill_cd]);

			}else{

				echo json_encode(["name"=>$enemy["name"],"skin"=>$enemy["twohand"]."/".$enemy["picture"], "player_position"=>3, "enemy_position"=>3, "player_hp"=>$duel_data["a_hp"], "player_max_hp"=>$a_data["max_hp"], "enemy_hp"=>$duel_data["c_hp"], "enemy_max_hp"=>$c_data["max_hp"], "ammo"=>$duel_data["a_ammo"], "time"=>((strtotime($duel_data["time"])) - strtotime("now")+18),"player_event"=>0,"enemy_event"=>0,"skill_cd"=>$skill_cd]);
			}


		}elseif($_SESSION["access"]==$data["creator"]){

			$raw_enemy = $con->query("SELECT `login`.`name`,`knowledge_base`.`picture`,`knowledge_base`.`twohand` FROM `login`,`knowledge_base` WHERE `login`.`id`='".$data["acceptor"]."' and `knowledge_base`.`id`=`login`.`skin`");
			$enemy = mysqli_fetch_array($raw_enemy);

			for($z=1;$z<=$skills_amount;$z++){
				if($c_data["skill".$z]!=0){
					$raw_skill_data = $con->query("SELECT `id`,`cd` from `skill` WHERE id=".$c_data["skill".$z]);
					$skill_data=mysqli_fetch_array($raw_skill_data);
					$raw_cd_check = $con->query("SELECT `step` from `duel_".$data["id"]."` where c_skill='".$skill_data['id']."' and not step=".$duel_data["step"]." ORDER BY step DESC LIMIT 1");
					$cd_check = mysqli_fetch_array($raw_cd_check);
					if(!$cd_check||$duel_data["step"]-$cd_check["step"]>$skill_data["cd"]){
						$skill_cd[count($skill_cd)]=0;
					}else{
						$skill_cd[count($skill_cd)]=$skill_data["cd"]-($duel_data["step"]-$cd_check["step"])+1;
					}
				}
				
			}

			if($duel_data["step"]!=1){

				echo json_encode(["name"=>$enemy["name"],"skin"=>$enemy["twohand"]."/".$enemy["picture"], "player_position"=>$prev_step["c_def"], "enemy_position"=>$prev_step["a_def"], "player_attack"=>$prev_step["c_attack"], "enemy_attack"=>$prev_step["a_attack"], "player_hp"=>$duel_data["c_hp"], "player_max_hp"=>$c_data["max_hp"], "enemy_hp"=>$duel_data["a_hp"], "enemy_max_hp"=>$a_data["max_hp"], "ammo"=>$duel_data["c_ammo"],"time"=>((strtotime($duel_data["time"])) - strtotime("now")),"player_event"=>$duel_data["c_event"],"enemy_event"=>$duel_data["a_event"],"skill_cd"=>$skill_cd]);
			}else{

				echo json_encode(["name"=>$enemy["name"],"skin"=>$enemy["twohand"]."/".$enemy["picture"], "player_position"=>3, "enemy_position"=>3, "player_max_hp"=>$c_data["max_hp"], "player_hp"=>$duel_data["c_hp"], "enemy_hp"=>$duel_data["a_hp"], "enemy_max_hp"=>$a_data["max_hp"], "ammo"=>$duel_data["c_ammo"], "time"=>((strtotime($duel_data["time"])) - strtotime("now")+18),"player_event"=>0,"enemy_event"=>0,"skill_cd"=>$skill_cd]);
			}
		}
	}else{
		echo 0;
	}


	$con->close();
?>