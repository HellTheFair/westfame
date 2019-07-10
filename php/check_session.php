<?php
	session_start();
    require_once("includes/connect_db.php");
    if(isset($_SESSION['access'])){
   /*     $cookie=mysqli_real_escape_string($con,$_COOKIE["session"]);
        $raw_temp=$con->query("SELECT `IP`,id FROM `login` WHERE id=".$_SESSION['access']." and `token`='".$cookie."'");
        $temp=mysqli_fetch_assoc($raw_temp);
       if($temp["id"]){*/
        echo 1;
       /* }else{
            unset($_SESSION["access"]);
            echo 0;
        }*/

    }elseif(isset($_COOKIE["session"])){
        $cookie=mysqli_real_escape_string($con,$_COOKIE["session"]);
        $raw_temp=$con->query("SELECT `IP`,id FROM `login` WHERE online=0 and `token`='".$cookie."'");
        $temp=mysqli_fetch_assoc($raw_temp);
        if($temp['id']){
            $_SESSION["access"]=$temp['id'];
            if($_SERVER["REMOTE_ADDR"]!=$temp['IP']){
                $token=uniqid().bin2hex( random_bytes(64) );
                setcookie("session",$token,time()+(86400*2),"/tester/php/","; httponly");
                $con->query("UPDATE login set token='".$token."', IP='".$_SERVER["REMOTE_ADDR"]."' where id=".$temp['id']);
            }
            echo 1;
        }else{
            unset($_SESSION["access"]);
            echo 0;
        }
    }else{
        unset($_SESSION["access"]);
        echo 0;
    }
    $con->close();

?>