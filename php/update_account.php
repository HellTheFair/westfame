<?php
	require_once("includes/connect_db.php");
	session_start();

	function checkUnique($field,$value){
	    global $con;
	    $checker=$con->query("SELECT id from login where ".$field."='".$value."'");
	    if($checker->num_rows==0){
	        return 1;
        }else{
	        return 0;
        }
    }

	if($_SESSION["access"]){
		$user=mysqli_fetch_assoc($con->query("SELECT status,password from login where id=".$_SESSION["access"]));
        if($user["status"]==0){
            if(isset($_POST["email"])&&strlen($_POST["email"])>0&&isset($_POST["password"])&&strlen($_POST["password"])>0){
                $check_email=mysqli_fetch_assoc($con->query("SELECT id from login where email='".$_POST["email"]."'"))["id"];
                if(filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                    if (checkUnique("email", $_POST["email"])) {
                        if(strlen($_POST["password"])>=6) {
                            $con->query("UPDATE login set email='" . $_POST["email"] . "', password='" . password_hash($_POST["password"], PASSWORD_BCRYPT, ["cost" => 10]) . "', status=1 WHERE id=" . $_SESSION["access"]);
                            $token = uniqid() . bin2hex(random_bytes(32));
                            $con->query("INSERT into email_update(user,email,token) VALUE(".$_SESSION["access"].",'".$_POST["email"]."','".$token."')");

                            $msg = 'To confirm your email of West Fame account please follow the link: https://westfame.com/php/confirm_email.php?email='.$_POST["email"].'&token='.$token;

                            $headers = 'From: hel@westfame.com' . "\r\n" .
                                'Return-Path: hel@westfame.com' . "\r\n";

                            echo mail($_POST["email"], "West's Fame account verification", $msg,$headers) ? 1 : "We could not send mail to that email please contact user support";

                        }else{
                            echo "Entered password is too short (minimum 6 characters)";
                        }
                    } else {
                        echo "Entered email is already being used";
                    }
                }else{
                    echo "Entered email is not valid";
                }
            }else{
                echo "Please fill up both fields";
            }
        }else{
            if(isset($_POST["current_password"])){
                if(password_verify($_POST["current_password"],$user["password"])){
                    $new_status=0;
                    $update_text="";
                    if(isset($_POST["email"])&&$_POST["email"]!=''){
                        if(filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                            if (checkUnique("email", $_POST["email"])) {
                                //$update_text.=" email='".$_POST["email"]."' ";
                                $new_status = $user["status"] == $vip_status ? $vip_status + 1 : $user["status"] - 1;
                                $token = uniqid() . bin2hex(random_bytes(32));
                                $con->query("DELETE from email_update where user=". $_SESSION["access"]);
                                $con->query("INSERT into email_update(user,email,token) VALUE(" . $_SESSION["access"] . ",'" . $_POST["email"] . "','" . $token . "')");

                                $msg = 'To confirm your email of West Fame account please follow the link: https://westfame.com/php/confirm_email.php?email='.$_POST["email"].'&token='.$token;

                                $headers = 'From: hel@westfame.com' . "\r\n" .
                                    'Return-Path: hel@westfame.com' . "\r\n";

                                echo mail($_POST["email"], "West's Fame account verification", $msg,$headers) ? 1 : "We could not send mail to that email please contact user support";

                            } else {
                                echo " Entered email is already being used. ";
                            }
                        }else{
                            echo "Entered email is not valid";
                        }
                    }
                    if(isset($_POST["name"])&&$_POST["name"]!='') {
                        $name = str_replace(' ', '', $_POST["name"]);
                        $name = ucfirst(strtolower($name));
                        if(preg_match("/^[a-zA-Z ]*$/",$name)&&strlen($name)>2&&strlen($name)<=12) {
                            if (checkUnique("name", $name)) {
                                if (strlen($update_text) > 0) $update_text .= ", ";
                                $update_text .= " name='" . $name . "' ";
                            } else {
                                echo " Entered name is already being used. ";
                            }
                        }else{
                            echo " Invalid characters in name";
                        }
                    }
                    if(isset($_POST["password"])&&$_POST["password"]!='') {
                        if(strlen($_POST["password"])>=6){
                            if(strlen($update_text)>0)$update_text.=", ";
                            $update_text.=" password='".password_hash($_POST["password"], PASSWORD_BCRYPT, ["cost" => 10])."' ";
                        }else{
                            echo " Entered password is too short (minimum 6 characters).";
                        }
                    }
                    $con->query("UPDATE login set ".$update_text." WHERE id=".$_SESSION["access"]);
                    if($new_status>0)$con->query("UPDATE login set status=".$new_status." WHERE not status = ".$vip_status." and id=".$_SESSION["access"]);
                    echo 1;
                }else{
                    echo "Failed to confirm your current password";
                }
            }else{
                echo "Enter your current password";
            }
        }
	}else{
		echo "Fill up all inputs";
	}
	$con->close();

?>