<?php

    //DONT FORGET ABOUT SECOND FILE
    $con = new Mysqli("localhost","root","","tester");
    $effect_amount=5;
    $effect_amount_text="effect1";
    for($i=2;$i<=$effect_amount;$i++)$effect_amount_text=$effect_amount_text.",effect".$i;
    $skills_amount=5;
    $skills_amount_text="skill1";
    for($i=2;$i<=$skills_amount;$i++)$skills_amount_text=$skills_amount_text.",skill".$i;
    $weapon_effect_amount=3;
    $weapon_effect_amount_text="effect1";
    for($i=2;$i<=$weapon_effect_amount;$i++)$weapon_effect_amount_text=$weapon_effect_amount_text.",effect".$i;

    $skill_unlock_level=5;
    $default_reward=100;
    $default_weapon= "5";
    $default_skin="1";
    $gold_for_hp=1;
    $gold_coef=0.13;
    $hp_coef=30;
    $start_hp=120;
    $hp_per_sec=5;
    //CHANGE THOSE TOGETHER
    $max_level=50;
    $level_coef=3;
    $level_inc_coef=0.166667;
    $wins_for_level=[1, 2, 3, 5, 7, 9, 12, 15, 18, 22, 26, 30, 35, 40, 45, 51, 57, 63, 70, 77, 84, 92, 100, 108, 117, 126, 135, 145, 155, 165, 176, 187, 198, 210, 222, 234, 247, 260, 273, 287, 301, 315, 330, 345, 360, 376, 392, 408, 425, 442, 459];
    //CHANGE THOSE TOGETHER
    $decoration_wall_amount = 2;
    $decoration_floor_amount = 2;
    $decoration_table_amount = 1;
    $decoration_amount = $decoration_wall_amount + $decoration_floor_amount + $decoration_table_amount;

    $gold_loss_level=40;
    $gold_loss_coef=0.75;
    $fame_decay_level=25;

    $default_fame=7;

    $inventory_cap=20;

    $round_time = 13;
    $round_max = 15;

    $vip_status=4;

    $bot_search_vip=5;
    $bot_search_default=10;
    $delete_duel_after=15;

    $system_events=["NoDirectDmg"];

    $time_differ=null;
    function strtotimeNOW(){
        global $time_differ,$con;
        if($time_differ==null){
            $time_server=$con->query("select now()")->fetch_row()[0];
            $time_differ=(strtotime($time_server)-strtotime("now"));
            $time_differ=$time_differ-$time_differ%60;
        }
        return strtotime("now")+$time_differ;
    }

    function award_achievement($user,$achieve){
        global $con;
        $achieve=$con->query("SELECT id,amount,field,reward from achievement_list where id=$achieve")->fetch_assoc();
        if(!!$achieve) {
            $con->query("INSERT INTO achivement_log(user,achievemnt) VALUE($user," . $achieve["id"] . ")");
            if ($achieve["reward"] > 0) {
                $reward_type = mysqli_fetch_assoc($con->query("SELECT type from knowledge_base where id=" . $achieve["reward"]))["type"];
                if (!!$reward_type) {
                    $con->query("INSERT INTO inventory(user,item,type) VALUE($user," . $achieve["reward"] . ",$reward_type)");
                }
            }
        }
    }

function update_statistic($user,$stat){
    global $con;
    global $default_reward,$gold_coef,$level_coef;
    $stats=[];
    $fields=[];
    $fields_text=[];
    foreach($stat as $key => $value){
        $stats[]=$key." = ".$key."+".$value;
        $fields[]="`".$key."`";
        $fields_text[]="'".$key."'";
    }
    $fields=implode(",",$fields);
    $fields_text=implode(",",$fields_text);
    $stats=implode(", ",$stats);
    $con->query("UPDATE user_statistic set ".$stats." WHERE id=".$user);
    $user_stats=mysqli_fetch_assoc($con->query("SELECT $fields from user_statistic where id=".$user));
    $dependent_achieves=$con->query("SELECT id,amount,field,reward from achievement_list where field in($fields_text) and not id = any(SELECT achievement from achievement_log where user=$user) ORDER BY amount")->fetch_All(MYSQLI_ASSOC);
    $dependent_quests=$con->query("SELECT quest_list.id,persistent,field,value,reward_type,reward from quest_list left join quest_log on quest=quest_list.id where field in ($fields_text) and complete=0 and user=".$user)->fetch_All(MYSQLI_ASSOC);
    $user_level=null;

    foreach($dependent_achieves as $achieve) {
        if(+$user_stats[$achieve["field"]]>=+$achieve["amount"]) {
            $con->query("INSERT INTO achievement_log(user,achievement) VALUE($user,".$achieve["id"].")");
            if($achieve["reward"]>0) {
                $reward_type = mysqli_fetch_assoc($con->query("SELECT type from knowledge_base where id=" . $achieve["reward"]))["type"];
                if (!!$reward_type) {
                    $con->query("INSERT INTO inventory(user,item,type) VALUE($user," . $achieve["reward"] . ",$reward_type)");
                }
            }
        }else{
            break;
        }
    }

    foreach($dependent_quests as $quest){
        if(+$user_stats[$quest["field"]]>=+$quest["value"]){
            update_statistic($user,["quests_completed"=>1]);
            if($quest["reward_type"]=="gold"){
                if($user_level==null)$user_level=mysqli_fetch_assoc($con->query("SELECT level from login where id=".$user))["level"];
                $reward=($default_reward*$gold_coef*$user_level+$default_reward)*ceil($user_level/$level_coef)*($quest["reward"]*0.01);
                $con->query("UPDATE login set gold=gold+".$reward." where id=".$user);
                $con->query("DELETE from quest_log where quest=".$quest["id"]." and user=".$user);
            }
            if($quest["reward_type"]=="item") {
                $item=$con->query("SELECT type from knowledge_base where id=".$quest["reward"]);
                if($item->num_rows>0) {
                    $item_owned=$con->query("SELECT id from inventory where item=".$quest["reward"]);
                    if($item_owned->num_rows==0) {
                        $item_type = mysqli_fetch_assoc($item)["type"];
                        $con->query("INSERT into inventory(user,item,type) value(" . $user . "," . $quest["reward"] . ",'" . $item_type . "')");
                    }
                    if($quest["persistent"]==0){
                        $con->query("DELETE from quest_log where quest=" . $quest["id"] . " and user=" . $user);
                    }else{
                        $con->query("UPDATE quest_log set complete=1 where quest=" . $quest["id"] . " and user=" . $user);
                    }
                }
            }
        }
    }

    return 1;
}

?>