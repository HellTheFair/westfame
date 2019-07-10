<?php
    require_once("includes/connect_db.php");
	session_start();
	session_unset();
	session_destroy();
	if(isset($_COOKIE["session"])){
		$con->query("UPDATE login set token='' where token='".$_COOKIE["session"]."'");
		setcookie("session",'123',time()-100000000,
			'/tester/php/');
	}
	header("Location: /tester");
	echo 0;
	$con->close();

?>