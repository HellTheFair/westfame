<?php
	require_once("includes/connect_db.php");
	session_start();
	if($_SESSION['access']&&$_POST["item"]){
		$duel_check=$con->query("SELECT id FROM `duel` WHERE (`creator`=".$_SESSION["access"]." or `acceptor`=".$_SESSION["access"].") and started=1 and finished=0")->fetch_row();
		if(!$duel_check) {
			if (!is_numeric($_POST["item"])) $_POST["item"] = 0;
			$check_inv = $con->query("SELECT type from inventory where item=" . $_POST["item"] . " and (user=" . $_SESSION["access"] . " or user=0)");

			if ($check_inv_type = mysqli_fetch_row($check_inv)[0]) {
				if ($check_inv_type == "weapon") {
					$prev_item = mysqli_fetch_assoc($con->query("SELECT " . $weapon_effect_amount_text . " from knowledge_base where id=(SELECT weapon from login where id=" . $_SESSION['access'] . ")"));

					$con->query("UPDATE login set weapon=" . $_POST["item"] . " where id=" . $_SESSION["access"]);
					$inv_f_hp = mysqli_fetch_assoc($con->query("SELECT " . $weapon_effect_amount_text . " from knowledge_base where id=" . $_POST["item"]));
					for ($i = 1; $i <= $weapon_effect_amount; $i++) {
						$inv_effect = mysqli_fetch_assoc($con->query("SELECT action,percentage from effect where id=" . $prev_item["effect" . $i]));
						if ($inv_effect["action"] == "health") {
							$con->query("UPDATE login set max_hp=round(max_hp/" . ($inv_effect["percentage"] / 100) . "), hp=round(hp + max_hp - max_hp*" . ($inv_effect["percentage"] / 100) . ") WHERE id=" . $_SESSION["access"]);
						}
					}
					for ($i = 1; $i <= $weapon_effect_amount; $i++) {
						$inv_effect = mysqli_fetch_assoc($con->query("SELECT action,percentage from effect where id=" . $inv_f_hp["effect" . $i]));
						if ($inv_effect["action"] == "health") {
							$con->query("UPDATE login set max_hp=round(max_hp*" . ($inv_effect["percentage"] / 100) . "), hp=round(hp + (max_hp - max_hp/" . ($inv_effect["percentage"] / 100) . ")) WHERE id=" . $_SESSION["access"]);
						}
					}
					$con->query("UPDATE login set hp=max_hp where hp>max_hp and id=" . $_SESSION["access"]);
				}
				if ($check_inv_type == "skin") $con->query("UPDATE login set skin=" . $_POST["item"] . " where id=" . $_SESSION["access"]);
				if ($check_inv_type == "title") $con->query("UPDATE login set title=" . $_POST["item"] . " where id=" . $_SESSION["access"]);
			} else {
				echo "No such item in your inventory";
			}
		}else{
			echo "You can't equip items during duels";
		}
	}else{	
		echo 0;
	}
	$con->close();

?>	