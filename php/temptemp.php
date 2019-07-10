<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/tester/oddman/php/includes/connect_db.php");
echo password_hash("asdasd", PASSWORD_BCRYPT, ["cost" => 10]);
echo "<br>";
//echo update_statistic(3,["duels_done"=>1]);
$time_server=$con->query("select now()")->fetch_row()[0];

$stmt=$con->prepare("SELECT id,status from login where status in (?,?)");
$asd=[];
$list=["ii",&$asd[0],&$asd[1]];
call_user_func_array(array($stmt,'bind_param'),$list);
$asd[0]="0";
$asd[1]="-1";
$stmt->execute();
$result=$stmt->get_result();print_r($result->fetch_all(MYSQLI_ASSOC));
?>