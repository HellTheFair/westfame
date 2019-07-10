<?php
	require_once("includes/connect_db.php");
	session_start();
	$raw_duel=$con->query("SELECT `id`,`acceptor`,`creator` from `duel` WHERE (acceptor='".$_SESSION["access"]."' or creator='".$_SESSION["access"]."') and `started`=1 and `finished`=0 ORDER BY `id` DESC");

	if($raw_duel->num_rows>0){
        $duel = mysqli_fetch_assoc($raw_duel);
        $duel_data = mysqli_fetch_assoc($con->query("SELECT * FROM `duel_id` where duel=".$duel["id"]." ORDER by step DESC LIMIT 1"));
        if($duel_data["step"]>1) {
            $prev_step = mysqli_fetch_assoc($con->query("SELECT `a_def`,`c_def`,`a_attack`,`c_attack` FROM `duel_id` WHERE duel=".$duel["id"]." and step='" . (+$duel_data["step"] - 1) . "'"));
        }else{
            $prev_step = mysqli_fetch_assoc($con->query("SELECT `a_def`,`c_def`,`a_attack`,`c_attack` FROM duel_id WHERE duel=0 and step=0"));
        }

		$c_data = mysqli_fetch_assoc($con->query("SELECT id,weapon,max_hp,".$skills_amount_text." FROM login WHERE id=".$duel["creator"]));
		$a_data = mysqli_fetch_assoc($con->query("SELECT id,weapon,max_hp,".$skills_amount_text." FROM login WHERE id=".$duel["acceptor"]));
		$c_data["name"]="c";
        $a_data["name"]="a";
		$skill_cd=[];
		header("Content-type: application/json");

		if($_SESSION["access"]==$duel["acceptor"]) {
            $player=&$a_data;
            $player["enemy"]=&$c_data;

        }elseif($_SESSION["access"]==$duel["creator"]) {
            $player=&$c_data;
            $player["enemy"]=&$a_data;
		}
        $raw_enemy = $con->query("SELECT `login`.`name`,`knowledge_base`.`picture`,`knowledge_base`.`twohand` FROM `login`,`knowledge_base` WHERE `login`.`id`='".$player["enemy"]["id"]."' and `knowledge_base`.`id`=`login`.`skin`");
		$enemy_twohand = $con->query("SELECT twohand from knowledge_base where id=".$player["enemy"]["weapon"])->fetch_assoc()["twohand"];
        $enemy = mysqli_fetch_assoc($raw_enemy);

        for($z=1;$z<=$skills_amount;$z++){
            if($player["skill".$z]!=0){
                $skill_data = mysqli_fetch_assoc($con->query("SELECT `id`,`cd` from `skill` WHERE id=".$player["skill".$z]));
                $raw_cd_check = $con->query("SELECT `step` from `duel_id` where duel=".$duel["id"]." and ".$player["name"]."_skill='".$skill_data['id']."' and not step=".$duel_data["step"]." ORDER BY step DESC LIMIT 1");
                $cd_check = mysqli_fetch_assoc($raw_cd_check);
                if($raw_cd_check->num_rows==0||$duel_data["step"]-$cd_check["step"]>$skill_data["cd"]){
                    $skill_cd[]=0;
                }else{
                    $skill_cd[]=$skill_data["cd"]-($duel_data["step"]-$cd_check["step"])+1;
                }
            }

        }

        if($duel_data["step"]!=1){
            $time=strtotime($duel_data["time"]) - strtotime("now")-$time_differ;
        }else{
            $time=strtotime($duel_data["time"]) - strtotime("now")+18-$time_differ;
        }
        $player["event"]=$duel_data[$player["name"]."_event"];
        $player["enemy"]["event"]=$duel_data[$player["enemy"]["name"]."_event"];
        $player["event"]=str_replace("&","",$player["event"]);
        $player["enemy"]["event"]=str_replace("&","",$player["enemy"]["event"]);
        foreach($system_events as $key => $value){
            $player["event"]=str_replace(";".$value,"",$player["event"]);
            $player["enemy"]["event"]=str_replace(";".$value,"",$player["enemy"]["event"]);
        }
        $player["event"]=explode(";",$player["event"]);
        array_splice($player["event"], 0, 1);
        $player["enemy"]["event"]=explode(";",$player["enemy"]["event"]);
        array_splice($player["enemy"]["event"], 0, 1);

        echo json_encode(["name"=>$enemy["name"],"skin"=>$enemy_twohand."/".$enemy["picture"], "player_position"=>$prev_step[$player["name"]."_def"], "enemy_position"=>$prev_step[$player["enemy"]["name"]."_def"], "player_attack"=>$prev_step[$player["name"]."_attack"], "enemy_attack"=>$prev_step[$player["enemy"]["name"]."_attack"], "player_max_hp"=>$player["max_hp"], "player_hp"=>$duel_data[$player["name"]."_hp"], "enemy_hp"=>$duel_data[$player["enemy"]["name"]."_hp"], "enemy_max_hp"=>$player["enemy"]["max_hp"], "ammo"=>$duel_data[$player["name"]."_ammo"],"time"=>$time,"player_event"=>$player["event"],"enemy_event"=>$player["enemy"]["event"],"skill_cd"=>$skill_cd]);

	}else{
		echo 0;
	}


	$con->close();
?>