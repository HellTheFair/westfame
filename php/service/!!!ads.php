<?php

require_once("includes/connect_db.php");
session_start();

if(!$_SESSION['access']){
    echo 0;
}else {
    $user_data=mysqli_fetch_assoc($con->query("SELECT level,tutorial from login where id=".$_SESSION["access"]));
    $tutorials=$con->query("SELECT id from protocols where id>".$user_data["tutorial"]." and level <=".$user_data["level"]." and type='tutorial' ORDER BY id asc");
    if($current_tutorial = mysqli_fetch_row($tutorials)[0]) {
        $con->query("UPDATE login set tutorial=".$current_tutorial." where id=".$_SESSION["access"]);
    }
    $next_tutorial = mysqli_fetch_row($tutorials)[0];
    echo $next_tutorial;
}

$con->close();

?>