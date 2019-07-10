<?php
	require_once("includes/connect_db.php");
	session_start();
    if(isset($_SESSION["access"])){
        $user=$con->query("SELECT tutorial,level from login where id=".$_SESSION["access"])->fetch_assoc();
        $check_tutorial=$con->query("SELECT level from protocols where id=".$user["tutorial"])->fetch_assoc();
        if($check_tutorial["level"]<=$user["level"]) {
            $next_tutorial = $con->query("SELECT id from protocols where type='tutorial' and id>(SELECT tutorial from login where id=" . $_SESSION["access"] . ")")->fetch_assoc();
            $con->query("UPDATE login set tutorial=".$next_tutorial["id"]." where id=" . $_SESSION["access"]);
            echo 1;
        }else{
            echo "You couldn't have completed this tutorial";
        }
    }else{
        echo 0;
    }
	$con->close();

?>