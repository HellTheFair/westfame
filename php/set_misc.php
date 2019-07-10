<?php
	require_once("includes/connect_db.php");
	session_start();
	if($_SESSION['access']&&$_POST["decorations"]){
		$misc_temp=explode(" ",$_POST["decorations"]);
		$misc=[];
		for($i=0;$i<$decoration_amount;$i++){
			if(!isset($misc_temp[$i])||!is_numeric($misc_temp[$i])||+$misc_temp[$i]<=1){
				$misc[$i]=0;
			}else{
				$misc[$i]=$misc_temp[$i];
			}
		}
		$text_misc=implode(",",$misc);
		//$user=mysqli_fetch_assoc($con->query("SELECT decoration_wall,decoration_floor,decoration_table from login where id=".$_SESSION['access']));
		$user_inv=$con->query("SELECT inv.item,base.subtype from inventory inv,knowledge_base base where base.type='misc' and base.id = inv.item and inv.item in (".$text_misc.") and (inv.user=".$_SESSION['access']." or inv.user=0)")->fetch_All(MYSQLI_ASSOC);
		$inv_item=[];
		$inv_item[0]=0;
		$inv_type=[];
		$inv_type[0]=0;
		for($i=0;$i<count($user_inv);$i++){
			$inv_item[]=$user_inv[$i]["item"];
			$inv_type[]=$user_inv[$i]["subtype"];
		}
		$misc_wall=[];
		$misc_floor=[];
		$misc_table=[];

		for($i=0;$i<$decoration_amount;$i++){
			$pos=array_search($misc[$i],$inv_item);
			if ($pos!=0){
				if($inv_type[$pos]=="wall"&&count($misc_wall)<$decoration_wall_amount){
                    $misc_wall[]=$inv_item[$pos];
                }
                if($inv_type[$pos]=="floor"&&count($misc_floor)<$decoration_floor_amount){
                    $misc_floor[]=$inv_item[$pos];
                }
                if($inv_type[$pos]=="table"&&count($misc_table)<$decoration_table_amount){
                    $misc_table[]=$inv_item[$pos];
                }
			}else{
				if($i<$decoration_wall_amount){
					$misc_wall[]=0;
				}
				if($i>=$decoration_wall_amount&&$i<$decoration_wall_amount+$decoration_floor_amount){
					$misc_floor[]=0;
				}
				if($i>=$decoration_amount-$decoration_table_amount){
					$misc_table[]=0;
				}
			}
		}
		$con->query("UPDATE login set decoration_wall='".implode(" ",$misc_wall)."', decoration_floor='".implode(" ",$misc_floor)."', decoration_table = '".implode(" ",$misc_table)."' WHERE id=".$_SESSION["access"]);
		echo 1;

	}else{	
		echo 0;
	}
	$con->close();

?>	