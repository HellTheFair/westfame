<?php
	require_once("includes/connect_db.php");
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
		$next_level=0;
		$gold_to_level=0;
		$user_data=mysqli_fetch_assoc($con->query("SELECT name,email,password,status,added from `login` where `id`='".$_SESSION['access']."'"));
        $status=mysqli_fetch_assoc($con->query("SELECT name,description from `player_status` where `id`='".$user_data["status"]."'"));
        $credentials=0;
		if(+$user_data["status"]==0){
            $credentials=1;
            $user_data["email"]="";
		}

        $status["description"]=str_replace("{d}",date('d F h:s',strtotime('+3 day',strtotime($user_data["added"]))),$status["description"]);

		$response=['name'=>$user_data["name"],'email'=>$user_data["email"],'credentials'=>$credentials,'status'=>$status,"creation_date"=>$user_data["added"]];
		header("Content-type: application/json");
		echo json_encode($response);
	}
	$con->close();

?>