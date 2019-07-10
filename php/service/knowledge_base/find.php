<?php

	require_once("../php/includes/connect_db.php");
	$text=mysqli_real_escape_string($con,$_POST["main"]);
	$type=mysqli_real_escape_string($con,$_POST["type"]);
	$min_lvl=mysqli_real_escape_string($con,$_POST["min-level"]);
	$max_lvl=mysqli_real_escape_string($con,$_POST["max-level"]);
	if(isset($_POST["twohand"]))$twohand=mysqli_real_escape_string($con,$_POST["twohand"]);
	$text_array=explode(" ",$text);

	$text_to_find = "((name like '%".$text_array[0]."%' ";
	for($i=1;$i<count($text_array);$i++){
		$text_to_find.=" and name like '%".$text_array[$i]."%' ";
	}
	if(is_numeric($text)){
		$text_to_find.=") or id='".$text."')";
	}else{
		$text_to_find.="))";
	}
	if($type)$text_to_find.=" and type='".$type."' ";
	if(isset($_POST["twohand"])&&$twohand!="any")$text_to_find.=" and twohand = ".$twohand;
	if($min_lvl&&$min_lvl>=0&&$min_lvl<=50){
		$text_to_find.=" and level >= ".$min_lvl." ";
	}else{
		$text_to_find.=" and level >= 0 ";
	}
	if($max_lvl&&$max_lvl>=0&&$max_lvl<=50){
		$text_to_find.=" and level <= ".$max_lvl." ";
	}else{
		$text_to_find.=" and level <= 50 ";
	}

	$answer=$con->query("Select * from knowledge_base where ".$text_to_find." and id>0 order by level asc")->fetch_all(MYSQLI_ASSOC);
	//echo "Select * from knowledge_base where ".$text_to_find." and id>0";
	header("Content-type: json/application");
	echo json_encode($answer);


?>