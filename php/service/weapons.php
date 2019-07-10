<?php
require_once("../php/includes/connect_db.php");

	for($i=1;$i<=50;$i++){
		echo "<br>".$i."<br>";
		$price=($default_reward*$gold_coef*$i+$default_reward)*ceil($i/$level_coef)/2;
		$rev_min=round(($start_hp+$hp_coef*($i-1))*0.25-($start_hp+$hp_coef*($i-1))*0.25*0.1);
		$rev_max=round(($start_hp+$hp_coef*($i-1))*0.25+($start_hp+$hp_coef*($i-1))*0.25*0.1);
		$winch_min=round(($start_hp+$hp_coef*($i-1))*0.45-($start_hp+$hp_coef*($i-1))*0.45*0.15);
		$winch_max=round(($start_hp+$hp_coef*($i-1))*0.45+($start_hp+$hp_coef*($i-1))*0.45*0.15);
		$sg_min=round(($start_hp+$hp_coef*($i-1))*0.34-($start_hp+$hp_coef*($i-1))*0.34*0.2);
		$sg_max=round(($start_hp+$hp_coef*($i-1))*0.34+($start_hp+$hp_coef*($i-1))*0.34*0.2);		
		if($i%5==0){			
			$con->query("INSERT INTO knowledge_base values(null,'weapon',null,'Revolver".$i."','generated weapon','default_weapon.png',0,".$rev_min.",".$rev_max.",6,".$i.",5,0,0,0,".$price.",1),(null,'weapon',null,'Winchester".$i."','generated weapon','default_winch.png',1,".$winch_min.",".$winch_max.",1,".$i.",5,0,0,0,".$price.",1),(null,'weapon',null,'Shotgun".$i."','generated weapon','default_sg.png',1,".$sg_min.",".$sg_max.",1,".$i.",5,0,0,0,".$price.",1)");
		}elseif($i%4==0){
			$con->query("INSERT INTO knowledge_base values(null,'weapon',null,'Revolver".$i."','generated weapon','default_weapon.png',0,".$rev_min.",".$rev_max.",6,".$i.",5,0,0,0,".$price.",1),(null,'weapon',null,'Shotgun".$i."','generated weapon','default_sg.png',1,".$sg_min.",".$sg_max.",1,".$i.",5,0,0,0,".$price.",1)");
		}elseif($i%3==0){
			$con->query("INSERT INTO knowledge_base values(null,'weapon',null,'Revolver".$i."','generated weapon','default_weapon.png',0,".$rev_min.",".$rev_max.",6,".$i.",5,0,0,0,".$price.",1),(null,'weapon',null,'Winchester".$i."','generated weapon','default_winch.png',1,".$winch_min.",".$winch_max.",1,".$i.",5,0,0,0,".$price.",1)");
		}elseif($i%2==0){
			$con->query("INSERT INTO knowledge_base values,(null,'weapon',null,'Winchester".$i."','generated weapon','default_winch.png',0,".$winch_min.",".$winch_max.",1,".$i.",5,0,0,0,".$price.",1),(null,'weapon',null,'Shotgun".$i."','generated weapon','default_sg.png',1,".$sg_min.",".$sg_max.",1,".$i.",5,0,0,0,".$price.",1)");
		}else{
			$con->query("INSERT INTO knowledge_base values(null,'weapon',null,'Revolver".$i."','generated weapon','default_weapon.png',0,".$rev_min.",".$rev_max.",6,".$i.",5,0,0,0,".$price.",1)");
		}
	}
?>