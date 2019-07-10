<?php
	require_once("includes/connect_db.php");

	if(isset($_GET["email"])&&isset($_GET["token"])){
        $checker = mysqli_fetch_assoc($con->query("SELECT mail.id,mail.user,mail.email,login.status as old_status from login,`email_update` mail where mail.`email`='" . $_GET["email"] . "' and mail.`token`='".$_GET["token"]."' and mail.user=login.id"));
        if(!!$checker){
            $con->query("UPDATE `login` set `email`='" . $checker["email"] . "' WHERE `id`=" . $checker["user"]);
            $con->query("UPDATE `login` set `status`=3 WHERE status<3 and `id`=" . $checker["user"]);
            $con->query("UPDATE `login` set `status`=$vip_status WHERE status>$vip_status and `id`=" . $checker["user"]);
            $con->query("Delete from email_update where id=".$checker["id"]);

            header("Location: /");
            echo 1;

            if($checker["old_status"]==1){
                $quest=$con->query("SELECT id,amount,field from quest_list where special=0 and level > 0 order by rand() limit 3")->fetch_All(MYSQLI_ASSOC);
                $fields=[];
                foreach($quest as $field){
                    $fields[]=$field["field"];
                }

                $fields=implode(",",$fields);

                $user=$con->query("SELECT login.id,login.level,".$fields." from user_statistic LEFT JOIN login on login.id=user_statistic.id where id= ".$_SESSION["access"])->fetch_All(MYSQLI_ASSOC);

                $rand_num=rand(0,count($quest)-1);
                $insert_values[]="(".$user["id"].",".$quest[$rand_num]["id"].",".($user[$quest[$rand_num]["field"]]+$quest[$rand_num]["amount"]).",0)";
                $con->query("INSERT INTO quest_log(user,quest,value,persistent) VALUES ".$insert_values);
            }

        } else {
            echo 0;
        }

	}else{
	    echo 0;
    }

	$con->close();

?>