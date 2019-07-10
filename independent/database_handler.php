<?php

require_once("includes/connect_db.php");

$time_start = microtime(true);
$eligibility_check = $con->query("SELECT frequency,last_use FROM oddmans_timing where action='loop'")->fetch_assoc();
if($eligibility_check["last_use"]+$eligibility_check["frequency"]*1000<=round(microtime(true)*1000)){

    $con->query("UPDATE oddmans_timing set last_use=".round(microtime(true)*1000)." WHERE action='loop'");

#user offline
    $raw_login = $con->query("SELECT `id` FROM `login` WHERE `online`=1 and status>=0 and last_renewal<now() - INTERVAL 2 SECOND");

    $update_offline_query=$con->prepare("UPDATE `login` SET `online`=0 WHERE `id`=?");
    $update_offline_duel_query=$con->prepare("UPDATE `duel` SET `finished`=-1 WHERE `creator`=? and `started`=0 and `finished`=0");
    $update_offline_query->bind_param("i",$update_offline_id);
    $update_offline_duel_query->bind_param("i",$update_offline_id);
    while($data=mysqli_fetch_assoc($raw_login)){
        $update_offline_id=$data['id'];
        $update_offline_query->execute();
        $update_offline_duel_query->execute();
    }

    #clear_chat
    $con->query("DELETE FROM `chat` WHERE (target=0 and time<now() - INTERVAL 30 Second ) or delivered=1");


    #EXECUTION OF SKILL EFFECTS FUNCTION
    $current_effect_query=$con->prepare("SELECT name,action,offensive,percentage,chance,length FROM effect where id=?");
    $current_effect_query->bind_param("i",$current_effect_query_id);

    function execute_effect(&$attacker,&$defender,$effect,$source="default"){
        global $con;
        global $duel_step,$prev_step,$current_step,$duel_end;
        global $current_effect_query_id;
        global $current_effect_query;
        $current_effect_query_id=$effect;
        $current_effect_query->execute();
        $raw_current_effect=$current_effect_query->get_result();
        $current_effect=$raw_current_effect->fetch_assoc();
        if(rand(1,100)<=$current_effect["chance"]){
            if($current_effect["length"]>1&&$source!="post")$attacker["text"].=";&".$current_effect["name"];
            if($current_effect["offensive"]==1){
                switch($current_effect["action"]){
                    case "damage":
                        $attacker["dmg_mult"][]=$current_effect["percentage"]*0.01;
                        if(strpos($attacker["text"],";Hit")!==false)$attacker["text"].=";".$current_effect["name"];
                        break;

                    case "enemy_damage":
                        $defender["dmg_mult"][]=$current_effect["percentage"]*0.01;
                        if($current_effect["percentage"]==0) $defender["text"].=";Blinded";
                        break;

                    case "hpcrit":
                        if(strpos($attacker["text"],";Crit")===false){
                            if($duel_step[$attacker["name"]."_hp"]/$attacker["user_data"]["max_hp"]<=$current_effect["percentage"]*0.01){
                                $attacker["dmg_mult"][]=2;
                            }

                        }
                        break;

                    case "crit":
                        if(strpos($attacker["text"],";Crit")===false){
                            $attacker["dmg_mult"][]=$current_effect["percentage"]*0.01;
                        }
                        break;

                    case "toxin":
                        if(strpos($attacker["text"],";Hit")!==false){
                            $hit_amount=mysqli_fetch_row($con->query("SELECT count(`step`) from `duel_id` where duel=".$duel_end["id"]." and ".$attacker["name"]."_event like '%;".$current_skill["name"]."%'"));
                            if($hit_amount[0]>0){
                                $attacker["dmg"][]=$attacker["wep_dmg"]*($current_effect["percentage"]*0.01*$hit_amount[0]);
                            }
                            $attacker["text"].=";".$current_effect["name"];
                        }
                        break;

                    case "post_damage":
                        if(strpos($attacker["text"],";Hit")!==false){
                            $attacker["dmg_mult"][]=1-$current_effect["percentage"]*0.01;
                        }
                        if(strpos($prev_step[$attacker["name"]."_event"],";Hit")!==false && strpos($prev_step[$attacker["name"]."_event"],";NoDirectDmg")===false){
                            if(strpos($prev_step[$attacker["name"]."_event"],";Crit")!==false){
                                $attacker["uneffectable_dmg"][]=2*$attacker["wep_dmg"]*($current_effect["percentage"]*0.01);
                            }else{
                                $attacker["uneffectable_dmg"][]=$attacker["wep_dmg"]*($current_effect["percentage"]*0.01);
                            }
                            $attacker["text"].=";".$current_effect["name"];
                        }
                        break;

                    case "root":
                        $duel_step[$defender["name"]."_def"]=$prev_step[$defender["name"]."_def"];
                        $con->query("Update duel_id set ".$defender["name"]."_def=".$duel_step[$defender["name"]."_def"]." where duel=".$duel_end["id"]." and step=".$current_step);
                        $defender["text"].=";Rooted";
                        break;

                    case "purify":
                        if(strpos($attacker["text"],";Unavoidable")!==false){
                            $attacker["text"].=";Unavoidable";
                            $temp_sum=0;

                            for($ei=0;$ei<count($attacker["dmg"]);$ei++){
                                $temp_sum+=$attacker["dmg"][$ei];
                            }
                            for($ei=0;$ei<count($attacker["dmg_mult"]);$ei++){
                                $temp_sum*=$attacker["dmg_mult"][$ei];
                            }
                            $attacker["uneffectable_dmg"][]=$temp_sum;
                        }
                        if($current_effect["percentage"]<100) {
                            $attacker["dmg_mult"][] = 1 - ($current_effect["percentage"] * 0.01);
                        }else{
                            $attacker["dmg_mult"][] = 0;
                        }
                        $attacker["uneffectable_dmg_mult"][count($attacker["uneffectable_dmg"])-1]=$current_effect["percentage"]*0.01;
                        break;

                    case "death":
                        if(($defender["final_hp"]/$defneder["user_data"]["max_hp"])*100>=$current_effect["percentage"]){
                            $defender["final_hp"]=0;
                            $defender["text"].=";Dead";
                        }

                        break;
                    default:

                        break;
                }
            }elseif($current_effect["offensive"]==0){
                switch($current_effect["action"]){
                    case "damage":
                        $attacker["def_mult"][]=$current_effect["percentage"]*0.01;
                        if(strpos($defender["text"],";Hit")!==false)$attacker["text"].=";".$current_effect["name"];
                        break;

                    case "enemy_damage":
                        $defender["def_mult"][]=$current_effect["percentage"]*0.01;
                        break;

                    case "rdah":
                        if(strpos($duel_step[$attacker["name"]."_event"],";Hit")!==false&& strpos($prev_step[$attacker["name"]."_event"],";NoDirectDmg")===false){
                            $attacker["def_mult"][]=$current_effect["percentage"]*0.01;
                        }
                        break;
                    case "dpah":
                        if(strpos($duel_step[$defender["name"]."_event"],";PartHit")!==false){
                            $defender["dmg"][]=-round($defender["wep_dmg"]*0.25*($current_effect["percentage"]*0.01));
                        }
                        break;

                    case "reload":
                        if($duel_step[$attacker["name"]."_attack"]>0&&$duel_step[$attacker["name"]."_ammo"]>0&&$attacker["ammo_refund"]==0){
                            $attacker["ammo"]++;
                            $attacker["ammo_refund"]=1;
                        }
                        $attacker["ammo"]++;
                        if($attacker["ammo"]>$attacker["wep"]["ammo"])$attacker["ammo"]=$attacker["wep"]["ammo"];
                        break;

                    case "full_reload":
                        $attacker["ammo"]+=$attacker["wep"]["ammo"]/($current_effect["percentage"]*0.01);
                        if($attacker["ammo"]>$attacker["wep"]["ammo"])$attacker["ammo"]=$attacker["wep"]["ammo"];
                        break;

                    case "partdmg":
                        if(strpos($defender["text"],"PartHit")!==false){
                            $defender["dmg"][]=-round($defender["wep_dmg"]*0.25*($current_effect["percentage"]*0.01));
                            if($current_effect["percentage"]==100){
                                $attacker["text"].=";Avoidance";
                            }
                        }
                        break;
                    case "regen":
                        $duel_step[$attacker["name"]."_hp"]+=$attacker["user_data"]["max_hp"]*($current_effect["percentage"]*0.01);
                        if($duel_step[$attacker["name"]."_hp"]>$attacker["user_data"]["max_hp"])$duel_step[$attacker["name"]."_hp"]=$attacker["user_data"]["max_hp"];
                        break;

                    case "denydeath":
                        print_r($attacker);
                        if($attacker["final_hp"]<=0){
                            if(strpos($attacker["text"],";Dead")==false)
                                $attacker["final_hp"]=1;
                            $attacker["text"].=";".$current_effect["name"];
                        }
                        break;

                    case "target_enemy":
                        $duel_step[$attacker["name"]."_attack"]=$duel_step[$defender["name"]."_def"];
                        $con->query("Update duel_id set ".$attacker["name"]."_attack=".$duel_step[$defender["name"]."_def"]." where duel=".$duel_end["id"]." and step=".$current_step);
                        break;

                    default:

                        break;
                }
            }
        }
    }

    $bot_check_query=$con->prepare("SELECT status,level,".$skills_amount_text." from login where id=?");
    $bot_check_query->bind_param("i",$bot_check_query_id);
    $duel_step_query = $con->prepare("SELECT * FROM duel_id WHERE duel=? ORDER BY step DESC LIMIT 1");
    $duel_step_query->bind_param("i",$duel_step_query_id);
    $player_data_query = $con->prepare("SELECT " . $skills_amount_text . ",weapon,level,fame,max_hp from `login` WHERE id=?");
    $player_data_query->bind_param("i",$player_data_query_id);
    $prev_step_query=$con->prepare("SELECT * FROM duel_id WHERE duel=? and step=?");
    $prev_step_query->bind_param("ii",$duel_step_query_id,$prev_step_query_step);
    $player_wep_query = $con->prepare("SELECT ".$weapon_effect_amount_text.",subtype,chance_to_crit,ammo,min_dmg,max_dmg from knowledge_base WHERE id=?");
    $player_wep_query->bind_param("i", $player_wep_query_id);
    $bot_odds_query=$con->prepare("SELECT * FROM bot_list where level=?");
    $bot_odds_query->bind_param("i",$bot_odds_query_level);
    $skill_effects_query=$con->prepare("SELECT ".$effect_amount_text." FROM `skill` WHERE id=?");
    $skill_effects_query->bind_param("i",$skill_effects_query_id);
    $skill_types="s";
    $passive_skills_query_type="";
    $passive_skills_query_params=[];
    $passive_skills_query_params[]=&$skill_types;
    $passive_skills_query_params[]=&$passive_skills_query_type;
    $skill_placeholders=[];
    $passive_skills_query_list=[];
    for($temp=0;$temp<$skills_amount;$temp++) {
        $skill_placeholders[]="?";
        $skill_types.="i";
        $passive_skills_query_params[]=&$passive_skills_query_list[$temp];
    }
    $skill_placeholders=implode(",",$skill_placeholders);
    $passive_skills_query = $con->prepare("SELECT name," . $effect_amount_text . ",passive,cd,chance,type FROM skill where type=? and passive=1 and id in ($skill_placeholders) ORDER BY priority DESC");
    call_user_func_array(array($passive_skills_query,'bind_param'),$passive_skills_query_params);

    $current_skill_query = $con->prepare("SELECT name," . $effect_amount_text . ",passive,cd,chance,type FROM skill where type=? and passive=0 and id=?");
    $current_skill_query->bind_param("si",$current_skill_query_type,$current_skill_query_id);
    $cd_check_query = $con->prepare("SELECT `step` from `duel_id` where  duel=? and ? like ? ORDER BY step DESC LIMIT 1");
    $cd_check_query->bind_param("iss",$cd_check_query_duel,$cd_check_query_event,$cd_check_query_value);


    #duel step calc
    $raw_duel_end = $con->query("SELECT `id`,`creator` as `c`,`acceptor` as `a`,`last_update` FROM `duel` WHERE `creator`>0 and `acceptor`>0 and `started`=1 and `finished`=0 and `last_update`<= now() + INTERVAL 1 SECOND");
    while($duel_end=mysqli_fetch_assoc($raw_duel_end)){

        $players=[];

        $a=[];
        $c=[];

        $a["name"]="a";
        $c["name"]="c";
        $a["target"]=&$c;
        $c["target"]=&$a;

        $players[$a["name"]]=&$a;
        $players[$c["name"]]=&$c;

        $bot_check_query_id=$duel_end["a"];
        $bot_check_query->execute();
        $raw_bot_check=$bot_check_query->get_result();
        $bot_check=$raw_bot_check->fetch_assoc();

        $duel_step_query_id=$duel_end['id'];
        $duel_step_query->execute();
        $raw_duel_step=$duel_step_query->get_result();
        $duel_step = $raw_duel_step->fetch_assoc();
        $current_step = $duel_step["step"];

        foreach($players as &$player) {
            $player_data_query_id=$duel_end[$player["name"]];
            $player_data_query->execute();
            $raw_player=$player_data_query->get_result();
            $player["user_data"] = $raw_player->fetch_assoc();
        }

        if($current_step!=1){
            $prev_step_query_step=$current_step - 1;
            $prev_step_query->execute();
            $raw_prev_step=$prev_step_query->get_result();
            $prev_step=$raw_prev_step->fetch_assoc();
        }else{
            $prev_step= mysqli_fetch_assoc($con->query("SELECT * FROM duel_id WHERE duel=0 and step=0"));
        }

        //PLAYERS VARS

        foreach($players as &$player){
            $player["ammo"]=$duel_step[$player["name"]."_ammo"];
            $player["ammo_refund"]=0;
            $player["dmg"]=[];
            $player["uneffectable_dmg"]=[];
            $player["uneffectable_dmg_mult"]=[];
            $player["unef_sum_dmg"]=0;
            $player["dmg_mult"]=[];
            $player["def_mult"]=[];
            $player["sum_dmg"]=0;
            $player["text"]="";
            $player_wep_query_id=$player["user_data"]["weapon"];
            $player_wep_query->execute();
            $raw_player_wep = $player_wep_query->get_result();
            $player["wep"] = $raw_player_wep->fetch_assoc();
            if($player["wep"]["subtype"]=="scalable") {
                $player["wep_dmg"] = rand(($start_hp+$hp_coef*($player["user_data"]["level"]-1))*($player["wep"]["min_dmg"]*0.01), ($start_hp+$hp_coef*($player["user_data"]["level"]-1))*($player["wep"]["max_dmg"]*0.01));
            }else{
                $player["wep_dmg"] = rand($player["wep"]["min_dmg"], $player["wep"]["max_dmg"]);
            }
        }

        //BOT_EXECUTION
        $bot_odds;
        if($bot_check["status"]<0){
            $bot_odds_query_level=$bot_check["level"];
            $bot_odds_query->execute();
            $raw_bot_odds=$bot_odds_query->get_result();
            $bot_odds=$raw_bot_odds->fetch_assoc();

            //BOT_MOVES
            if(rand(1,100)<=$bot_odds["chance_to_move"]){
                if(rand(1,100)<=50){
                    if($duel_step["a_def"]!=1){
                        $duel_step["a_def"]-=1;
                    }else{
                        $duel_step["a_def"]+=1;
                    }
                }else{
                    if($duel_step["a_def"]!=5){
                        $duel_step["a_def"]+=1;
                    }else{
                        $duel_step["a_def"]-=1;
                    }
                }
                $con->query("UPDATE duel_id SET a_def=".$duel_step["a_def"]." WHERE duel=".$duel_end['id']." and step=".$current_step);
            }
            //BOT HITS
            if(rand(1,100)<=$bot_odds["chance_to_hit"]){
                if(($prev_step["c_def"]-$duel_step["c_def"]<=1&&$prev_step["c_def"]-$duel_step["c_def"]>=-1)||($duel_step["c_def"]-$prev_step["c_def"]<=1&&$duel_step["c_def"]-$prev_step["c_def"]>=-1)){
                    $duel_step["a_attack"]=$duel_step["c_def"];
                }else{
                    $temp_arr=[];
                    for($i=1;$i<=5;$i++){
                        if($prev_step["c_def"]!=$i&&$duel_step["c_def"]!=$i)$temp_arr[count($temp_arr)]=$i;
                    }
                    $duel_step["a_attack"]=$temp_arr[rand(0,count($temp_arr)-1)];
                }
            }elseif(rand(1,100<=$bot_odds["chance_to_parthit"])){
                if($prev_step){
                    $duel_step["a_attack"]=$prev_step["c_def"];
                }else{
                    $duel_step["a_attack"]=3;
                }

            }else{
                $temp_arr=[];
                for($i=1;$i<=5;$i++){
                    if($prev_step["c_def"]!=$i&&$duel_step["c_def"]!=$i)$temp_arr[count($temp_arr)]=$i;
                }
                $duel_step["a_attack"]=$temp_arr[rand(0,count($temp_arr)-1)];
            }
            $con->query("UPDATE duel_id SET a_attack=".$duel_step["a_attack"]." WHERE duel=".$duel_end["id"]." and step=".$current_step);

            //BOT_SKILLS
            if($a["ammo"]/($a["wep"]["ammo"]*0.01)<=$bot_odds["when_to_reload"]){
                $con->query("UPDATE `duel_id` set `a_skill`=1 WHERE  duel=".$duel_end["id"]." and step=".$current_step);
                $duel_step["a_skill"]=1;
            }elseif(rand(1,100)<=$bot_odds["chance_to_skill"]){

                $bot_skill_list=[];

                for($i=1;$i<=$skills_amount;$i++){

                    if($bot_check["skill".$i]!=0){

                        $raw_bot_skill=$con->query("SELECT `id`,`cd`,`passive` FROM `skill` WHERE id=".$bot_check["skill".$i]);
                        $bot_skill = mysqli_fetch_assoc($raw_bot_skill);
                        if($bot_skill["passive"]==0){
                            $raw_cd_check = $con->query("SELECT `step` from `duel_id` where duel=".$duel_end["id"]." and a_skill='".$bot_skill["id"]."' and not step=".$current_step." ORDER BY step DESC LIMIT 1");
                            $cd_check = mysqli_fetch_assoc($raw_cd_check);

                            if(!$cd_check||$current_step-$cd_check["step"]>$bot_skill["cd"]){

                                $bot_skill_list[count($bot_skill_list)]=$bot_skill["id"];
                            }
                        }
                    }
                }
                if(count($bot_skill_list)>0){

                    $bot_use_skill=$bot_skill_list[rand(0,count($bot_skill_list)-1)];
                    $skill_effects_query_id=$bot_use_skill;
                    $skill_effects_query->execute();
                    $raw_bot_used_skill=$skill_effects_query->get_result();
                    $bot_used_skill = $raw_bot_used_skill->fetch_assoc();
                    for($i=1;$i<=$effect_amount;$i++){
                        $current_effect_query_id=$bot_used_skill['effect'.$i];
                        $current_effect_query->execute();
                        $raw_roll_check = $current_effect_query->get_result();
                        $roll_check=$raw_roll_check->fetch_assoc();

                        if($roll_check["action"]=="roll"){

                            if($duel_step["a_def"]<$prev_step["a_def"]){
                                if($duel_step["a_def"]!=1){
                                    $duel_step["a_def"]-=1;
                                }else{
                                    $duel_step["a_def"]+=3;
                                }
                            }elseif($duel_step["a_def"]>$prev_step["a_def"]){
                                if($duel_step["a_def"]!=5){
                                    $duel_step["a_def"]+=1;
                                }else{
                                    $duel_step["a_def"]-=3;
                                }
                            }elseif($duel_step["a_def"]==$prev_step["a_def"]){
                                if(rand(1,100)<=50){
                                    if($duel_step["a_def"]>=3){
                                        $duel_step["a_def"]-=2;
                                    }else{
                                        $duel_step["a_def"]+=2;
                                    }
                                }else{
                                    if($duel_step["a_def"]<=3){
                                        $duel_step["a_def"]+=2;
                                    }else{
                                        $duel_step["a_def"]-=2;
                                    }
                                }
                            }

                            $con->query("UPDATE duel_id SET a_def=".$duel_step["a_def"]." WHERE duel=".$duel_end['id']." and step=".$current_step);

                        }
                    }

                    $con->query("UPDATE `duel_id` set `a_skill`=".$bot_use_skill." WHERE  duel=".$duel_end["id"]." and step=".$current_step);
                    $duel_step["a_skill"]=$bot_use_skill;

                }
            }

        }

        //PLAYER PRE EFFECTS

        foreach($players as &$player) {

            //player PASSIVE
            $passive_skills_query_type="pre";
            for ($i = 1; $i <= $skills_amount; $i++) {
                $passive_skills_query_list[$i - 1] = $player["user_data"]["skill" . $i];
            }
            $passive_skills_query->execute();
            $passive_skills=$passive_skills_query->get_result();
            while ($current_skill=$passive_skills->fetch_assoc()) {
                if (!!$current_skill) {
                    $cd_fine = 1;
                    $skill_fine = 1;
                    if ($current_skill["cd"] > 0) {
                        $cd_fine = 0;
                        $cd_check_query_duel=$duel_end["id"];
                        $cd_check_query_event=$player["name"]."_event";
                        $cd_check_query_value="%;" . $current_skill["name"] . "%";
                        $cd_check_query->execute();
                        $raw_cd_check =$cd_check_query->get_result();
                        $cd_check = $raw_cd_check->fetch_assoc();
                        if ($current_step == 1 || $raw_cd_check->num_rows ==0 || $current_step - $cd_check["step"] > $current_skill["cd"]) $cd_fine = 1;

                    }

                    if ($cd_fine == 1 && rand(1, 100) <= $current_skill["chance"]) {

                        for ($j = 1; $j <= $effect_amount; $j++) {
                            if ($current_skill["effect" . $j] > 0) execute_effect($player, $player["target"], $current_skill["effect" . $j]);

                        }
                        $player["text"].=";".$current_skill["name"];

                    }

                }
            }

            //player SKILL
            if ($duel_step[$player["name"]."_skill"] > 0) {
                $current_skill_query_id=$duel_step[$player["name"]."_skill"];
                $current_skill_query_type="pre";
                $current_skill_query->execute();
                $raw_player_skill =  $current_skill_query->get_result();
                if ($raw_player_skill->num_rows>0) {
                    $player["skill"] = $raw_player_skill->fetch_assoc();
                    $player["text"] .= ";" . $player["skill"]["name"];

                    for ($i = 1; $i <= $effect_amount; $i++) {
                        if ($player["skill"]['effect' . $i] > 0) execute_effect($player, $player["target"], $player["skill"]['effect' . $i]);
                    }
                }

            }
        }

        //PLAYERS DAMAGE

        foreach($players as &$player){
            if($player["ammo"]>0&&$duel_step[$player["name"]."_attack"]==$duel_step[$player["target"]["name"]."_def"]){
                $player["text"].=";Hit";
                $player["dmg"][]=$player["wep_dmg"];

            }elseif($player["ammo"]>0){
                if($prev_step==0&&$duel_step[$player["name"]."_attack"]==3&&abs($duel_step[$player["target"]["name"]."_def"]-3)<=1){
                    $player["text"].=";PartHit";
                    $player["dmg"][]=round($player["wep_dmg"]*0.25);
                }elseif($duel_step[$player["name"]."_attack"]==$prev_step[$player["target"]["name"]."_def"] && abs($duel_step[$player["target"]["name"]."_def"]-$prev_step[$player["target"]["name"]."_def"])<=1){
                    $player["text"].=";PartHit";
                    $player["dmg"][]=round($player["wep_dmg"]*0.25);
                }

            }
        }

        //PLAYERS AMMO AND CRIT

        foreach($players as &$player) {
            if (strpos($player["text"], ";Hit") !== false && rand(0, 100) <= $player["wep"]["chance_to_crit"]) {
                $player["dmg_mult"][] = 2;
                $player["text"] .= ";Crit";
            }
            if ($player["ammo"] <= 0) {
                $player["text"] .= ";No ammo";
                $player["dmg_mult"][] = 0;
                $player["ammo"] = 0;
            }

            if ($duel_step[$player["name"]."_attack"] > 0 && $player["ammo"] > 0) {
                $player["ammo"]--;
            }
        }


        //PlAYERS EFFECTS
        foreach($players as &$player) {
            //PLAYER WEAPON EFFECTS
            for ($i = 1; $i <= $weapon_effect_amount; $i++) {
                if ($player["wep"]["effect" . $i] > 0) execute_effect($player, $player["target"], $player["wep"]["effect" . $i]);
            }

            //player PASSIVE

            $passive_skills_query_type="default";
            for ($i = 1; $i <= $skills_amount; $i++) {
                $passive_skills_query_list[$i - 1] = $player["user_data"]["skill" . $i];
            }
            $passive_skills_query->execute();
            $passive_skills=$passive_skills_query->get_result();
            while ($current_skill=$passive_skills->fetch_assoc()) {
                $cd_fine = 1;
                $skill_fine = 1;
                if ($current_skill["cd"] > 0) {
                    $cd_fine = 0;
                    $cd_check_query_duel = $duel_end["id"];
                    $cd_check_query_event = $player["name"] . "_event";
                    $cd_check_query_value = "%;" . $current_skill["name"] . "%";
                    $cd_check_query->execute();
                    $raw_cd_check = $cd_check_query->get_result();
                    $cd_check = $raw_cd_check->fetch_assoc();
                    if ($current_step == 1 || $raw_cd_check->num_rows == 0 || $current_step - $cd_check["step"] > $current_skill["cd"]) $cd_fine = 1;

                }

                if ($cd_fine == 1 && rand(1, 100) <= $current_skill["chance"]) {

                    for ($j = 1; $j <= $effect_amount; $j++) {
                        if ($current_skill["effect" . $j] > 0) execute_effect($player, $player["target"], $current_skill["effect" . $j]);

                    }

                }

            }

            //player SKILL
            if ($duel_step[$player["name"]."_skill"] > 0) {
                $current_skill_query_id=$duel_step[$player["name"]."_skill"];
                $current_skill_query_type="default";
                $current_skill_query_passive=0;
                $current_skill_query->execute();
                $raw_player_skill = $current_skill_query->get_result();
                if ($raw_player_skill->num_rows>0) {
                    $player["skill"] = $raw_player_skill->fetch_assoc();
                    $player["text"] .= ";" . $player["skill"]["name"];

                    for ($i = 1; $i <= $effect_amount; $i++) {
                        if ($player["skill"]['effect' . $i] > 0) execute_effect($player, $player["target"], $player["skill"]['effect' . $i]);
                    }
                }

            }
        }

        //PLAYERS POST EFFECTS

        foreach($players as &$player) {
            $delayed_events = explode("&",$duel_step[$player["name"]."_event"]);
            if(count($delayed_events)>1){
                for($i=1;$i<count($delayed_events);$i++){
                    $event = explode(";",$delayed_events[$i])[0];
                    $current_effect_query_id=$event;
                    $current_effect_query->execute();
                    $raw_delayed_effect=$current_effect_query->get_result();
                    $delayed_effect=$raw_delayed_effect->fetch_assoc();
                    execute_effect($player,$player["target"],$delayed_effect["id"],"post");
                    if ($delayed_effect["length"] > 2) {
                        $cd_check_query_duel=$duel_end["id"];
                        $cd_check_query_event=$player["name"]."_event";
                        $cd_check_query_value="%;" . $current_skill["name"] . "%";
                        $cd_check_query->execute();
                        $raw_cd_check =$cd_check_query->get_result();
                        $cd_check = $raw_cd_check->fetch_assoc();
                        if ($current_step < $cd_check["step"] + $delayed_effect["length"]){
                            $player["text"].=";&".$event;
                            echo $player["text"];

                        }

                    }

                }
            }
        }

        //PLAYERS DAMAGE DONE

        foreach($players as &$player) {

            for ($i = 0; $i < count($player["dmg"]); $i++) {
                $player["sum_dmg"] += $player["dmg"][$i];
            }
            for ($i = 0; $i < count($player["dmg_mult"]); $i++) {
                $player["sum_dmg"] *= $player["dmg_mult"][$i];
            }
            for ($i = 0; $i < count($player["target"]["def_mult"]); $i++) {
                $player["sum_dmg"] *= $player["target"]["def_mult"][$i];
            }
            if ($player["sum_dmg"] == 0) $player["text"] .= ";NoDirectDmg";
            for ($i = 0; $i < count($player["uneffectable_dmg"]); $i++) {
                $temp_coef=1;
                if(isset($player["uneffectable_dmg_mult"][$i]))$temp_coef=$player["uneffectable_dmg_mult"][$i];
                $player["unef_sum_dmg"] += $player["uneffectable_dmg"][$i]*$temp_coef;
            }
            $player["sum_dmg"]+=$player["unef_sum_dmg"];
            $player["sum_dmg"] = round($player["sum_dmg"]);
            $player["target"]["final_hp"]=$duel_step[$player["target"]["name"]."_hp"]-$player["sum_dmg"];
        }

        //PLAYER AFTER EFFECTS

        foreach($players as &$player) {

            //player PASSIVE

            $passive_skills_query_type="after";
            for ($i = 1; $i <= $skills_amount; $i++) {
                $passive_skills_query_list[$i - 1] = $player["user_data"]["skill" . $i];
            }
            $passive_skills_query->execute();
            $passive_skills=$passive_skills_query->get_result();
            while ($current_skill=$passive_skills->fetch_assoc()) {
                $cd_fine = 1;
                $skill_fine = 1;
                if ($current_skill["cd"] > 0) {
                    $cd_fine = 0;
                    $cd_check_query_duel=$duel_end["id"];
                    $cd_check_query_event=$player["name"]."_event";
                    $cd_check_query_value="%;" . $current_skill["name"] . "%";
                    $cd_check_query->execute();
                    $raw_cd_check =$cd_check_query->get_result();
                    $cd_check = $raw_cd_check->fetch_assoc();
                    if ($current_step == 1 || $raw_cd_check->num_rows==0 || $current_step - $cd_check["step"] > $current_skill["cd"]) $cd_fine = 1;

                }

                if ($cd_fine == 1 && rand(1, 100) <= $current_skill["chance"]) {

                    for ($j = 1; $j <= $effect_amount; $j++) {
                        if ($current_skill["effect" . $j] > 0) execute_effect($player, $player["target"], $current_skill["effect" . $j]);

                    }

                }

            }

            //player SKILL
            if ($duel_step[$player["name"]."_skill"] > 0) {
                $current_skill_query_id=$duel_step[$player["name"]."_skill"];
                $current_skill_query_type="after";
                $current_skill_query_passive=0;
                $current_skill_query->execute();
                $raw_current_skill = $current_skill_query->get_result();
                if ($raw_player_skill->num_rows>0) {
                    $player["skill"] = $raw_player_skill->fetch_assoc();
                    $player["text"] .= ";" . $player["skill"]["name"];

                    for ($i = 1; $i <= $effect_amount; $i++) {
                        if ($player["skill"]['effect' . $i] > 0) execute_effect($player, $player["target"], $player["skill"]['effect' . $i]);
                    }
                }

            }
        }


        $con->query("INSERT INTO duel_id(duel,step,`c_def`, `c_attack`, `a_def`, `a_attack`, `c_skill`, `a_skill`, `c_hp`, `a_hp`, `c_ammo`, `a_ammo`, `time`, `a_event`, `c_event`) VALUES (".$duel_end["id"].",".($current_step+1).",'".$duel_step["c_def"]."',0,'".$duel_step["a_def"]."',0,0,0,'".$c["final_hp"]."','".$a["final_hp"]."','".$c["ammo"]."','".$a["ammo"]."',now() + INTERVAL ".$round_time." SECOND,'".$a["text"]."','".$c["text"]."')");
        $con->query("UPDATE `duel` SET `last_update`=now() + INTERVAL ".$round_time." SECOND WHERE `id`='".$duel_end['id']."'");


        //DUEL END CHECK
        if($duel_step["a_hp"]-$c["sum_dmg"]<=0||$duel_step["c_hp"] - $a["sum_dmg"] <= 0 || $duel_step["step"]>$round_max) {

            $duel_end_status=3;
            $winner=-1;
            $loser=-1;
            $reward_l_coef=0.25;
            $reward_w_coef=0.25;
            $win_f_coef=0;
            $lose_f_coef=0;
            $players_hp=mysqli_fetch_assoc($con->query("SELECT c_hp,a_hp from duel_id where duel=".$duel_end['id']." and step=1"));

            //rewarding
            if($duel_step["c_hp"] - $a["sum_dmg"] > 0 && $duel_step["a_hp"] - $c["sum_dmg"] <= 0) {

                update_statistic($duel_end["a"],["duels_done"=>1,"duels_lost"=>1,"duels_killed_in"=>1]);
                update_statistic($duel_end["c"],["duels_done"=>1,"duels_won"=>1,"duels_survived"=>1,"duels_kills"=>1]);

                $win_f_coef=1;
                $lose_f_coef=-1;

                $winner_fame=$c["user_data"]["fame"];
                $loser_fame=$a["user_data"]["fame"];
                $winner_level=$c["user_data"]["level"];
                $loser_level=$a["user_data"]["level"];
                $winner_hp=$duel_step["c_hp"]-$a["sum_dmg"];
                $winner_max_hp=$players_hp["c_hp"];
                $loser_hp = 0;
                $loser_max_hp=$players_hp["a_hp"];

                $winner=$duel_end["c"];
                $loser=$duel_end["a"];
                $duel_end_status=$winner;
                $reward_l_coef=0;
                $reward_w_coef=1;

            }elseif($duel_step["c_hp"] - $a["sum_dmg"] <= 0 && $duel_step["a_hp"] - $c["sum_dmg"] > 0) {

                update_statistic($duel_end["c"],["duels_done"=>1,"duels_lost"=>1,"duels_killed_in"=>1]);
                update_statistic($duel_end["a"],["duels_done"=>1,"duels_won"=>1,"duels_survived"=>1,"duels_kills"=>1]);

                $win_f_coef=1;
                $lose_f_coef=-1;

                $winner_fame=$a["user_data"]["fame"];
                $loser_fame=$c["user_data"]["fame"];
                $winner_level=$a["user_data"]["level"];
                $loser_level=$c["user_data"]["level"];
                $winner_hp=$duel_step["a_hp"]-$c["sum_dmg"];
                $winner_max_hp=$players_hp["a_hp"];
                $loser_hp = 0;
                $loser_max_hp=$players_hp["c_hp"];

                $winner=$duel_end["a"];
                $duel_end_status=$winner;
                $loser=$duel_end["c"];
                $reward_l_coef=0;
                $reward_w_coef=1;
            }elseif($duel_step["c_hp"] - $a["sum_dmg"] <= 0 && $duel_step["a_hp"] - $c["sum_dmg"] <= 0){

                update_statistic($duel_end["c"],["duels_done"=>1,"duels_killed_in"=>1,"duels_kills"=>1]);
                update_statistic($duel_end["a"],["duels_done"=>1,"duels_killed_in"=>1,"duels_kills"=>1]);

                $win_f_coef=0;
                $lose_f_coef=0;

                $winner_fame=1;
                $loser_fame=1;
                $winner_level=1;
                $loser_level=1;
                $winner_hp=0;
                $winner_max_hp=$players_hp["a_hp"];
                $loser_hp = 0;
                $loser_max_hp=$players_hp["c_hp"];

                $winner=$duel_end["a"];
                $duel_end_status=-1;
                $loser=$duel_end["c"];
                $reward_l_coef=0.25;
                $reward_w_coef=0.25;
            }elseif($duel_step["step"]>$round_max){
                $a_hp=$duel_step["a_hp"]-$c["sum_dmg"];
                $c_hp=$duel_step["c_hp"]-$a["sum_dmg"];
                if($a_hp<=0&&$c_hp<=0){
                    if($c_hp>$a_hp){

                        update_statistic($duel_end["a"],["duels_done"=>1,"duels_lost"=>1,"duels_survived"=>1]);
                        update_statistic($duel_end["c"],["duels_done"=>1,"duels_won"=>1,"duels_survived"=>1,"duels_outlived"=>1]);

                        $win_f_coef=1;
                        $lose_f_coef=-1;

                        $winner_fame=$c["user_data"]["fame"];
                        $loser_fame=$a["user_data"]["fame"];
                        $winner_level=$c["user_data"]["level"];
                        $loser_level=$a["user_data"]["level"];
                        $winner_hp=$duel_step["c_hp"]-$a["sum_dmg"];
                        $winner_max_hp=$players_hp["c_hp"];

                        $winner=$duel_end["c"];
                        $duel_end_status=$winner;
                        $loser=$duel_end["a"];
                        $reward_l_coef=0.25;
                        $reward_w_coef=0.75;
                    }elseif($duel_step["c_hp"]-$a["sum_dmg"]<$duel_step["a_hp"]-$c["sum_dmg"]) {

                        update_statistic($duel_end["c"], ["duels_done" => 1, "duels_lost" => 1, "duels_survived" => 1]);
                        update_statistic($duel_end["a"], ["duels_done" => 1, "duels_won" => 1, "duels_survived" => 1, "duels_outlived" => 1]);

                        $win_f_coef = 1;
                        $lose_f_coef = -1;

                        $winner_fame = $a["user_data"]["fame"];
                        $loser_fame = $c["user_data"]["fame"];
                        $winner_level = $a["user_data"]["level"];
                        $loser_level = $c["user_data"]["level"];
                        $winner_hp = $duel_step["a_hp"] - $c["sum_dmg"];
                        $winner_max_hp = $players_hp["a_hp"];

                        $winner = $duel_end["a"];
                        $duel_end_status = $winner;
                        $loser = $duel_end["c"];
                        $reward_l_coef = 0.25;
                        $reward_w_coef = 0.75;
                    }
                }else{

                    update_statistic($duel_end["c"],["duels_done"=>1,"duels_survived"=>1]);
                    update_statistic($duel_end["a"],["duels_done"=>1,"duels_survived"=>1]);

                    $win_f_coef=0;
                    $lose_f_coef=0;

                    $winner_fame=1;
                    $loser_fame=1;
                    $winner_level=1;
                    $loser_level=1;
                    $winner_hp = $a_hp>=0 ? $a_hp : 0;
                    $winner_max_hp=$players_hp["a_hp"];
                    $loser_hp = $c_hp>=0 ? $c_hp : 0;
                    $loser_max_hp=$players_hp["c_hp"];

                    $winner=$duel_end["a"];
                    $duel_end_status=-1;
                    $loser=$duel_end["c"];
                    $reward_l_coef=0.25;
                    $reward_w_coef=0.25;
                }

            }

            $fame_diff=$winner_fame-$loser_fame;
            $temp_coef=1;
            if($fame_diff>0) {
                0 <= $fame_diff && $fame_diff < 100 ? $temp_coef = 1 - abs($fame_diff) * 0.005 : $temp_coef = 0.5;
            }else{
                -100 < $fame_diff && $fame_diff <= 0 ? $temp_coef = 1 + abs($fame_diff) * 0.01 : $temp_coef = 2;
            }

            $lose_f_coef*=$temp_coef;
            $win_f_coef*=$temp_coef;
            $winner_hp_left=$winner_hp/$winner_max_hp;
            $hp_done=1-$winner_hp_left;
            $lose_f_coef*=1-$hp_done/5;
            $win_f_coef*=1+$winner_hp_left/5;
            $lose_f_coef*=$loser_level*0.01+0.5;
            if($current_step<10)$win_f_coef*=1+$current_step*0.1;
            $undercap=$wins_for_level[$loser_level-1]*$default_fame;
            $overcap=$wins_for_level[$loser_level]*$default_fame;
            if($winner_fame<=$undercap)$win_f_coef*=2;
            if($loser_fame<=$undercap)$lose_f_coef*=0;
            if($loser_fame>=$overcap)$lose_f_coef*=2;
            $winner_gold=($default_reward + $winner_level* $gold_coef * $default_reward )*$reward_w_coef;
            $loser_gold=($default_reward + $loser_level* $gold_coef * $default_reward)* $reward_l_coef;
            if($loser_level>=$gold_loss_level)$loser_gold=$winner_gold*$gold_loss_coef*-1;

            update_statistic($winner,["gold_earned"=>$winner_gold,"gold_won"=>$winner_gold,"damage_taken"=>$winner_max_hp-$winner_hp,"damage_done"=>$loser_max_hp-$loser_hp]);
            update_statistic($loser,["gold_earned"=>$loser_gold,"gold_won"=>$loser_gold,"damage_taken"=>$loser_max_hp-$loser_hp,"damage_done"=>$winner_max_hp-$winner_hp]);

            $con->query("UPDATE `duel` SET `finished`=1 WHERE `id`='" . $duel_end['id'] . "'");
            $con->query("INSERT INTO `duel_log`(`id`,`creator`,`acceptor`,`winner`,`time`) VALUES('" . $duel_end['id'] . "','" . $duel_end["c"] . "','" . $duel_end["a"] . "','" . $duel_end_status . "',now())");
            $con->query("UPDATE `login` set fame=fame+".round($default_fame*$lose_f_coef).", gold=gold+" . $loser_gold . " WHERE status>=0 and id=" . $loser);
            $con->query("UPDATE `login` set fame=fame+".round($default_fame*$win_f_coef).", gold=gold+" .$winner_gold. " WHERE status>=0 and id=" . $winner);
        }

        $con->query("UPDATE `login` set hp=".$duel_step["c_hp"]." WHERE id=".$duel_end["c"]." and status >= 0");
        $con->query("UPDATE `login` set hp=".$duel_step["a_hp"]." WHERE id=".$duel_end["a"]." and status >= 0");
    }


    $con->query("UPDATE `login` set gold=0 WHERE gold<0");

    //HP regen
    $con->query("UPDATE `login` set hp=0 WHERE hp<0");
    $hp_regen_query=$con->prepare("UPDATE `login` set hp=hp+".$hp_per_sec." where hp < max_hp and status between ? and ?");
    $hp_regen_query->bind_param("ii",$hp_reget_query_min_status,$hp_reget_query_max_status);
    $hp_reget_query_min_status=0;
    $hp_reget_query_max_status=$vip_status+2;
    $hp_regen_query->execute();
    $hp_reget_query_min_status=$vip_status;
    $hp_reget_query_max_status=$vip_status+2;
    $hp_regen_query->execute();
    $con->query("UPDATE `login` set hp=hp+".$hp_per_sec." where hp < max_hp and status>= 0 and last_chat_renewal> now() - INTERVAL 2 SECOND");
    $con->query("UPDATE `login` set hp=max_hp WHERE hp > max_hp");

    #remove_finished_duels
    $raw_finished_duel=$con->query("SELECT id,last_update FROM `duel` WHERE finished<0");

    $delete_duel_query=$con->prepare("Delete from duel_id where duel=?");
    $delete_duel_query->bind_param("i",$delete_duel_query_id);
    $delete_duel_entry_query=$con->prepare("Delete from duel where id=?");
    $delete_duel_entry_query->bind_param("i",$delete_duel_query_id);

    while($finished_duel = mysqli_fetch_assoc($raw_finished_duel)){
        $delete_duel_query_id=$finished_duel['id'];
        $delete_duel_query->execute();
    }
    $con->query("DELETE FROM `duel` WHERE finished<0");
    $raw_finished_duel=$con->query("SELECT * FROM `duel` WHERE finished>0");
    while($finished_duel = mysqli_fetch_assoc($raw_finished_duel)){
        if(strtotimeNOW()-strtotime($finished_duel["last_update"])>$delete_duel_after){
            $delete_duel_query_id=$finished_duel['id'];
            $delete_duel_query->execute();
        }

        if(strtotimeNOW()-strtotime($finished_duel["last_update"])>10800){
            $delete_duel_query_id=$finished_duel['id'];
            $delete_duel_query->execute();
            $delete_duel_entry_query->execute();
        }
        if(!$finished_duel["last_update"]){
            $delete_duel_query_id=$finished_duel['id'];
            $delete_duel_query->execute();
            $delete_duel_entry_query->execute();
        }
    }

    #add bot for waiting
    $raw_late_duel = $con->query("SELECT `id`,`creator` FROM `duel` WHERE `started`=0 and `acceptor`=0 and `added`< now() - INTERVAL $bot_search_default SECOND UNION SELECT `id`,`creator` FROM `duel` outter WHERE `started`=0 and `acceptor`=0 and exists(SELECT id from login where status>=4 and id=outter.creator) and `added`< now() - INTERVAL $bot_search_vip SECOND");
    while($late_duel=mysqli_fetch_assoc($raw_late_duel)){
        $raw_waiting_player = $con->query("SELECT `level` FROM login WHERE id=".$late_duel["creator"]);
        $waiting_player=mysqli_fetch_assoc($raw_waiting_player);
        $raw_find_bot = $con->query("SELECT `id` from login where status=-1 and level=".$waiting_player["level"]." ORDER BY `id` DESC LIMIT ".rand(0,1).",3");
        $find_bot = mysqli_fetch_assoc($raw_find_bot);
        $con->query("UPDATE duel set acceptor=".$find_bot["id"]." WHERE `id`=".$late_duel["id"]);
    }

    //Find Opponent
    $raw_new_duel = $con->query("SELECT `creator`,`id` FROM `duel` WHERE `creator`>0 and `acceptor`=0 and `started`=0 and `finished`=0");
    $new_duel_list = [];
    while($new_duel=mysqli_fetch_assoc($raw_new_duel)){
        $raw_cr = $con->query("SELECT level FROM `login` WHERE `id`='".$new_duel['creator']."'");
        $cr = mysqli_fetch_assoc($raw_cr);
        $new_duel_list[count($new_duel_list)] =['id'=>$new_duel['id'],'cr'=>$new_duel['creator'],'level'=>$cr["level"]];
    }
    for($i=0;$i<count($new_duel_list)-1;$i++){
        for($j=$i+1;$j<count($new_duel_list);$j++){
            if($new_duel_list[$i]['level'] - $new_duel_list[$j]['level']>=-1||$new_duel_list[$i]['level'] - $new_duel_list[$j]['level']<=1){
                $con->query("UPDATE `duel` SET acceptor='".$new_duel_list[$j]['cr']."' WHERE `id`='".$new_duel_list[$i]['id']."'");
                $con->query("DELETE FROM `duel` WHERE creator='".$new_duel_list[$j]['cr']."'");
                array_splice($new_duel_list,$i,1);
                array_splice($new_duel_list,$j,1);
            }
        }
    }


    //Start Duel
    $raw_duel = $con->query("SELECT * FROM `duel` WHERE `creator`>0 and `acceptor`>0 and `started`=0 and `finished`=0");

    $select_ammo_query=$con->prepare("SELECT ammo from knowledge_base where type='weapon' and id=?");
    $select_ammo_query->bind_param("i",$select_ammo_query_id);
    $check_player_query=$con->prepare("SELECT online,weapon,hp from `login` Where `id`=?");
    $check_player_query->bind_param("i",$check_player_query_id);

    while($data=mysqli_fetch_assoc($raw_duel)){
        if($data["creator"]!=$data["acceptor"]){
            $check_player_query_id=$data["creator"];
            $check_player_query->execute();
            $raw_check_c =$check_player_query->get_result();
            $check_player_query_id=$data["acceptor"];
            $check_player_query->execute();
            $raw_check_a =$check_player_query->get_result();
            $check_c=mysqli_fetch_assoc($raw_check_c);
            $check_a=mysqli_fetch_assoc($raw_check_a);
            if(+$check_a["online"]&&+$check_c["online"]){

                $con->query("UPDATE `duel` SET `finished`=-1 WHERE `creator`='".$data["acceptor"]."' and `finished`=0 and `started`=0");
                $con->query("UPDATE `duel` SET `started`=1, last_update=now() + INTERVAL ".($round_time+5)." second WHERE `id`='".$data['id']."'");

                $select_ammo_query_id=$check_a["weapon"];
                $select_ammo_query->execute();
                $raw_a_wep_ammo=$select_ammo_query->get_result();
                $select_ammo_query_id=$check_c["weapon"];
                $select_ammo_query->execute();
                $raw_c_wep_ammo=$select_ammo_query->get_result();
                $c_wep_ammo=mysqli_fetch_assoc($raw_c_wep_ammo);
                $a_wep_ammo=mysqli_fetch_assoc($raw_a_wep_ammo);
                $con->query("INSERT INTO `duel_id`(`duel`,`step`, `c_def`, `c_attack`, `a_def`, `a_attack`, `c_skill`, `a_skill`, `c_hp`, `a_hp`, `c_ammo`, `a_ammo`,`time`) VALUES (
					".$data["id"].",1,3,0,3,0,0,0,".$check_c["hp"].",".$check_a["hp"].",".$c_wep_ammo["ammo"].",".$a_wep_ammo["ammo"].",now())");


            }
        }
    }

    #DECAY FAME GOLD
    $decay_check = mysqli_fetch_assoc($con->query("SELECT last_use,frequency from `oddmans_timing` where action='decay'"));

    if($decay_check ["last_use"]+$decay_check ["frequency"]*1000<=round(microtime(true)*1000)) {
        $con->query("UPDATE login set fame=fame*0.9 where status>=0 and level>=$fame_decay_level and fame>round((level*$level_inc_coef+0.5)*level)*$default_fame");
        $con->query("UPDATE login set gold=gold*0.9 where status>=0 and level>=$gold_loss_level");
        $con->query("UPDATE oddmans_timing set last_use=".round(microtime(true)*1000)." where action='decay'");
    }

    #RENEW BOT

    //$con->query("UPDATE `login` set fame=round((level*$level_inc_coef+0.5)*level)*$default_fame*1.2 WHERE status<0 and fame<round((level*$level_inc_coef+0.5)*level)*$default_fame*1.2");

    $bot_renew_check = mysqli_fetch_assoc($con->query("SELECT last_use,frequency from `oddmans_timing` where action='disable_bot'"));
    if($bot_renew_check["last_use"]+$bot_renew_check["frequency"]*1000<=round(microtime(true)*1000)){

        $bots_to_update=$con->query("Select id,level from login where status=-2")->fetch_All(MYSQLI_ASSOC);
        for($i=0;$i<count($bots_to_update);$i++){
            $new_hp=$start_hp+$hp_coef*($bots_to_update[$i]["level"]-1);

            $new_skills_amount=$skills_amount;
            for($z=1;$z<=$skills_amount;$z++) {
                if(($z-1)*$skill_unlock_level>$bots_to_update[$i]["level"])$new_skills_amount--;
            }

            $new_skills=$con->query("SELECT id,passive,".$effect_amount_text." from skill where level<=".$bots_to_update[$i]['level']." and id>1 ORDER by rand() limit ".$new_skills_amount)->fetch_All(MYSQLI_ASSOC);

            $new_weapon=mysqli_fetch_assoc($con->query("SELECT id,".$weapon_effect_amount_text." from knowledge_base where (type='weapon' and level=".$bots_to_update[$i]['level'].") or id=".$default_weapon." order by rand() limit 1"));
            $new_skin=mysqli_fetch_assoc($con->query("Select id from knowledge_base where type='skin' order by rand() limit 1"));
            $new_skills_text="skill1=".$new_skills[0]["id"];
            for($j=2;$j<=$skills_amount;$j++){
                if(isset($new_skills[$j-1])) {
                    $new_skills_text .= ",skill" . $j . "=" . $new_skills[$j - 1]["id"];
                }else{
                    $new_skills_text .= ",skill" . $j . "=0";
                }
            }
            for($j=1;$j<=$weapon_effect_amount;$j++){
                $item_effect=mysqli_fetch_assoc($con->query("SELECT action,percentage from effect where id=".$new_weapon["effect".$j]));
                if($item_effect["action"]=="health"){
                    $new_hp=$new_hp*($item_effect["percentage"]*0.01);
                }
            }
            for($j=1;$j<=$skills_amount;$j++){
                if(isset($new_skills[$j-1])) {
                    if ($new_skills[$j - 1]["passive"] == 1) {
                        for ($z = 1; $z <= $effect_amount; $z++) {
                            if ($new_skills[$j - 1]["effect" . $z] > 0) {
                                $skill_effect = mysqli_fetch_assoc($con->query("SELECT action,percentage from effect where id=" . $new_skills[$j - 1]["effect" . $z]));
                                if ($skill_effect["action"] == "health") {
                                    $new_hp = $new_hp * ($skill_effect["percentage"] / 100);
                                }
                            }
                        }
                    }
                }
            }
            $con->query("UPDATE login set skin=".$new_skin["id"].",weapon=".$new_weapon["id"].",".$new_skills_text.",max_hp=".$new_hp.",hp=max_hp, last_renewal=now(),gold=0 WHERE id=".$bots_to_update[$i]["id"]);
        }
        $con->query("Update login set status=-1 where status=-2");
        $con->query("Update login set status=-2 where id in (SELECT * from (SELECT log.id FROM login log WHERE log.status=-1 and (log.last_renewal, log.level) in(select min(last_renewal),level from login sub_log where sub_log.status=-1 group by sub_log.level) group by log.level) alias)");
        echo "shit just got real";
        $con->query("UPDATE oddmans_timing set last_use=".round(microtime(true)*1000)." WHERE action='disable_bot'");
    }


    #Quest Setting to users

    $quest_reset_check = mysqli_fetch_assoc($con->query("SELECT last_use,value,frequency from `oddmans_timing` where action='reset_quests'"));
    if($quest_reset_check["last_use"]+$quest_reset_check["frequency"]*1000<=round(microtime(true)*1000)) {
        $con->query("DELETE from quest_log where persistent<=0");
        $quest=$con->query("SELECT id,amount,field from quest_list where special=0 and level > 0 order by rand() limit 3")->fetch_All(MYSQLI_ASSOC);
        $special_quest=0;
        if(!!$quest_reset_check["value"]){
            $special_quest=mysqli_fetch_assoc($con->query("SELECT id,amount,field,level from quest_list where id=".$quest_reset_check["value"]));
            $special_quest_check=$con->prepare("SELECT id from quest_log where quest=".$special_quest["id"]." and user=?");
            $special_quest_check->bind_param("i", $sp_quest_user);
        }
        $fields=[];
        foreach($quest as $field){
            $fields[]=$field["field"];
        }
        if($special_quest!=0)$fields[]=$special_quest["field"];
        $fields=implode(",",$fields);
        $normal_users=$con->query("SELECT login.id,login.level,".$fields." from user_statistic LEFT JOIN login on login.id=user_statistic.id where status between 2 and ".($vip_status-1))->fetch_All(MYSQLI_ASSOC);
        $vip_users=$con->query("SELECT login.id,login.level,".$fields." from user_statistic LEFT JOIN login on login.id=user_statistic.id where status >=".$vip_status)->fetch_All(MYSQLI_ASSOC);
        $insert_values=[];
        foreach($normal_users as $user){
            if($special_quest!=0&&$special_quest["level"]<=$user["level"]){
                $sp_quest_user=$user["id"];
                $special_quest_check->execute();
                $result = $special_quest_check->get_result();
                if(!$result->fetch_assoc())$insert_values[]="(".$user["id"].",".$special_quest["id"].",".($user[$special_quest["field"]]+$special_quest["amount"]).",1)";
            }

            $rand_num=rand(0,count($quest)-1);
            $insert_values[]="(".$user["id"].",".$quest[$rand_num]["id"].",".($user[$quest[$rand_num]["field"]]+$quest[$rand_num]["amount"]).",0)";
        }
        foreach($vip_users as $user){
            if($special_quest!=0&&$special_quest["level"]<=$user["level"]){

                $sp_quest_user=$user["id"];
                $special_quest_check->execute();
                $result = $special_quest_check->get_result();
                if(!$result->fetch_assoc())$insert_values[]="(".$user["id"].",".$special_quest["id"].",".($user[$special_quest["field"]]+$special_quest["amount"]).",1)";
            }
            foreach($quest as $cur_quest) {
                $insert_values[] = "(" . $user["id"] . "," . $cur_quest["id"] . "," . ($user[$cur_quest["field"]] + $cur_quest["amount"]) . ",0)";
            }
        }
        $insert_values=implode(",",$insert_values);
        $con->query("INSERT INTO quest_log(user,quest,value,persistent) VALUES ".$insert_values);

        $con->query("UPDATE oddmans_timing set last_use=".round(microtime(true)*1000)." WHERE action='reset_quests'");
    }

    #Remove trials
    $con->query("DELETE FROM email_update where date<=now() - INTERVAL 1 day");
    $con->query("DELETE FROM login where status between 0 and 1 and added<=now() - INTERVAL 3 day");
    $con->query("DELETE FROM user_statistic where not exists(SELECT id from login where id=user_statistic.id)");
    $con->query("DELETE FROM quest_log where not exists(SELECT id from login where id=quest_log.user)");

}else{
    echo "too goddamn often";
}

echo 'Total execution time in seconds: ' . (microtime(true) - $time_start).'. memory used in bytes : '.memory_get_usage();

$con->close();

?>