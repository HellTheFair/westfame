<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']&&!$_POST['target']){
		echo 0;
	}else{
		$user_data=mysqli_fetch_assoc($con->query("SELECT decoration_wall,decoration_floor,decoration_table,level,title,hp,max_hp,skin,weapon,".$skills_amount_text." from `login` where `id`=".$_POST['target']));
		$raw_skin = $con->query("SELECT picture from knowledge_base where id=".$user_data["skin"]);
		$skin=mysqli_fetch_assoc($raw_skin)["picture"];
		$raw_weapon = $con->query("SELECT picture,twohand from knowledge_base where id=".$user_data["weapon"]);
		$weapon=mysqli_fetch_assoc($raw_weapon);
		$decor_array=explode(" ", $user_data["decoration_wall"]." ".$user_data["decoration_floor"]." ".$user_data["decoration_table"]);
		$decoration=[];
		for($z=0;$z<$decoration_amount;$z++){
            $decoration_raw=$con->query("SELECT description,id,subtype as type,name,picture FROM knowledge_base base where id =".$decor_array[$z]);
            $decoration[]=mysqli_fetch_assoc($decoration_raw);
        }

		$response=["decoration"=>$decoration,"twohand"=>$weapon["twohand"],"skin"=>$skin,"weapon_skin"=>$weapon["picture"]];
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>