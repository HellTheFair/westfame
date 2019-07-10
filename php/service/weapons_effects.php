<?php
require_once("../php/includes/connect_db.php");

$effect_list=[11,16,39,17,18];

$weapons=$con->query("SELECT id,level from knowledge_base where type='weapon' and id<>5 and id>0");

while($weapon=$weapons->fetch_assoc()){
    $current_weapon=$weapon["id"];
    $i=$weapon["level"];
    echo "<br>".$current_weapon;
    $effect_list_amount=3;
    $effects_per_level=1;
    if($i>=5){
        $effect_list_amount=4;
        $effects_per_level=2;
    }
    if($i>=10){
        $effect_list_amount=5;
        $effects_per_level=3;
    }
    $effects=$effect_list;
    $cur_effect=rand(0,$effect_list_amount-1);
    $effect_list_amount--;
    $update_text="effect1=".$effects[$cur_effect];
    array_splice($effects,$cur_effect,1);
    for($j=2;$j<=$effects_per_level;$j++){
        if(rand(0,100)<=50){
            $cur_effect=rand(0,$effect_list_amount-1);
            $effect_list_amount--;
            $update_text.=",effect".$j."=".$effects[$cur_effect];
            array_splice($effects,$cur_effect,1);
        }
    }
    echo "UPDATE knowledge_base set $update_text where id=$current_weapon and type='weapon'<br>";
    //	$con->query("UPDATE knowledge_base set $update_text where id=$current_weapon and type='weapon'");
}
?>