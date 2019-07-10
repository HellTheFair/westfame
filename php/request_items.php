<?php
require_once("includes/connect_db.php");
session_start();
if(!$_SESSION['access']){
    echo 0;
}else{
    $next_level=0;
    $gold_to_level=0;
    $raw_user_data = $con->query("SELECT level,gold from `login` where `id`='".$_SESSION['access']."'");
    $user_data=mysqli_fetch_assoc($raw_user_data);

    $shop_misc=$con->query("SELECT `id`,`name`,`description`,`picture`,`price` From knowledge_base where type='misc' and sellable=1 and not id in (SELECT item from inventory where type='misc' and (user=".$_SESSION["access"]." or user=0)) and level<=".$user_data["level"]." ORDER BY level DESC, price")->fetch_All(MYSQLI_ASSOC);
    $shop_skin=$con->query("SELECT `id`,`name`,`description`,`picture`,`price` From knowledge_base where type='skin' and sellable=1 and not id in (SELECT item from inventory where type='skin' and (user=".$_SESSION["access"]." or user=0)) and level<=".$user_data["level"]." ORDER BY id LIMIT 1")->fetch_assoc();

    $text_col="eff1.name as effect1";
    $text_join="LEFT JOIN effect eff1 on asd.effect1=eff1.id";
    for($i=2;$i<=$weapon_effect_amount;$i++){
        $text_col.=",eff".$i.".name as effect".$i;
        $text_join.=" LEFT JOIN effect eff".$i." on asd.effect".$i."=eff".$i.".id";
    }

    $shop_weapon=$con->query("SELECT asd.`id`,asd.`name`,asd.`description`,asd.`picture`,asd.`price`,asd.`subtype`,asd.min_dmg,asd.max_dmg,asd.ammo,asd.chance_to_crit,".$text_col." FROM knowledge_base asd ".$text_join." WHERE asd.type='weapon' and (asd.sellable=1 or asd.id in (SELECT item from inventory where type='weapon' and (user=".$_SESSION["access"]." or user=0))) and (asd.level between ".($user_data["level"]-3)." and ".$user_data["level"]." or asd.subtype='scalable') ORDER BY asd.level DESC, twohand")->fetch_All(MYSQLI_ASSOC);
    $inventory_skin=$con->query("SELECT distinct base.id,base.name,base.description,base.picture FROM inventory,knowledge_base base WHERE inventory.item=base.id and base.type='skin' and (inventory.user=".$_SESSION["access"]." or inventory.user=0)")->fetch_All(MYSQLI_ASSOC);
    $inventory_misc=$con->query("SELECT inventory.item,base.subtype as type,base.name,base.description,base.picture FROM inventory,knowledge_base base WHERE inventory.item=base.id and base.type='misc' and (inventory.user=".$_SESSION["access"]." or inventory.user=0)")->fetch_All(MYSQLI_ASSOC);
    $inventory_weapon_raw=$con->query("SELECT item FROM inventory WHERE type='weapon' and (user=".$_SESSION["access"]." or user=0)");
    $inventory_weapon=[];
    for($q=0;$q<count($shop_weapon);$q++){
        if($shop_weapon[$q]["subtype"]=="scalable"){
            $shop_weapon[$q]["min_dmg"]="".floor(($start_hp+$hp_coef*($user_data["level"]-1))*($shop_weapon[$q]["min_dmg"]/100));
            $shop_weapon[$q]["max_dmg"]="".floor(($start_hp+$hp_coef*($user_data["level"]-1))*($shop_weapon[$q]["max_dmg"]/100));
        }
        unset($shop_weapon[$q]["subtype"]);
    }

    while($inventory_weapon_temp=mysqli_fetch_assoc($inventory_weapon_raw)["item"]){
        $inventory_weapon[count($inventory_weapon)]=$inventory_weapon_temp;
    }

    $quests=$con->query("SELECT quest.id,log.value,quest.description,quest.field,quest.amount,quest.reward_type,quest.reward from quest_list quest, quest_log log where log.complete=0 and quest.id=log.quest and user=".$_SESSION["access"])->fetch_All(MYSQLI_ASSOC);
    $user_quests = [];
    if(!!$quests) {
        $fields = [];
        foreach ($quests as $quest) $fields[] = $quest["field"];
        $fields_text = implode(",", $fields);
        $stats = mysqli_fetch_assoc($con->query("SELECT " . $fields_text . " from user_statistic where id=" . $_SESSION["access"]));
        foreach ($quests as $quest) {
            if ($quest["reward_type"] == "gold") $quest["reward"] = round(($default_reward * $gold_coef * $user_data["level"] + $default_reward) * ceil($user_data["level"] / $level_coef) * ($quest["reward"] * 0.01));
            if ($quest["reward_type"] == "item") $quest["reward"] = mysqli_fetch_assoc($con->query("SELECT name from knowledge_base where id=" . $quest["reward"]))["name"];
            $user_quests[] = ["desc" => $quest["description"], "reward_type" => $quest["reward_type"], "reward" => $quest["reward"], "target" => +$quest["amount"], "value" => ($stats[$quest["field"]]-($quest["value"]-$quest["amount"]))];
        }
    }
    $response=['gold'=>$user_data['gold'],"shop_misc"=>$shop_misc,"shop_skin"=>$shop_skin,"shop_weapon"=>$shop_weapon,"weapon_effect_amount"=>$weapon_effect_amount,"inventory_skin"=>$inventory_skin,"inventory_misc"=>$inventory_misc,"inventory_weapon"=>$inventory_weapon,"quests"=>$user_quests];
    header("Content-type: application/json");
    echo json_encode($response);
}
$con->close();

?>