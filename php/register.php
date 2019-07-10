<?php
session_start();
if(!isset($_SESSION["access"])) {
    if (isset($_POST["name"])&&isset($_POST["token"])) {
        require_once("includes/connect_db.php");

        $url = "https://www.google.com/recaptcha/api/siteverify";
        $postvars='secret=6LeNlKMUAAAAABhIt_AV-hLqQddwD5tHH6RAp5NC';
        $postvars.="&response=".$_POST["token"];
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if($result->success&&$result->score>0) {

            $name = str_replace(' ', '', $_POST["name"]);
            $name = ucfirst(strtolower($name));
            $checker= $con->query("Select name from login where name='" . $name . "'");
            if ($checker->num_rows == 0) {
                if(preg_match("/^[a-zA-Z ]*$/",$name)&&strlen($name)>2&&strlen($name)<=12) {
                    $uid = uniqid();
                    $token = uniqid() . bin2hex(random_bytes(64));
                    $con->query("INSERT INTO `login` (`email`, `password`, `name`, `token`, `IP`) VALUES ('" . $uid . "', '', '" . $name . "', '" . $token . "', '" . $_SERVER['REMOTE_ADDR'] . "');");
                    setcookie("session", $token, time() + (86400 * 7), "/php/", "westfame.com",false,true);
                    $id = mysqli_fetch_row($con->query("SELECT id from login where token='" . $token . "' and IP='" . $_SERVER['REMOTE_ADDR'] . "'"))[0];
                    if ($id > 0) {
                        $_SESSION["access"] = $id;
                        $con->query("INSERT INTO user_statistic(id) value($id)");
                        $con->query("INSERT INTO quest_log(user,quest,value,persistent) value($id,1,1,1)");
                        echo 1;
                    }else{
                        echo "Something ain't going right. Try again later";
                    }
                }else{
                    echo "How the hell am I going to pronounce that? Say another name";
                }
            } else {
                echo "I already know a guy with this name. Say another name";
            }
        }else{
            echo "You does not look like human to me try again later";
        }
        $con->close();
    } else {
        echo 0;
    }
}else{
    echo 1;
}

?>