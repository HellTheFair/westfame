<?php

	require_once("../php/includes/connect_db.php");
	$text=mysqli_real_escape_string($con,$_POST["main"]);
	$type=mysqli_real_escape_string($con,$_POST["type"]);
	$min_lvl=mysqli_real_escape_string($con,$_POST["min-level"]);
	$max_lvl=mysqli_real_escape_string($con,$_POST["max-level"]);
	if(isset($_POST["effect"]))$effect=mysqli_real_escape_string($con,$_POST["effect"]);
	if(isset($_POST["offensive"]))$offensive=mysqli_real_escape_string($con,$_POST["offensive"]);
	if(isset($_POST["passive"]))$passive=mysqli_real_escape_string($con,$_POST["passive"]);
	if(isset($_POST["effect"]))$effect=mysqli_real_escape_string($con,$_POST["effect"]);
	$text_array=explode(" ",$text);

	$text_for_skill = "((name like '%".$text_array[0]."%' ";
	$text_for_effect = "(((name like '%".$text_array[0]."%' ";
	

	for($i=1;$i<count($text_array);$i++){
		$text_for_skill.=" and name like '%".$text_array[$i]."%' ";
		$text_for_effect.=" and name like '%".$text_array[$i]."%' ";
	}
	if(is_numeric($text)){
		$text_for_skill.=") or id='".$text."')";
		$text_for_effect.=") or id='".$text."')";
	}else{
		$text_for_skill.="))";
		$text_for_effect.="))";
	}
	$text_for_effect.=" or (action like '%".$text_array[0]."%'))";
	if($type&&$type=="skill"){
		if(isset($passive))$text_for_skill.=" and passive = ".$passive." ";
		if(isset($effect)){
			$text_for_skill.=" and (effect1 = ".$effect." ";
			for($i=2;$i<$effect_amount;$i++)$text_for_skill.=" or effect".$i." = ".$effect." ";
			$text_for_skill.=") ";
		}
		if($min_lvl&&$min_lvl>=0&&$min_lvl<=50){
			$text_for_skill.=" and level >= ".$min_lvl." ";
		}else{
			$text_for_skill.=" and level >= 0 ";
		}
		if($max_lvl&&$max_lvl>=0&&$max_lvl<=50){
			$text_for_skill.=" and level <= ".$max_lvl." ";
		}else{
			$text_for_skill.=" and level <= 50 ";
		}
	}elseif($type&&$type=="effect"){

		if(isset($offensive))$text_for_effect.=" and offensive=".$offensive." ";
		if(isset($effect))$text_for_effect=" id = ".$effect." ";
		
	}else{

		if(isset($offensive))$text_for_effect.=" and offensive=".$offensive." ";
		if(isset($passive))$text_for_skill.=" and passive = ".$passive." ";

		if(isset($effect)){
			$text_for_skill.=" and (effect1 = ".$effect." ";
			for($i=2;$i<$effect_amount;$i++)$text_for_skill.=" or effect".$i." = ".$effect." ";
			$text_for_skill.=") ";
		}

		if($min_lvl&&$min_lvl>=0&&$min_lvl<=50){
		$text_for_skill.=" and level >= ".$min_lvl." ";
		}else{
			$text_for_skill.=" and level >= 0 ";
		}
		if($max_lvl&&$max_lvl>=0&&$max_lvl<=50){
			$text_for_skill.=" and level <= ".$max_lvl." ";
		}else{
			$text_for_skill.=" and level <= 50 ";
		}
		if(isset($effect))$text_for_effect=" id = ".$effect." ";
	}

	$answer_skill=$con->query("Select *,'skill' as type from skill where ".$text_for_skill." and id>0 order by level asc")->fetch_all(MYSQLI_ASSOC);
	$answer_effect=$con->query("Select *,'effect' as type from effect where ".$text_for_effect." and id>0")->fetch_all(MYSQLI_ASSOC);
	//echo "Select *,'skills' as type from skill where ".$text_for_skill." and id>0";
	$answer=[];
	if(!$type||$type!="effect"||$type=="skill")$answer=array_merge($answer,$answer_skill);
	if(!$type||$type!="skill"||$type=="effect")$answer=array_merge($answer,$answer_effect);
	header("Content-type: json");
	echo json_encode($answer);


?>