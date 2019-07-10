<?php

	require_once("../php/includes/connect_db.php");

	$answer=$con->query("SELECT distinct type from knowledge_base where id>0")->fetch_All(MYSQLI_ASSOC);

	header("Content-type: json");
	echo json_encode($answer);

?>