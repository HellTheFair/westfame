<?php
	require_once("includes/connect_db.php");
	session_start();
	$raw_data=$con->query("SELECT `id`,`creator`,`acceptor` from `duel` WHERE (acceptor='".$_SESSION["access"]."' or creator='".$_SESSION["access"]."') and `started`=1 and `finished`=0 and last_update>now() ORDER BY `id` DESC");

	$data = mysqli_fetch_array($raw_data);

	if($raw_data->num_rows > 0){
		$raw_last_step = $con->query("SELECT `step` FROM `duel_id` WHERE duel=".$data["id"]." ORDER BY `step` DESC");
		$last_step = mysqli_fetch_row($raw_last_step)[0];
		$raw_prev_step = $con->query("SELECT `step` FROM `duel_id` WHERE duel=".$data["id"]." ORDER BY `step` DESC LIMIT 2");
		mysqli_fetch_row($raw_prev_step);
		$prev_step = mysqli_fetch_row($raw_prev_step)[0];
		if($last_step==1)$prev_step=1;

        $attack=0;
        $def=0;
        $skill=0;

		if(isset($_POST["attack"]))$attack = $_POST["attack"];
        if(isset($_POST["def"]))$def = $_POST["def"];
        if(isset($_POST["skill"]))$skill = $_POST["skill"];
		$roll=0;

		if($skill<=0||!(+$skill))$skill=0;
		if($attack<0||$attack>5||!(+$attack))$attack=0;
		if($def<0||$def>5||!(+$def))$def=0;
		if($last_step==1&&$def==0)$def=3;
		$raw_def_check = $con->query("SELECT `a_def`,`c_def` FROM `duel_id` WHERE duel=".$data["id"]." and step='".$prev_step."'");
		$def_check = mysqli_fetch_array($raw_def_check);

		// STEPS FOR ACCEPTOR

		if($_SESSION["access"]==$data["acceptor"]){				
			if($skill>0){
				$skill_fine=0;
				$raw_player_skill_check = $con->query("SELECT ".$skills_amount_text." FROM `login` where id=".$_SESSION["access"]);
				$raw_skill_check = $con->query("SELECT ".$effect_amount_text.",cd from `skill` where id='".$skill."'");
				
				$player_skill_check = mysqli_fetch_array($raw_player_skill_check);
				if($skill==1)$skill_fine=1;
				for($z=1;$z<=$skills_amount;$z++){
					if($player_skill_check["skill".$z]==$skill)$skill_fine=1;
				}
				$skill_check = mysqli_fetch_array($raw_skill_check);
				if( $skill_check[0] &&$skill_fine==1){
					$raw_cd_check = $con->query("SELECT `step` from `duel_id` where duel=".$data["id"]." and a_skill='".$skill."' and not step=".$last_step." ORDER BY step DESC LIMIT 1");
					$cd_check = mysqli_fetch_array($raw_cd_check);
					
					if($last_step==1||!$cd_check||$last_step-$cd_check["step"]>$skill_check["cd"]){
						
							//CHECK FOR ROLL EFFECT
						for($i=1;$i<=$effect_amount;$i++){	

							$raw_roll_check = $con->query("SELECT action from effect WHERE id='".$skill_check['effect'.$i]."'");
							$roll_check=mysqli_fetch_array($raw_roll_check);
							if($roll_check["action"]=="roll")$roll=1;
							
						}
					}else{
						$skill=0;
					}
				}else{
					$skill=0;
				}
			}

			if($def==0)$def=$def_check["a_def"];
			if($last_step==1)$def_check["a_def"]=3;
			if($def_check["a_def"]-$def>1&&!$roll)$def=$def_check["a_def"]-1;
			if($def_check["a_def"]-$def<-1&&!$roll)$def=$def_check["a_def"]+1;
            if(!!$roll){
                if($def_check["a_def"]-$def>2)$def=$def_check["a_def"]-2;
                if($def_check["a_def"]-$def<-2)$def=$def_check["a_def"]+2;
            }

			$con->query("UPDATE `duel_id` SET a_def='".$def."', a_attack='".$attack."', a_skill='".$skill."' WHERE duel=".$data["id"]." and step='".$last_step."'");

			//STEPS FOR CREATOR
		}elseif($_SESSION["access"]==$data["creator"]){
			if($skill>0){
				$skill_fine=0;
				$raw_player_skill_check = $con->query("SELECT ".$skills_amount_text." FROM `login` where id=".$_SESSION["access"]);
				$raw_skill_check = $con->query("SELECT ".$effect_amount_text.",cd  from `skill` where id='".$skill."'");
				
				$player_skill_check = mysqli_fetch_array($raw_player_skill_check);
				if($skill==1)$skill_fine=1;
				for($z=1;$z<=$skills_amount;$z++){
					if($player_skill_check["skill".$z]==$skill)$skill_fine=1;
				}
				$skill_check = mysqli_fetch_array($raw_skill_check);
				if( $skill_check[0] &&$skill_fine==1){
					$raw_cd_check = $con->query("SELECT `step` from `duel_id` where duel=".$data["id"]." and c_skill='".$skill."' and not step=".$last_step." ORDER BY step DESC LIMIT 1");
					$cd_check = mysqli_fetch_array($raw_cd_check);

					if($last_step==1||!$cd_check||$last_step-$cd_check["step"]>$skill_check["cd"]){

							//CHECK FOR ROLL EFFECT
						for($i=1;$i<=$effect_amount;$i++){		
							$raw_roll_check = $con->query("SELECT action from effect WHERE id='".$skill_check['effect'.$i]."'");
							$roll_check=mysqli_fetch_array($raw_roll_check);
							//if($skill_check['effect'.$i]==2)$roll=1;
							if($roll_check["action"]=="roll")$roll=1;
							
						}
					}else{
						$skill=0;
					}
				}else{
					$skill=0;
				}
			}
			
			if($def==0)$def=$def_check["c_def"];
			if($last_step==1)$def_check["c_def"]=3;
			if($def_check["c_def"]-$def>1&&!$roll)$def=$def_check["c_def"]-1;
			if($def_check["c_def"]-$def<-1&&!$roll)$def=$def_check["c_def"]+1;
			if(!!$roll){
                if($def_check["c_def"]-$def>2)$def=$def_check["c_def"]-2;
                if($def_check["c_def"]-$def<-2)$def=$def_check["c_def"]+2;
            }


			$con->query("UPDATE `duel_id` SET c_def='".$def."', c_attack='".$attack."', c_skill='".$skill."' WHERE duel=".$data["id"]." and step='".$last_step."'");
			
			
		}
	}else{
		echo 0;
	}
	

	$con->close();
?>