<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$skill_data=[];
		$next_level=0;
		$gold_to_level=0;
		$raw_user_data = $con->query("SELECT * from `login` where `id`='".$_SESSION['access']."'");
		$user_data=mysqli_fetch_assoc($raw_user_data);
		$raw_skin = $con->query("SELECT picture from knowledge_base where id=".$user_data["skin"]);
		$skin=mysqli_fetch_assoc($raw_skin)["picture"];
		$raw_weapon = $con->query("SELECT picture,twohand from knowledge_base where id=".$user_data["weapon"]);
		$weapon=mysqli_fetch_assoc($raw_weapon);
		
		for($z=1;$z<$skills_amount;$z++){
			if($user_data["skill".$z]!=0){
				$raw_skill_data = $con->query("SELECT `id`,`name`,`cd`,`description`,`picture`,`passive` from `skill` WHERE id=".$user_data["skill".$z]);
				$skill_data[count($skill_data)]=mysqli_fetch_assoc($raw_skill_data);
			
			}
		}
		$next_level_hp=$user_data['hp'];
		if(+$user_data["level"]<$max_level){
			$next_level=+$user_data["level"] + 1;
			$gold_to_level=($default_reward*$gold_coef*$user_data["level"]+$default_reward)*ceil($user_data["level"]/$level_coef);
			$next_level_hp=$start_hp+$hp_coef*$user_data["level"];
			$next_skills=mysqli_fetch_row($con->query("SELECT count(*) from skill where level=".$next_level))[0];
		}

		$shop_misc=$con->query("SELECT `id`,`name`,`description`,`picture`,`price` From knowledge_base where type='misc' and sellable=1 and level<=".$user_data["level"]." ORDER BY level DESC")->fetch_All(MYSQLI_ASSOC);
		$shop_skin=$con->query("SELECT `id`,`name`,`description`,`picture`,`price` From knowledge_base where type='skin' and sellable=1 and level<=".$user_data["level"]." ORDER BY level DESC")->fetch_All(MYSQLI_ASSOC);
		
		$text_col="eff1.name as effect1";
		$text_join="LEFT JOIN effect eff1 on asd.effect1=eff1.id";
		for($i=2;$i<=$weapon_effect_amount;$i++){
			$text_col.=",eff".$i.".name as effect".$i;
			$text_join.=" LEFT JOIN effect eff".$i." on asd.effect".$i."=eff".$i.".id";
		}
		
		$shop_weapon=$con->query("SELECT asd.`id`,asd.`name`,asd.`description`,asd.`picture`,asd.`price`,asd.min_dmg,asd.max_dmg,asd.ammo,asd.chance_to_crit,".$text_col." FROM knowledge_base asd ".$text_join." WHERE asd.type='weapon' and asd.sellable=1 and asd.level between ".($user_data["level"]-3)." and ".$user_data["level"]." ORDER BY asd.level DESC")->fetch_All(MYSQLI_ASSOC);


		$response=['name'=>$user_data["name"],'level'=>$user_data["level"],'next_level'=>$next_level,'next_level_hp'=>$next_level_hp,'max_hp'=>$user_data['max_hp'],'hp'=>$user_data['hp'],'next_skills'=>$next_skills,'gold_to_level'=>$gold_to_level,'gold'=>$user_data['gold'],'skills'=>$skill_data,"skin"=>$weapon["twohand"]."/".$skin,"weapon_skin"=>$weapon["picture"],"shop_misc"=>$shop_misc,"shop_skin"=>$shop_skin,"shop_weapon"=>$shop_weapon,"weapon_effect_amount"=>$weapon_effect_amount];
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>