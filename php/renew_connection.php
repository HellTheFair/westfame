<?php

session_start();
header("Content-type: application/json");
if(isset($_SESSION["access"])) {
    require_once("includes/connect_db.php");
    $con->query("UPDATE `login` SET `last_renewal`=now(), `online`=1 WHERE `id`=". $_SESSION['access']);
    $new_items=$con->query("SELECT picture,name,type,description from knowledge_base where id = any(SELECT item from inventory where user=".$_SESSION["access"]." and date>=now())")->fetch_All(MYSQLI_ASSOC);
    $last_loop=mysqli_fetch_assoc($con->query("Select last_use from oddmans_timing where action='loop'"));
    $hp=$con->query("SELECT gold,max_hp,hp,status,last_chat_renewal as chat from login where id=".$_SESSION["access"])->fetch_assoc();
    $hp_price=($hp["max_hp"]-$hp["hp"])*$gold_for_hp;
    $hp_time=$hp_per_sec;
    if($hp["status"]>=$vip_status)$hp_time+=$hp_per_sec;
    if(strtotime($hp["chat"])>strtotimeNOW()-2)$hp_time+=$hp_per_sec;
    $hp_time=($hp["max_hp"]-$hp["hp"])/$hp_time;
    $server_status=1;
    $achivements=$con->query("SELECT id,description,name from achievement_list where id=any(SELECT achievement from achievement_log where user=".$_SESSION["access"]." and date>now() - INTERVAL 1 SECOND)")->fetch_All(MYSQLI_ASSOC);
    $cur_duel=$con->query("SELECT id from duel where started=1 and finished=0 and (acceptor=".$_SESSION["access"]." or creator = ".$_SESSION["access"].")")->fetch_assoc()["id"];
    $cur_duel = !!$cur_duel ? !!$cur_duel : "0";
    if($last_loop["last_use"]<round(microtime(true) * 1000-10000))$server_status=3;
    echo json_encode(["hp"=>$hp["hp"], "max_hp"=>$hp["max_hp"],"gold"=>$hp["gold"],"hp_time"=>ceil($hp_time),"hp_price"=>$hp_price,"new_achieves"=>$achivements,'protocol'=>$server_status,'new_items'=>$new_items,"active_duel"=>$cur_duel]);
    $con->close();

}else{
    echo 0;
}

?>