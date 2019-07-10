<?php
	require_once("includes/connect_db.php");
	error_reporting(E_NOTICE);
	session_start();
	if(!$_SESSION['access']){
		echo 0;
	}else{
        $last_loop=mysqli_fetch_assoc($con->query("Select last_use from oddmans_timing where action='loop'"));
        if($last_loop["last_use"]>=round(microtime(true) * 1000-10000)) {
            $checker = $con->query("SELECT `id` from `duel` WHERE `creator`=" . $_SESSION['access'] . " and `finished`=0")->fetch_all();
            if (!$checker) {
                $con->query("INSERT INTO `duel`(creator) values('" . $_SESSION['access'] . "')");
                echo 1;
            } else {
                $con->query("UPDATE `duel` SET `finished`=-1 WHERE `creator`=" . $_SESSION['access'] . " and `started`=0 and `finished`=0");
                echo 0;
            }
        }else{
          echo "server down";
        }
		
	}
	$con->close();

?>