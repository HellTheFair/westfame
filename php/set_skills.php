<?php
	require_once("includes/connect_db.php");
	session_start();
	if($_SESSION['access']&&$_POST["skills"]){
		$duel_check=$con->query("SELECT id FROM `duel` WHERE (`creator`=".$_SESSION["access"]." or `acceptor`=".$_SESSION["access"].") and started=1 and finished=0")->fetch_row();
		if(!$duel_check) {
			$skills_temp = explode(" ", $_POST["skills"]);
			$skills = [];

			$user = mysqli_fetch_assoc($con->query("SELECT level from login where id=" . $_SESSION['access']));

			for ($i = 0; $i < $skills_amount; $i++) {
				if (!isset($skills_temp[$i]) || !is_numeric($skills_temp[$i]) || +$skills_temp[$i] <= 1) {
					$skills[$i] = 0;
				} else {
					$skills[$i] = $skills_temp[$i];
					if (($i - 1) * $skill_unlock_level > $user["level"]) $skills[$i] = 0;
				}
			}

			$text_col = "eff1.action as effect1,eff1.id as effect1id";
			$text_join = "LEFT JOIN effect eff1 on skill.effect1=eff1.id";
			for ($i = 2; $i <= $effect_amount; $i++) {
				$text_col .= ",eff" . $i . ".action as effect" . $i . ",eff" . $i . ".id as effect" . $i . "id";
				$text_join .= " LEFT JOIN effect eff" . $i . " on skill.effect" . $i . "=eff" . $i . ".id";
			}
			$text_skills = implode(",", $skills);
			$user_skills = mysqli_fetch_row($con->query("SELECT " . $skills_amount_text . " from login where id=" . $_SESSION['access']));
			$user_effects = $con->query("SELECT skill.id,skill.passive," . $text_col . " from skill " . $text_join . " where skill.id in (" . implode(",", $user_skills) . ")")->fetch_All(MYSQLI_ASSOC);
			for ($i = 0; $i < count($user_effects); $i++) {
				if ($user_effects[$i]["id"] != 0 && $user_effects[$i]["passive"] == 1) {
					for ($j = 1; $j <= $effect_amount; $j++) {
						if ($user_effects[$i]["effect" . $j] == "health") {
							$percentage = mysqli_Fetch_row($con->query("SELECT percentage from effect where id=" . $user_effects[$i]["effect" . $j . "id"]))[0];
							$con->query("UPDATE login set max_hp=round(max_hp/" . ($percentage / 100) . "), hp=round(hp + max_hp - max_hp*" . ($percentage / 100) . ") where id=" . $_SESSION["access"]);
							$con->query("UPDATE login set hp=max_hp where hp>max_hp and id=" . $_SESSION["access"]);
						}
					}
				}
			}
			$check_skills = $con->query("SELECT skill.id,skill.passive,skill.level," . $text_col . " from skill " . $text_join . " where level<=" . $user["level"] . " and skill.id in (" . $text_skills . ")")->fetch_All(MYSQLI_ASSOC);
			$ar_update = [];
			for ($i = 0; $i < count($check_skills); $i++) {
				$pos = array_search($check_skills[$i]["id"], $skills);
				$ar_update[$pos] = $check_skills[$i]["id"];
				if ($check_skills[$i]["id"] != 0 && $check_skills[$i]["passive"] == 1) {
					for ($j = 1; $j <= $effect_amount; $j++) {
						if ($check_skills[$i]["effect" . $j] == "health") {
							$percentage = mysqli_Fetch_row($con->query("SELECT percentage from effect where id=" . $check_skills[$i]["effect" . $j . "id"]))[0];
							$con->query("UPDATE login set max_hp=round(max_hp*" . ($percentage / 100) . "), hp=round(hp + (max_hp - max_hp/" . ($percentage / 100) . ")) where id=" . $_SESSION["access"]);
						}
					}
				}
			}

			for ($i = 0; $i < $skills_amount; $i++) {
				if (!isset($ar_update[$i])) $ar_update[$i] = 0;
			}
			$set_skills = "skill1=" . $ar_update[0];
			for ($i = 2; $i <= $skills_amount; $i++) {
				$set_skills .= ",skill" . $i . "=" . $ar_update[$i - 1];
			}
			$con->query("UPDATE login set " . $set_skills . " where id=" . $_SESSION["access"]);
			echo 1;
		}else{
			echo "You can't change skills during duels";
		}
	}else{	
		echo 0;
	}
	$con->close();

?>	