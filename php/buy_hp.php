<?php
	require_once("includes/connect_db.php");
	session_start();
    if(isset($_SESSION["access"])) {
        $user_hp=$con->query("SELECT hp,max_hp from login where id=".$_SESSION["access"])->fetch_assoc();
        $answer = $con->query("UPDATE login set hp=max_hp,gold=gold-" . (($user_hp["max_hp"] - $user_hp["hp"]) * $gold_for_hp) . " where id=" . $_SESSION["access"] . " and gold>=" . (($user_hp["max_hp"] - $user_hp["hp"]) * $gold_for_hp) . " and hp < max_hp");
        if ($con->affected_rows == 0) {
            echo "You're too poor";
        } else {
            update_statistic($_SESSION["access"], ["hp_bought" => 1,"gold_spent"=>($user_hp["max_hp"]-$user_hp["hp"])*$gold_for_hp]);
            echo 1;
        }
    }else{
        echo 0;
    }
	$con->close();

?>