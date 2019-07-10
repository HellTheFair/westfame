<?php

	require_once("../php/includes/connect_db.php");

	$answer=$con->query("SELECT distinct name,id from effect where type='player'")->fetch_All(MYSQLI_ASSOC);

	header("Content-type: json");
	echo json_encode($answer);

?>