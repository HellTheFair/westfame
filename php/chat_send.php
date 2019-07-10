<?php
	require_once("includes/connect_db.php");
	session_start();
	if(isset($_POST["msg"])&&isset($_SESSION["access"])){
		$msg=$_POST["msg"];
		$target=0;
		if(isset($_POST["target"])){
            if($_POST["target"]) {
                $result = $con->query("Select id,online from login where status>=0 and id=" . $_POST["target"]);
                if ($result->num_rows > 0) {
                    $target = $_POST["target"];
                    if (mysqli_fetch_assoc($result)["online"] == 0) echo "This player is offline but messages will be delivered once he logs in";
                }
            }
        }
		$temp_check=$con->query("SELECT sender from chat where (`time`=now() and sender=".$_SESSION["access"].") or ( `time`>now() - INTERVAL 5 SECOND and message='".$msg."' and sender=".$_SESSION["access"].")");
		if(!mysqli_fetch_row($temp_check)){
			$con->query("INSERT INTO `chat`(`sender`,`target`,`time`,`message`) VALUES('".$_SESSION["access"]."',".$target.",now(),'".$msg."')");
		}else{
			echo "Slow down";
		}
	}
	$con->close();
?>