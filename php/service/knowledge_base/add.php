<?php
	error_reporting(0);
	session_start();
	if($_SESSION['access']){
		require_once("../php/includes/connect_db.php");	
		$status=mysqli_fetch_assoc($con->query("SELECT status from login where id=".$_SESSION["access"]))["status"];
		if($status>=1){

			if($_POST["name"]&&$_POST["type"]){

				$item=[];
				foreach($_POST as $key => $value){
					$item[$key] = mysqli_escape_string($con,$_POST[$key]);
				}
				
				//echo json_encode(print_r($_POST));
				$name=explode(".",$_FILES["icon"]["name"]);
				$ext=$name[count($name)-1];
				$name[count($name)-1]="";
				$name=implode("",$name);
				$target_dir=$_SERVER["DOCUMENT_ROOT"]."/tester/img/".$item["type"]."/".$name.".".$ext;
				if(!file_exists($target_dir))move_uploaded_file($_FILES["icon"]["tmp_name"],$target_dir);
				$text="";
				$cols="";
				if($item["level"]>50)$item["level"]=50;
				if($item["price"]&&$item["price"]<=0)$item["price"]=1;
				if(+$item["twohand"]==1){
					$ammo=1;
				}else{
					$ammo=6;
				}
				foreach($item as $key => $value){
					if($text!="")$text.=", ";
					if(+$value&&$value<0)$value=1;
					$text.="'".$value."'";
					if($cols!="")$cols.=", ";
					$cols.=$key;
				}
				$cols.=", picture";
				$text.=", '".$name.".".$ext."'";
				$con->query("INSERT INTO knowledge_base(".$cols.",ammo,sellable,appended) VALUE (".$text.",".$ammo.",1,1)");
				echo 1;
			}
		}else{
			echo "access denied";
		}
	}else{
		echo "access denied";
	}
	
?>