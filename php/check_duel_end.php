<?php
	require_once("includes/connect_db.php");
	session_start();
	

	$raw_data=$con->query("SELECT `acceptor`,`creator`,`id`,`last_update` FROM `duel` WHERE (`creator`='".$_SESSION["access"]."' or `acceptor`='".$_SESSION["access"]."') and finished>0 ORDER by `last_update` DESC LIMIT 1");

	if($raw_data->num_rows>0){
	    $data=mysqli_fetch_assoc($raw_data);
        if (strtotime("now")+$time_differ-strtotime($data["last_update"])>=-12&&strtotime("now")+$time_differ-strtotime($data["last_update"])<15) {
            $raw_duel_data = $con->query("SELECT `step`,`a_hp`,`c_hp`,`a_ammo`,`c_ammo`,`time`,`a_event`,`c_event` FROM `duel_id` WHERE duel=".$data["id"]." ORDER by step DESC LIMIT 1");
            $duel_data = mysqli_fetch_assoc($raw_duel_data);
            $raw_prev_step = $con->query("SELECT `a_def`,`c_def`,`a_attack`,`c_attack` FROM `duel_id` WHERE duel=".$data["id"]." and step='" . (+$duel_data["step"] - 1) . "'");
            $raw_max_hp = $con->query("SELECT `a_hp`,`c_hp` FROM `duel_id` WHERE duel=".$data["id"]." and step=1");
            $max_hp = mysqli_fetch_assoc($raw_max_hp);
            $prev_step = mysqli_fetch_assoc($raw_prev_step);

            $duel_data["c_event"] = str_replace("&", "", $duel_data["c_event"]);
            $duel_data["a_event"] = str_replace("&", "", $duel_data["a_event"]);
            foreach($system_events as $key => $value){
                $duel_data["c_event"]=str_replace(";".$value,"",$duel_data["c_event"]);
                $duel_data["a_event"]=str_replace(";".$value,"",$duel_data["a_event"]);
            }
            $duel_data["c_event"] = explode(";", $duel_data["c_event"]);
            array_splice($duel_data["c_event"], 0, 1);
            $duel_data["a_event"] = explode(";", $duel_data["a_event"]);
            array_splice($duel_data["a_event"], 0, 1);

            header("Content-type: application/json");

            if ($_SESSION["access"] == $data["acceptor"]) {
                $player["name"] = "a";
                $player["enemy"] = "c";
                $player["enemy_id"] = $data["creator"];
            } elseif ($_SESSION["access"] == $data["creator"]) {
                $player["name"] = "c";
                $player["enemy"] = "a";
                $player["enemy_id"] = $data["acceptor"];
            }

            $enemy = mysqli_fetch_assoc($con->query("SELECT name FROM `login` WHERE `id`=" . $player["enemy_id"]));
            echo json_encode(["name" => $enemy["name"], "player_position" => $prev_step[$player["name"] . "_def"], "enemy_position" => $prev_step[$player["enemy"] . "_def"], "player_attack" => $prev_step[$player["name"] . "_attack"], "enemy_attack" => $prev_step[$player["enemy"] . "_attack"], "player_max_hp" => $max_hp[$player["name"] . "_hp"], "player_hp" => $duel_data[$player["name"] . "_hp"], "enemy_hp" => $duel_data[$player["enemy"] . "_hp"], "enemy_max_hp" => $max_hp[$player["enemy"] . "_hp"], "ammo" => $duel_data[$player["name"] . "_ammo"], "time" => (strtotime($duel_data["time"]) - strtotime("now")+$time_differ), "player_event" => $duel_data[$player["name"] . "_event"], "enemy_event" => $duel_data[$player["enemy"] . "_event"]]);
        }else{
            echo 0;
        }
    }else{
        echo 0;
    }

	$con->close();
?>