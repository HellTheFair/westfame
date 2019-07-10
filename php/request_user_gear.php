<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$user_skills=[];
		$user_data=mysqli_fetch_assoc($con->query("SELECT decoration_wall,decoration_floor,decoration_table,level,title,hp,max_hp,skin,weapon,".$skills_amount_text." from `login` where `id`=".$_SESSION['access']));

		$raw_skin = $con->query("SELECT picture from knowledge_base where id=".$user_data["skin"]);
		$skin=mysqli_fetch_assoc($raw_skin)["picture"];
		$raw_weapon = $con->query("SELECT id,picture,twohand from knowledge_base where id=".$user_data["weapon"]);
		$weapon=mysqli_fetch_assoc($raw_weapon);
		$raw_title = $con->query("SELECT name from knowledge_base where id=".$user_data["title"]);
		$title=mysqli_fetch_assoc($raw_title)["name"];
		$decor_array=explode(" ", $user_data["decoration_wall"]." ".$user_data["decoration_floor"]." ".$user_data["decoration_table"]);

		for($z=1;$z<=$skills_amount;$z++){
            if(($z-1)*$skill_unlock_level<=$user_data["level"]){
                if($user_data["skill".$z]>=0){
                    $raw_user_skills = $con->query("SELECT `id`,`name`,`cd`,`description`,`picture`,`passive`,`level` from `skill` WHERE id=".$user_data["skill".$z]);
                    $user_skills[]=mysqli_fetch_assoc($raw_user_skills);
                }
            }else{
                $user_skills[]=["id"=>0,"name"=>"skill №$z","description"=>"This skill slot unlocks at level ".(($z-1)*$skill_unlock_level),"level"=>(($z-1)*$skill_unlock_level)];
            }
		}
		$decoration=[];
		for($z=0;$z<$decoration_amount;$z++){
            $decoration_raw=$con->query("SELECT description,id,subtype as type,name,picture FROM knowledge_base base where id =".$decor_array[$z]);
            $decoration[]=mysqli_fetch_assoc($decoration_raw);
        }

        $next_level_hp=$user_data['max_hp'];
        if(+$user_data["level"]<$max_level){
            $next_level_hp=($user_data['max_hp']/($start_hp+$hp_coef*($user_data["level"]-1)))*($start_hp+$hp_coef*$user_data["level"]);
        }

		$response=['title'=>$title, 'next_level_hp'=>$next_level_hp, 'max_hp'=>$user_data['max_hp'],'hp'=>$user_data['hp'],'user_skills'=>$user_skills,"decoration"=>$decoration,"weapon_equipped"=>$weapon["id"],"twohand"=>$weapon["twohand"],"skin"=>$skin,"weapon_skin"=>$weapon["picture"]];
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>