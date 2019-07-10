<?php
	require_once("includes/connect_db.php");
	session_start();
	if($_SESSION["access"]&&$_POST["item"]){
		if(!is_numeric($_POST["item"]))$_POST["item"]=0;
		$item=mysqli_fetch_assoc($con->query("SELECT id,level,price,type from knowledge_base where sellable=1 and id=".$_POST["item"]));
		if(!$item){
			echo "No such item exists or none can be sold";
		}else{	
			$amount=mysqli_fetch_row($con->query("SELECT count(id) from knowledge_base where type='weapon' and id in (SELECT item from inventory where user=".$_SESSION["access"].")"))[0];
			if(0<$inventory_cap){
				$user=mysqli_fetch_assoc($con->query("SELECT gold,level from login where id=".$_SESSION["access"]));
				
				if($user["gold"]<$item["price"]){
					echo "Not enough gold";
				}else{	
					if($user["level"]<$item["level"]){
						echo "Your level is too low";
					}else{
						$checker=$con->query("SELECT id from inventory where (user=".$_SESSION["access"]." or user=0) and item=".$item["id"]);
						if(!mysqli_fetch_row($checker)[0]){
                            $skin_fine=0;
						    if($item["type"]=="skin"){
						        $prev_skins=$con->query("SELECT id from knowledge_base where type='skin' and sellable=1 and id<".$item["id"]." and level<".$item["level"]." and not id = any(SELECT item from inventory where user=".$_SESSION["access"]." and type='skin')");
						        if($prev_skins->num_rows==0)$skin_fine=1;
                            } else {
                                $skin_fine=1;
                            }
						    if($skin_fine==1) {
                                $con->query("UPDATE login set gold=gold-" . $item["price"] . " WHERE gold>=" . $item["price"] . " and level>=" . $item["level"] . " and id=" . $_SESSION["access"]);
                                $con->query("INSERT INTO inventory(user,item,`date`,type) VALUES(" . $_SESSION["access"] . "," . $item["id"] . ",now(),'" . $item["type"] . "')");
                                update_statistic($_SESSION["access"], ["gold_spent" => $item["price"], "items_bought" => 1]);
                                echo 1;
                            }else{
						        echo "You don't have all the previous skins";
                            }
						}else{
							echo "You already have this item";
						}
					}
				}
			}else{
				echo "Not enough space in your inventory";
			}
		}
	}else{
		echo "Who the fuk are you";
	}
	$con->close();

?>