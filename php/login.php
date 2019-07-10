<?php
	require_once("includes/connect_db.php");
	session_start();

	if(isset($_SESSION['access'])){
		echo 1;
	}else{
	    if(isset($_POST['login'])&&isset($_POST['password'])) {
            $login = mysqli_real_escape_string($con, $_POST['login']);
            $password = mysqli_real_escape_string($con, $_POST['password']);
            $checker = mysqli_fetch_assoc($con->query("SELECT `id`,`password` from `login` where (`email`='" . $login . "' or `name`='".$login."') and `online`=0 and status>=0"));

            if (!!$checker && password_verify($password, $checker["password"])) {
                $token = uniqid() . bin2hex(random_bytes(64));
                $_SESSION['access'] = $checker['id'];
                $con->query("UPDATE `login` set `token`='" . $token . "',`IP`='" . $_SERVER['REMOTE_ADDR'] . "' WHERE `id`=" . $_SESSION["access"]);
                setcookie("session", $token, time() + (86400 * 7), "/php/", "westfame.com",false,true);
                echo 1;
            } else {
                echo 0;
            }
        }else{
	        echo 0;
        }
	}

	$con->close();

?>