$("body *").addClass("force_display");

$("#login_form_holder div > *").on("focus",(e)=>{
	$("#new_game_holder").css("position", "absolute");
	$("#new_game_holder").hide();
	$(e.currentTarget).on("blur",()=>{
		if($("#login_form_holder div > *:focus").length==0) {
			$("#new_game_holder").css("position", "relative");
			$("#new_game_holder").show();
		}
	});
});


$("input").on("focus",(e)=>{
	let w = window.innerWidth;
	let h = window.innerHeight;
	let viewport = document.querySelector("meta[name=viewport]");
	viewport.setAttribute("content", "height=" + h + ", width=" + w + ", initial-scale=1.0, user-scalable=no");
	$("html").css("overflow-y","auto");
	//alert(window.innerHeight-document.clientHeight);
	//protocol(2);
	$(e.currentTarget).on("blur",()=>{
		if($("input:focus").length==0){
			let viewport = document.querySelector("meta[name=viewport]");
			viewport.setAttribute("content", "width=device-width, initial-scale=1.0, height=device-height, user-scalable=no");
			$("html").css("overflow-y","");
			$("html").scrollTop(0);
		}
		$(e.currentTarget).off("blur");
	});
});

try{

	window.onload = ()=>{
		$("body *").removeClass("force_display");
		$.get("php/check_session.php",(response)=>{
			if(+response){
				$("#login").submit();
			}else{
				$("#loading_screen").hide();
			}
		});
	}

	$("#new_game").click(()=>protocol(5));

	$(".password_toggle").click((e)=>{
		let target = $(e.currentTarget).siblings('input')[0];
		$(e.currentTarget).toggleClass("closed");
		target.type == "password" ? target.type = "text" : target.type = "password";
	});

	var request_user_data = ()=>{};
	var request_user_gear = ()=>{};
	var request_items = ()=>{};

	$("#login_submit").on("click touchstart",()=>$("#login").submit());

	$("#login").submit((e)=>{
		$.post("php/login.php",{
			login: $("#login_login").val(),
			password: $("#login_password").val()
		},(response)=>{
			if(!(+response)){
				alert_this(response);
				$("#login_password").val('');
				$("#loading_screen").hide();
				let log_input=$("#login input");
				log_input.css("border-color","red");
				log_input.change(()=>log_input.css("border-color","unset"));
			}else{
				//FISRT STAGE PASSED
				$("#login_password").val('');
				$("#loading_screen").show();

				var renew_connection = ()=>{
					$.ajax({url:"php/renew_connection.php", success: (r)=>{
							if(r){
								if(+$(".user_gold").text()!=+r.gold)$(".user_gold").text(r.gold);

								if(+r.active_duel){
									if(!duel_timer)check_duel();
								}

								if(!protocol_affects_hp) {
									$(".user_hp_bar > .user_hp").css("width", r.hp / (r.max_hp / 100) + "%");
									$(".user_hp_bar > span").text(r.hp + "/" + r.max_hp);
									//not full hp
									if (+r.hp < +r.max_hp) {
										$("#hp_price").text(r.hp_price);
										let hp_time_sec = r.hp_time % 60 >= 10 ? r.hp_time % 60 : "0" + r.hp_time % 60;
										let hp_time_min = Math.floor(r.hp_time / 60) >= 10 ? Math.floor(r.hp_time / 60) : "0" + Math.floor(r.hp_time / 60);
										$("#time_till_full_hp").text(hp_time_min + ":" + hp_time_sec);
										$("#buy_hp_holder").show();
									}

									if (+r.hp >= +r.max_hp) {
										$("#buy_hp_holder").hide();
									}
								}
								//new items received
								if(r.new_items.length>0){
									$("#new_item_display .new_item").remove();
									for (let i = 0; i < r.new_items.length; i++) {
										if(i<3){
											let temp=r.new_items[i].type;
											if(r.new_items[i].type=="weapon")temp="weapon/side";
											if(r.new_items[i].type=="skin")temp="player/skin";
											$("#new_item_display .loader").append('<div class="new_item '+r.new_items[i].type+'_icon" style="background-image:url(img/'+temp+'/'+r.new_items[i].picture+');"></div>');
											if(r.new_items[i].type=="title"){
												$("#home_title_choice .loader").append(`<div class="home_item_title" data-item-id="${r.new_items[i].id}"><div class="home_title_name"><span>${r.new_items[i].name}</span><div class="button slim home_item_equip">Use</div></div><div class="home_title_description">${r.new_items[i].description}</div></div>`);
												$("#home_title_choice .home_item_equip:last").click((e)=>{
													$.post("php/equip_item.php",{
															item:$(e.target).closest(".home_item")[0].dataset.itemId
														},(response)=>{
															console.log(response);
															alert_this(response);
															if(response!=="0")request_user_gear();
														}
													);
												});
											}
										}
									}
									$("#new_item_extra span").show();
									r.length>3?$("#new_item_extra span").text(r.length-3):$("#new_item_extra").hide();
									$("#new_item_display").show();
								}

								if(r.protocol){
									protocol(r.protocol);
								}
								//new achievement completed
								if(r.new_achieves.length>0){
									let text_to_append="";
									for(let i=0;i<r.new_achieves.length;i++){
										let amount=$(".achivement").length;
										if(amount%12==0)$("#home_memory_book .book_page:first").before(`<div class="book_page"></div>`);
										if(amount%6==0)$("#home_memory_book .book_page:first").append(`<div class="book_page_part"></div>`);
										$("#home_memory_book .book_page:first .book_page_part:last").append(`<div class="achievment" data-achievement-id="${r.new_achieves[i].name}"><div>${r.new_achieves[i].name}</div><div>${r.new_achieves[i].name}</div></div>`);
									}
								}
							}
						},error: (xhr,status,error)=>{
							protocol(0);
						}
					});
					if($("#saloon_page").is(":visible"))renew_chat();
				}
				renew_connection();

				$("#game_menu_page").show();
				$("#login_page").hide();

				var skill_checker=0;
				var hp_timer;

				var request_account_data = ()=>{
					$.get("php/request_account_data.php",(response)=> {
						console.log(response);
						if(response){

							$(".user_name").text(response.name);
							$("#account_status_name").text(response.status.name);
							$("#account_status_desc").html(response.status.description);
							$("#account_created").html(response.creation_date);
							response.credentials==1 ? $("#current_password").addClass("hide_content") : $("#current_password").removeClass("hide_content");
							$("#account_detail input").val("");
							$("#new_name input").val(response.name);
							$("#new_name input").data("value",response.name);
							$("#new_email input").val(response.email);
							$("#new_email input").data("value",response.email);
						}
					});
				}

				//loading user data
				request_user_data = ()=>{
					$.get("php/request_user_data.php",(response)=>{
						console.log(response);
						if(!response){
							//location.reload();
						}else{

							if(response.protocol){
								if($("#menu_sheriff").is(":visible")){
									protocol(response.protocol.id,response.protocol.sheriff_text);
								}else{
									$("#menu_sheriff").onVisible(()=>{
										protocol(response.protocol.id,response.protocol.sheriff_text);
									});
								}
							}
							if($(".user_gold").is(":visible")){
								$(".user_gold").text(response.gold);
							}else{
								$(".user_gold").onVisible(()=>{
									$(".user_gold").text(response.gold);
								});
							}
							if($(".user_fame").is(":visible")){
								$(".user_fame").text(response.fame);
							}else{
								$(".user_fame").onVisible(()=>{
									$(".user_fame").text(response.fame)
								});
							}

							$(".user_level").text(response.level);

							if(!!response.next_level){
								$("#level_now").text(response.level);
								$("#level_next").text(response.next_level);
								$("#hp_now").text(response.max_hp);
								$("#hp_next").text(response.next_level_hp);
								$("#next_skills").text(response.next_skills);
								if(response.next_skills<=0)$("#next_skills").parent().hide();
								$("#gold_to_level").text(response.gold_to_level);
								$("#menu_player").css("pointer-events","");
							}else{
								$("#menu_player").css("pointer-events","none");
							}
							//filling up home skill list
							$("#home_skills_choice .home_item").remove();
							$("#home_skill_icon").css("background-image","url(img/skills/"+response.available_skills[0].picture+")");
							$("#home_skill_name").text(response.available_skills[0].name);
							if(response.available_skills[0].cd==0)$("#home_skill_type").text("Passive");
							if(response.available_skills[0].cd>0)$("#home_skill_type").text("Useable every "+response.available_skills[0].cd+(response.available_skills[0].cd==1 ? " round" : " rounds"));
							$("#home_skill_desc").text(response.available_skills[0].description);
							for(let i=0;i<response.available_skills.length;i++){
								$("#home_skills_choice .loader").append('<div class="home_item retro_filter" style="background-image:url(img/skills/'+response.available_skills[i].picture+');" '+
									'data-skill-id="'+response.available_skills[i].id+'" '+
									'data-skill-name="'+response.available_skills[i].name+'" '+
									'data-skill-cd="'+response.available_skills[i].cd+'" '+
									'data-skill-desc="'+response.available_skills[i].description+'" '+
									'data-skill-picture="'+response.available_skills[i].picture+'"></div>');
							}
							//click on home skill
							$("#home_skills_choice .home_item").click((e)=>{
								$("#home_skills_choice .home_item.chosen").removeClass("chosen");
								$(e.target).addClass("chosen");
								$("#home_skill_preview")[0].dataset.skillId=e.target.dataset.skillId;
								$("#home_skill_icon").css("background-image","url(img/skills/"+e.target.dataset.skillPicture+")");
								$("#home_skill_name").text(e.target.dataset.skillName);
								if(e.target.dataset.skillCd==0)$("#home_skill_type").text("Passive");
								if(e.target.dataset.skillCd>0)$("#home_skill_type").text("Useable every "+e.target.dataset.skillCd+(e.target.dataset.skillCd==1 ? " round" : " rounds"));
								$("#home_skill_desc").text(e.target.dataset.skillDesc);
								$("#home_skill_use").text("Remove");
								if($(".home_skill_chosen.chosen")[0] && $("#home_skills_choice .home_item.chosen")[0]){
									let temp=0;
									for(let i=0;i<$(".home_skill_chosen").length;i++){
										if($("#home_skills_choice .home_item.chosen")[0].dataset.skillId==$(".home_skill_chosen")[i].dataset.skillId)temp=1;
									}
									if(temp>=1){
										$("#home_skill_use").text("Remove");
									}else{
										$("#home_skill_use").text("Switch");
									}
								}
							});
						}
					});
				}
//loading user gear

				var equipped_weapon=0;

				request_user_gear = ()=>{
					$.get("php/request_user_gear.php",(response)=>{
						console.log(response);
						if(!response){
							//location.reload();
						}else{
							$(".user_title").text(" "+response.title);
							if(response.max_hp>=response.next_level_hp)$("#hp_next").text(response.next_level_hp);
							$("#the_player").css("background-image","url(img/player/rear/"+response.twohand+"/"+response.skin+")");
							$("#menu_player").css("background-image","url(img/player/rear/"+response.twohand+"/"+response.skin+")");
							$(".user_hp_bar > .user_hp").css("width",response.hp/(response.max_hp/100)+"%");
							$(".user_hp_bar > span").text(response.hp+"/"+response.max_hp);
							$("#home_skin").css("background-image","url(img/player/skin/"+response.skin+"),url(img/temp.png)");
							$(".avatar").css("background-image","url(img/player/front/"+response.twohand+"/"+response.skin+")");

							equipped_weapon=response.weapon_equipped;
							$(".home_item.equipped").removeClass("equipped");
							$(".home_item[data-item-id="+equipped_weapon+"]").addClass("equipped");

//deciding weapon type and filling up slot

							if(response.twohand==0){
								let twohand = $("#home_twohand > div");
								let weapon = $("#home_weapon");
								twohand.css("background-image","unset");
								weapon.css("background-image","url(img/weapon/side/"+response.weapon_skin+
									"),url(img/weapon/side/"+response.weapon_skin+
									"),url(img/weapon/side/"+response.weapon_skin+")");
								weapon.off("click");
								twohand.off("click");
								weapon.click(()=>{
									if(!$(".home_choice").is(":visible"))$("#home_weapon_choice").show();
								});
							}
							if(response.twohand==1){
								let twohand = $("#home_twohand > div");
								let weapon = $("#home_weapon");
								weapon.css("background-image","unset");
								twohand.css("background-image","url(img/weapon/side/"+response.weapon_skin+")");
								weapon.off("click");
								twohand.off("click");
								twohand.click(()=>{
									if(!$(".home_choice").is(":visible"))$("#home_weapon_choice").show();
								});
							}
							//filling up decoration items by type
							let w_counter=0;
							let f_counter=0;
							let t_counter=0;
							for(let i=0;i<response.decoration.length;i++){
								switch(response.decoration[i].type){
									case "wall":
										let wall = $(".home_decoration.wall");
										$(wall[w_counter]).removeClass("empty");
										wall[w_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
										wall[w_counter].dataset.picture=response.decoration[i].picture;
										wall[w_counter++].dataset.itemId=response.decoration[i].id;
										break;
									case "floor":
										let floor = $(".home_decoration.floor");
										$(floor[f_counter]).removeClass("empty");
										floor[f_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
										floor[f_counter].dataset.picture=response.decoration[i].picture;
										floor[f_counter++].dataset.itemId=response.decoration[i].id;
										break;
									case "table":
										let table = $(".home_decoration.table");
										$(table[t_counter]).removeClass("empty");
										table[t_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
										table[t_counter].dataset.picture=response.decoration[i].picture;
										table[t_counter++].dataset.itemId=response.decoration[i].id;
										break;
									default:
										if(i<$(".home_decoration.wall").length){
											w_counter++;
										}
										if(i>=$(".home_decoration.wall").length&&i<$(".home_decoration.wall").length+$(".home_decoration.floor").length){
											f_counter++;
										}
										if(i>=$(".home_decoration.wall").length+$(".home_decoration.floor").length){
											t_counter++;
										}
								}
							}
//not full hp
							if(!protocol_affects_hp) {
								if (+response.hp < +response.max_hp) {
									$("#buy_hp_holder").show();
								}
								if (+response.hp >= +response.max_hp) {
									$("#buy_hp_holder").hide();
								}
							}
//filling up duel skills
							for(let i=0;i<$(".skill.cell:not(#reload_skill)").length;i++){
								let skill_cell=$(".skill.cell:not(#reload_skill)");
								if(response.user_skills[i]&&response.user_skills[i].id>0){
									skill_cell[i].dataset.skillId=response.user_skills[i].id;
									skill_cell[i].dataset.name=response.user_skills[i].name;
									skill_cell[i].dataset.desc=response.user_skills[i].description;
									skill_cell[i].style.backgroundImage='url(img/skills/'+response.user_skills[i].picture+')';
									skill_cell[i].classList.remove("empty");
									if(response.user_skills[i].passive==1)$(".skill.cell:not(#reload_skill)")[i].classList.add("inactive");
								}
							}
							//filling up used skills for home
							$(".home_skill_active").remove();
							let chosen_skill=$(".home_skill_chosen");
							for(let i=0;i<response.user_skills.length;i++){
								if(response.user_skills[i].id>0){
									$(chosen_skill[i]).removeClass("empty");
									$(chosen_skill[i]).css("background-image","url(img/skills/"+response.user_skills[i].picture+")");
									chosen_skill[i].dataset.skillId=response.user_skills[i].id;
									chosen_skill[i].dataset.skillName=response.user_skills[i].name;
									chosen_skill[i].dataset.skillCd=response.user_skills[i].cd;
									chosen_skill[i].dataset.skillDesc=response.user_skills[i].description;
									chosen_skill[i].dataset.skillPicture=response.user_skills[i].picture;
									$("#home_skills > div").append('<div class="home_skill_active retro_filter"><div></div></div>');
									let active_skill=$(".home_skill_active > div");
									$(active_skill[active_skill.length-1]).css("background-image","url(img/skills/"+response.user_skills[i].picture+")");

								}else{
									if(response.user_skills[i].level>0){
										$(chosen_skill[i]).addClass("locked");
										$(chosen_skill[i]).html(`<div>${response.user_skills[i].level}</div><div>level required</div>`);
									}else{
										$(chosen_skill[i]).removeClass("locked");
										$(chosen_skill[i]).html("");
									}
								}
							}

							$(".home_skill_chosen").off("click");
							$(".home_skill_chosen:not(.locked)").click((e)=>{
								$(".home_skill_chosen.chosen").removeClass("chosen");
								$(e.target).addClass("chosen");
								if(!$(e.target).hasClass("empty")){
									$("#home_skill_preview")[0].dataset.skillId=e.target.dataset.skillId;
									$("#home_skill_icon").css("background-image","url(img/skills/"+e.target.dataset.skillPicture+")");
									$("#home_skill_name").text(e.target.dataset.skillName);
									if(e.target.dataset.skillCd==0)$("#home_skill_type").text("Passive");
									if(e.target.dataset.skillCd>0)$("#home_skill_type").text("Useable every "+e.target.dataset.skillCd+(e.target.dataset.skillCd==1 ? " round" : " rounds"));
									$("#home_skill_desc").text(e.target.dataset.skillDesc);
									$("#home_skill_use").text("Remove");
								}
								if($(".home_skill_chosen.chosen")[0] && $("#home_skills_choice .home_item.chosen")[0]){
									let temp=0;
									for(let i=0;i<$(".home_skill_chosen").length;i++){
										if($("#home_skills_choice .home_item.chosen")[0].dataset.skillId==$(".home_skill_chosen")[i].dataset.skillId)temp=1;
									}
									if(temp>=1){
										$("#home_skill_use").text("Remove");
									}else{
										$("#home_skill_use").text("Switch");
									}

								}
							});
						}
					});
				}
//Loading user inventory and shop
				request_items = ()=>{
					$.get("php/request_items.php",(response)=>{
						console.log(response);
						if(!response){
							//location.reload();
						}else{
							if($(".user_gold").is(":visible")){
								$(".user_gold").text(response.gold);
							}else{
								$(".user_gold").onVisible(()=>$(".user_gold").text(response.gold));
							}
							$(".shop_weapon_item").remove();
							$("#home_weapon_choice .home_item").remove();
							if(response.shop_weapon.length){
								for(let i=0;i<response.shop_weapon.length;i++){
									if(response.inventory_weapon.includes(""+response.shop_weapon[i].id)){
										let temp_text = "";
										if(response.shop_weapon[i]["effect1"]) {
											temp_text = "<div>" + response.shop_weapon[i]["effect" + 1] + "</div>";
											for (let j = 2; j <= response.weapon_effect_amount; j++)
												if(response.shop_weapon[i]["effect" + j])temp_text += "<div>" + response.shop_weapon[i]["effect" + j] + "</div>";
										}
										//filling up user weapon list for home
										$("#home_weapon_choice .loader").append('<div class="home_item" style="background-image:url(img/weapon/side/'+response.shop_weapon[i].picture+');" data-item-id="'+response.shop_weapon[i].id+'">'+
											'<div class="home_item_name">'+response.shop_weapon[i].name+'</div>'+
											'<div class="home_item_desc">'+response.shop_weapon[i].description+'</div>'+
											'<div class="home_item_stats wooden_border bg_texture">'+
											'<div>Deals '+response.shop_weapon[i].min_dmg+'-'+response.shop_weapon[i].max_dmg+'</div>'+
											'<div>Has '+response.shop_weapon[i].chance_to_crit+'% chance to hit critically</div>'+
											(response.shop_weapon[i].ammo==1 ? "<div>"+response.shop_weapon[i].ammo+" Bullet</div>" : "<div>"+response.shop_weapon[i].ammo+" Bullets</div>" )+temp_text+
											'</div><div class="button home_item_equip">Equip</div></div></div>');
									}else{
										//filling up user weapon list for shop
										$("#shop_weapon").removeClass("empty");
										let temp_text=response.shop_weapon[i]["effect"+1];
										for(let j=2;j<=response.weapon_effect_amount;j++)temp_text+=";"+response.shop_weapon[i]["effect"+j];
										$("#shop_weapon_list").append('<div class="shop_weapon_item" data-item-id="'+response.shop_weapon[i].id+
											'" data-ammo="'+response.shop_weapon[i].ammo+
											'" data-crit="'+response.shop_weapon[i].chance_to_crit+
											'" data-desc="'+response.shop_weapon[i].description+
											'" data-min_dmg="'+response.shop_weapon[i].min_dmg+
											'" data-max_dmg="'+response.shop_weapon[i].max_dmg+
											'" data-name="'+response.shop_weapon[i].name+
											'" data-effect="'+temp_text+
											'" data-picture="'+response.shop_weapon[i].picture+
											'" data-price="'+response.shop_weapon[i].price+
											'"><div style="background-image:url(img/weapon/side/'+response.shop_weapon[i].picture+
											')"></div></div>');
									}
								}
								//empty weapon list at shop

								if(!$(".shop_weapon_item").length)$("#shop_weapon").addClass("empty");

								$(".home_item.equipped").removeClass("equipped");
								$(".home_item[data-item-id="+equipped_weapon+"]").addClass("equipped");

								$(".shop_weapon_item").click((e)=>{
									$(".shop_weapon_item.chosen").removeClass("chosen");
									$(e.currentTarget).addClass("chosen");
									$("#shop_weapon_picture").css("background-image","url(img/weapon/side/"+e.currentTarget.dataset.picture+")")
									$("#shop_weapon_name").text(e.currentTarget.dataset.name);
									$("#shop_weapon_desc").text(e.currentTarget.dataset.desc);
									let price=$("#shop_weapon_price");
									price.text(e.currentTarget.dataset.price);
									price[0].dataset.item=e.currentTarget.dataset.itemId;
									let stat=$("#shop_weapon_stat");
									stat.empty();
									stat.append("<div>Deals "+e.currentTarget.dataset.min_dmg+" - "+e.currentTarget.dataset.max_dmg+" damage on impact</div>");
									stat.append("<div>Has "+e.currentTarget.dataset.crit+"% chance to crit</div>");
									let eff=e.currentTarget.dataset.effect.split(";");
									for(let i=0;i<eff.length;i++){
										if(eff[i]!="null")$("#shop_weapon_stat").append("<div>"+eff[i]+"</div>");

									}
									if(e.currentTarget.dataset.ammo==1){
										$("#shop_weapon_stat").append("<div>"+e.currentTarget.dataset.ammo+" Bullet</div>");
									}else{
										$("#shop_weapon_stat").append("<div>"+e.currentTarget.dataset.ammo+" Bullets</div>");
									}
								});
								$($(".shop_weapon_item")[0]).click();
							}

//filling up misc list at shop
							$(".shop_misc_item").remove();
							for(let i=0;i<response.shop_misc.length;i++){
								$("#shop_misc.empty").removeClass("empty");
								$("#shop_misc_list").append('<div class="shop_misc_item" data-item-id="'+response.shop_misc[i].id
									+'" style="background-image:url(img/misc/'+response.shop_misc[i].picture
									+')"><div class="shop_misc_name">'+response.shop_misc[i].name
									+'</div><div class="shop_misc_picture"></div><div class="shop_misc_desc">"'+response.shop_misc[i].description
									+'"</div><div class="shop_misc_buy button"><span class="gold" class="shop_misc_price">'+response.shop_misc[i].price
									+'</span>');

							}
							//empty misc at shop
							if(!response.shop_misc.length)$("#shop_misc").addClass("empty");
							$(".shop_misc_buy").click((e)=>{
								let temp_id=$(e.target).closest(".shop_misc_item")[0].dataset.itemId;
								$.post("php/buy_item.php",{"item": temp_id},(response)=>{
									console.log(response);
									if(response==1){
										request_items();
										renew_connection();
									}else{
										alert_this(response);
									}
								});
							});
//loading shop skins
							if(!!response.shop_skin){
								$("#shop_skin.empty").removeClass("empty");
								$(".shop_skin_picture").css("background-image","url(img/player/skin/"+response.shop_skin.picture+")");
								$(".shop_skin_name").text(response.shop_skin.name);
								$(".shop_skin_desc").text(response.shop_skin.description);
								let buy=$(".shop_skin_buy");
								buy[0].dataset.itemId=response.shop_skin.id;
								$(".shop_skin_buy > span").text(response.shop_skin.price);
								buy.off("click");
								buy.click((e)=>{
									let temp_id= e.target.dataset.itemId ? e.target.dataset.itemId : e.target.parentNode.dataset.itemId;
									$.post("php/buy_item.php",{"item": temp_id},(response)=>{
										console.log(response);
										if(response==1){
											request_items();
											renew_connection();
										}else{
											alert_this(response);
										}
									});
								});
							}else{
								//empty skin at shop
								$("#shop_skin").addClass("empty");
							}


							//filling up the decoration list at home
							$("#home_decoration_choice .home_item").remove();
							if(response.inventory_misc.length){
								for(let i=0;i<response.inventory_misc.length;i++){
									let new_item = '<div class="home_item" style="background-image: url(img/misc/'+
										response.inventory_misc[i].picture+')" data-item-id="'+
										response.inventory_misc[i].item+'" data-picture="'+
										response.inventory_misc[i].picture+'" data-type="'+
										response.inventory_misc[i].type+'"><div><div class="text_title">'+
										response.inventory_misc[i].name+'</div><div>'+
										response.inventory_misc[i].description+'</div></div></div>';
									switch(response.inventory_misc[i].type){
										case "wall":
											$("#home_decoration_choice_wall.empty").removeClass("empty");
											$("#home_decoration_choice_wall .loader").append(new_item);
											break;
										case "floor":
											$("#home_decoration_choice_floor.empty").removeClass("empty");
											$("#home_decoration_choice_floor .loader").append(new_item);
											break;
										case "table":
											$("#home_decoration_choice_table.empty").removeClass("empty");
											$("#home_decoration_choice_table .loader").append(new_item);
											break;
									}
								}
								$("#home_decoration_choice .home_item").click((e)=>{
									$("#home_decoration_choice .home_item").removeClass("chosen");
									$(e.currentTarget).addClass("chosen");
									decor_type=e.currentTarget.dataset.type;
									$(".home_decoration > div").hide();
									$(".home_decoration."+decor_type+" > div").show();
									$(".home_decoration .decoration_replace").hide();
									$(".home_decoration.empty .decoration_remove").hide();
									$(".decoration_replace").show();
								});
							}
//empty decoration types at home
							if(!$("#home_decoration_choice_table .loader .home_item").length)
								$("#home_decoration_choice_table").addClass("empty");
							if(!$("#home_decoration_choice_wall .loader .home_item").length)
								$("#home_decoration_choice_wall").addClass("empty");
							if(!$("#home_decoration_choice_floor .loader .home_item").length)
								$("#home_decoration_choice_floor").addClass("empty");

//filling up skin list at home
							$("#home_skin_choice .home_item").remove();
							for(let i=0;i<response.inventory_skin.length;i++){
								$("#home_skin_choice .loader").append('<div class="home_item" style="background-image: url(img/player/skin/'+response.inventory_skin[i].picture+
									');" data-item-id="'+response.inventory_skin[i].id+'">'+
									'<div class="home_item_desc">'+response.inventory_skin[i].description+'</div>'+
									'<div class="button home_item_equip">Equip</div>'+
									'</div>');
							}

							$(".home_item_equip").click((e)=>{
								$.post("php/equip_item.php",{
										item:$(e.target).closest(".home_item")[0].dataset.itemId
									},(response)=>{
										console.log(response);
										alert_this(response);
										if(response!=="0")request_user_gear();
									}
								);
							});

							if(!!response.quests){
								$("#quest_log_block .loader .active_quest").remove();
								for(let i=0;i<response.quests.length;i++) {
									if(response.quests[i].reward_type=="gold")response.quests[i].reward='<b class="gold">'+response.quests[i].reward+"</b>";
									$("#quest_log_block .loader").append(`<div class="active_quest light_texture"><div class="quest_progress right_side_text"><span class="quest_current">${response.quests[i].value}</span>/<span class="quest_aim">${response.quests[i].target}</span></div><div class="quest_desc">${response.quests[i].desc}</div><div class="quest_reward">Reward:&nbsp<span class="quest_reward_value">${response.quests[i].reward}</span></div></div>`);
								}
							}

						}
					});
				}

				var request_achieves = ()=>{
					$.get("php/request_achievements.php",(r)=>{
						if(r.achieves.length>0) {
							$(".book_page").remove();
							let text_to_append = [];
							let i = 0;
							for (i; i < r.achieves.length; i++) {
								if (i % 12 == 0) text_to_append[text_to_append.length] = `<div class="book_page">`;
								if (i % 6 == 0) text_to_append[text_to_append.length - 1] += `<div class="book_page_part">`;
								text_to_append[text_to_append.length - 1] += `<div class="achievement" data-achievemnt-id="${r.achieves[i].id}"><div>${r.achieves[i].name}</div><div>${r.achieves[i].description}</div></div>`;
								if (i % 6 == 5) text_to_append[text_to_append.length - 1] += `</div>`;
								if (i % 12 == 11) text_to_append[text_to_append.length - 1] += `</div>`;
							}
							if (i % 6 != 0) text_to_append[text_to_append.length - 1] += `</div>`;
							if (i % 12 != 0) text_to_append[text_to_append.length - 1] += `</div>`;
							text_to_append = text_to_append.reverse();
							text_to_append = text_to_append.join("");
							$("#home_memory_book").append(text_to_append);
							$(".book_page").click((e) => {
								e.stopPropagation();
								$(e.currentTarget).toggleClass("left_side");
								$(".top_page").removeClass("top_page");
								$(".book_page.left_side:first").addClass("top_page");
							});
						}
						if(r.titles.length>0){
							$(".home_item_title").remove();
							for(let i=0;i<r.titles.length;i++){
								$("#home_title_choice .loader").append(`<div class="home_item_title home_item" data-item-id="${r.titles[i].id}"><div class="home_title_name"><span>${r.titles[i].name}</span><div class="button slim home_item_equip">Use</div></div><div class="home_title_description">${r.titles[i].description}</div></div>`);
							}
							$("#home_title_choice .home_item_equip").off("click");
							$("#home_title_choice .home_item_equip").click((e)=>{
								$.post("php/equip_item.php",{
										item:$(e.target).closest(".home_item")[0].dataset.itemId
									},(response)=>{
										console.log(response);
										alert_this(response);
										if(response!=="0")request_user_gear();
									}
								);
							});
						}
					});
				}
				request_achieves();

				var request_data = ()=>{
					request_user_data();
					request_user_gear();
					request_items();
					request_account_data();
				}
				request_data();

				$.get("php/check_current_stage.php",(response)=>{

					if(+response){
						$("#loading_screen").show();
						check_duel();
					}else{
						$("#loading_screen").hide();
					}
				});


				//display leveling block;
				$("#menu_player").click(()=>{
					$("#level_up_holder").show();
					$("#level_up_holder").click();
				});

				//online proof

				setInterval(()=>renew_connection(),1000);

				//add duel request

				$("#add_request").click((e)=>{
					if($(e.currentTarget).hasClass("disabled"))return;
					$.get("php/add_request.php",(response)=>{
						if(+response){
							if(response==1) {
								$("#find_duel_text").css("visibility", "hidden");
								$("#cancel_duel_text").show();
								check_duel();
							}else{
								alert_this(response);
							}
						}else{
							clearInterval(duel_timer);
							$("#find_duel_text").css("visibility","visible");
							$("#cancel_duel_text").hide();
						}
					});
				});

				let memory_book=$("#home_memory_book");
				memory_book.click((e)=>{
					if(memory_book.hasClass("open")){

					}else{
						e.stopPropagation();
						memory_book.toggleClass("open");
						memory_book.toggleClass("clickable");
						setTimeout(()=>{
							$("#home_book_cover").toggleClass("open");
						},500);
						window.onclick=(e)=>{
							if($(e.target).parent()[0].id!="home_memory_book") {
								e.stopPropagation();
								$(".book_page.left_side").removeClass("left_side");
								$(".book_page.top_page").removeClass("top_page");
								setTimeout(() => {
									$("#home_book_cover").toggleClass("open");
								}, 200);
								setTimeout(() => {
									memory_book.toggleClass("open");
									memory_book.toggleClass("clickable");
								}, 700);
								window.onclick=()=>{};
							}
						};
					}
				});

				$("#user_block").click(()=>{
					$("#account_detail_block").show();
					$("#account_detail_block").click();
				});

				$("#update_account_detail").click(()=>{
					let fields={};
					if($("#new_name input").val()!=$("#new_name input").data("value"))fields.name=$("#new_name input").val();
					if($("#new_email input").val()!=$("#new_email input").data("value"))fields.email=$("#new_email input").val();
					if($("#new_password input").val().length>0)fields.password=$("#new_password input").val();
					if($("#current_password input").val().length>0)fields.current_password=$("#current_password input").val();
					$.post("php/update_account.php",fields,(r)=>{
						alert_this(r);
						if(r==1){
							alert_this("Successfuly updated");
							request_account_data();
						}
					});
				});

				$("#home_title").click(()=>{
					if(!$(".home_choice").is(":visible"))$("#home_title_choice").show();
				});

				$("#home_skin").click(()=>{
					if(!$(".home_choice").is(":visible"))$("#home_skin_choice").show();
				});

				$("#home_skills").click(()=>{
					if(!$(".home_choice").is(":visible")){
						$("#home_skills_choice").show();
						setTimeout(()=>$("#home_skills_all div:first").focus(),300);
					}
				});

				$("#home_skill_use").click(()=>{
					let skill_active_chosen=$(".home_skill_chosen.chosen");
					let skill_chosen = $("#home_skills_choice .home_item.chosen");
					let all_skills=$(".home_skill_chosen");
					if(!skill_active_chosen[0] && skill_chosen[0]){

						for(let i=0;i<all_skills.length;i++){
							if(all_skills[i].dataset.skillId==$("#home_skill_preview")[0].dataset.skillId){
								for (key in all_skills[i].dataset) delete all_skills[i].dataset[key];
								$(all_skills[i]).css("background-image","unset");
								$(all_skills[i]).addClass("empty");
								$("#home_skill_use").text("Switch");
								break;
							}
						}
					}
					if(skill_active_chosen[0] && !skill_chosen[0]){
						for (key in skill_active_chosen[0].dataset) delete skill_active_chosen[0].dataset[key];
						$("#home_skills_choice .home_item.chosen").removeClass("chosen");
						$("#home_skills_choice .home_item[data-skill-id="+$("#home_skill_preview")[0].dataset.skillId+"]").addClass("chosen");
						skill_active_chosen.css("background-image","unset");
						skill_active_chosen.addClass("empty");
						$("#home_skill_use").text("Switch");
					}
					if(skill_active_chosen[0] && skill_chosen[0]){
						let temp=0;
						for(let i=0;i<all_skills.length;i++){
							if(skill_chosen[0].dataset.skillId==all_skills[i].dataset.skillId)temp=1;
						}
						if(temp==0){
							skill_active_chosen.removeClass("empty");
							let tempdata=skill_chosen.data();
							for(key in tempdata)skill_active_chosen[0].dataset[key]=tempdata[key];
							$("#home_skills_choice .home_item.chosen").removeClass("chosen");
							$("#home_skills_choice .home_item[data-skill-id="+$("#home_skill_preview")[0].dataset.skillId+"]").addClass("chosen");
							skill_active_chosen.css("background-image","url(img/skills/"+skill_active_chosen[0].dataset.skillPicture+")");
							$("#home_skill_use").text("Remove");
						}else{
							for(let i=0;i<all_skills.length;i++){
								if(all_skills[i].dataset.skillId==$("#home_skill_preview")[0].dataset.skillId){
									for (key in all_skills[i].dataset) delete all_skills[i].dataset[key];
									$("#home_skills_choice .home_item.chosen").removeClass("chosen");
									$("#home_skills_choice .home_item[data-skill-id="+$("#home_skill_preview")[0].dataset.skillId+"]").addClass("chosen");
									$(all_skills[i]).css("background-image","unset");
									$(all_skills[i]).addClass("empty");
									$("#home_skill_use").text("Switch");
									break;
								}
							}
						}
					}
				});

				$(".skill.cell").hover((e)=>{
					if(!!e.currentTarget.dataset.skillId) {
						let tooltip = $("#skill_tooltip");
						tooltip.find(".skill_name").text(e.currentTarget.dataset.name);
						tooltip.find(".skill_description").text(e.currentTarget.dataset.desc);
						tooltip.show();
						let pos = $(e.currentTarget).offset();
						if($(window).width()/2<pos.left) {
							tooltip.css("left", pos.left + e.currentTarget.clientWidth / 2 - tooltip[0].clientWidth);
						}else{
							tooltip.css("left", pos.left + e.currentTarget.clientWidth / 2);
						}
						tooltip.css("top", pos.top - tooltip[0].clientHeight);
					}
				},()=>{
					$("#skill_tooltip").hide();
				});

				$("#shop_weapon_buy").click((e)=>{
					let temp_id;
					if(!$(e.target).find("span")[0]){
						temp_id = $(e.target)[0].dataset.item;
					}else{
						temp_id = $(e.target).find("span")[0].dataset.item;
					}
					$.post("php/buy_item.php",{"item": temp_id},(response)=>{
						console.log(response);
						if(response==1){
							request_items();
							renew_connection();
						}else{
							alert_this(response);
						}
					});
				});

				var check_scroll=0;
				var renew_chat = ()=>{
					$.get("php/renew_chat.php",(response)=>{
						let temp_h=new Date().getHours();
						if(temp_h<10) temp_h="0"+temp_h;
						let temp_m=new Date().getMinutes();
						if(temp_m<10) temp_m="0"+temp_m;
						let temp_s=new Date().getSeconds();
						if(temp_s<10) temp_s="0"+temp_s;
						for(let i=0;i<response.length;i++){
							if(response[i].target==0){
								$("#chat").append(`<div class="chat_row global_row">${temp_h}:${temp_m}:${temp_s}[<span class="chat_name" data-user-id="${response[i].sender_id}">${response[i].sender}</span>]: <span class="msg_text">${response[i].msg}</span></div`);

							}else if(response[i].target=="user"){
								let msg = $("#chat .msg_text");
								$("#chat").append(`<div class="chat_row private_row">${temp_h}:${temp_m}:${temp_s}[<span class="chat_name" data-user-id="${response[i].sender_id}">${response[i].sender}</span>]: <span class="msg_text">${response[i].msg}</span></div>`);
							}else if(response[i].sender_id=="user"){
								let msg = $("#chat .msg_text");
								$("#chat").append(`<div class="chat_row private_row">${temp_h}:${temp_m}:${temp_s} to [<span class="chat_name" data-user-id="${response[i].target}">${response[i].target_name}</span>]: <span class="msg_text">${response[i].msg}</span></div>`);
							}
						}
						var chat=document.getElementById("chat");
						chat.onscroll = ()=>{
							check_scroll=1;
							if(chat.scrollTop+chat.clientHeight>=chat.scrollHeight-10)check_scroll=0;
						}
						if(!check_scroll)chat.scrollTop=chat.scrollHeight - chat.clientHeight;
					});
				}

				$("#chat").on("click contextmenu",(e)=>{
					let target = $(e.target);
					if(target.hasClass("chat_name")){
						e.stopPropagation();
						let left_shift = target.position().left+target.width()/2;
						let top_shift=0;
						let context_menu=$("#chat .context_menu");
						if(target.offset().top>$(window).height()/2) {
							top_shift = target.parent().position().top + target.position().top -context_menu.height() + $("#chat").scrollTop();
						}else{
							top_shift = target.parent().position().top - target.position().top + target.height()+ $("#chat").scrollTop();
						}

						context_menu.css({"left":left_shift,"top":top_shift});
						context_menu.data("target",target.data("userId"));
						context_menu.data("targetName",target.text());
						context_menu.show();
						$(window).off("click");
						$(window).on("click",(e)=>{
							$("#chat .context_menu").hide();
							$(window).off("click");
						});
					}
				});

				$("#chat").scroll(()=>{
					$("#chat .context_menu").hide();
					$(window).off("click");
				});

				$("#chat_set_target").click((e)=>{
					e.stopPropagation();
					let chat_target=$("#chat_target");
					let target=$("#chat .context_menu");
					chat_target.data("prevTargetName","Town");
					chat_target.data("prevTarget",0);
					chat_target.data("target",target.data("target"));
					chat_target.text(target.data("targetName"));
					$(window).off("click");
					$("#chat .context_menu").hide();
				});

				$("#chat_visit").click((e)=>{
					e.stopPropagation();
					let target=$("#chat .context_menu");
					$.post("php/visit_player.php",{
						target: target.data("target")
					},(response)=>{
						$(".page").hide();
						$("#visitor_page").show();
						$("#visit_home_skin").css("background-image","url(img/player/skin/"+response.skin+"),url(img/temp.png)");
//deciding weapon type and filling up slot
						let twohand = $("#visit_home_twohand > div");
						let weapon = $("#visit_home_weapon");
						if(response.twohand==0){
							twohand.css("background-image","unset");
							weapon.css("background-image","url(img/weapon/side/"+response.weapon_skin+
								"),url(img/weapon/side/"+response.weapon_skin+
								"),url(img/weapon/side/"+response.weapon_skin+")");
						}
						if(response.twohand==1){
							weapon.css("background-image","unset");
							twohand.css("background-image","url(img/weapon/side/"+response.weapon_skin+")");
						}
						//filling up decoration items by type
						let w_counter=0;
						let f_counter=0;
						let t_counter=0;
						for(let i=0;i<response.decoration.length;i++){
							switch(response.decoration[i].type){
								case "wall":
									let wall = $(".visit_home_decoration.wall");
									$(wall[w_counter]).removeClass("empty");
									wall[w_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
									wall[w_counter].dataset.picture=response.decoration[i].picture;
									wall[w_counter++].dataset.itemId=response.decoration[i].id;
									break;
								case "floor":
									let floor = $(".visit_home_decoration.floor");
									$(floor[f_counter]).removeClass("empty");
									floor[f_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
									floor[f_counter].dataset.picture=response.decoration[i].picture;
									floor[f_counter++].dataset.itemId=response.decoration[i].id;
									break;
								case "table":
									let table = $(".visit_home_decoration.table");
									$(table[t_counter]).removeClass("empty");
									table[t_counter].style.backgroundImage="url(img/misc/"+response.decoration[i].picture+")";
									table[t_counter].dataset.picture=response.decoration[i].picture;
									table[t_counter++].dataset.itemId=response.decoration[i].id;
									break;
								default:
									let decor_wall = $(".visit_home_decoration.wall");
									let decor_floor = $(".visit_home_decoration.floor");
									if(i<decor_wall.length){
										w_counter++;
									}
									if(i>=decor_wall.length&&i<decor_wall.length+decor_floor.length){
										f_counter++;
									}
									if(i>=decor_wall.length+decor_floor.length){
										t_counter++;
									}
							}
						}
					});
					$(window).off("click");
					$("#chat .context_menu").hide();
				});

				$("#chat_target").click(()=>{
					let target=$("#chat_target");
					if(target.data("target")==0){
						target.data("target",target.data("prevTarget"));
						target.data("prevTarget",0);
						target.text(target.data("prevTargetName"));
						target.data("prevTargetName","Town");
					}else{
						target.data("prevTarget",target.data("target"));
						target.data("target",0);
						target.data("prevTargetName",target.text());
						target.text("Town");
					}

				});


				$("#send_holder .button").on("click",()=>{$("#chat_form").submit()});

				$("#chat_form").submit((e)=>{
					e.preventDefault();
					let msg_input=$("#msg_input");
					if(msg_input.val()){
						var msg_to_send=msg_input.val();
						msg_input.val("");
						$.post("php/chat_send.php",{
							target: $("#chat_target").data("target"),
							msg: msg_to_send
						},(response)=>{
							if(!(+response)&&response!="")$("#chat").append('<div class="chat_row system_row">'+response+'</div>');
						});

					}
				});

				$("#buy_hp").click(()=>{
					$.get("php/buy_hp.php",(response)=>{
						alert_this(response);
						//if(response==1)request_user_data();
					});
				});
				$("#buy_level").click(()=>{
					$.get("php/buy_level.php",(response)=>{
						alert_this(response);
						if(response==1){
							request_user_data();
							request_user_gear();
							request_items();
						}
					});
				});

				$(".decoration_replace").click((e)=>{
					let used=$("#home_decoration_choice .home_item.chosen");
					if (used[0]){
						let target = $(e.target).closest(".home_decoration");
						target.removeClass("empty");
						target[0].dataset.itemId=used[0].dataset.itemId;
						target.css("background-image","url(img/misc/"+used[0].dataset.picture+")");
						$(".home_decoration .decoration_remove").show();
						$(".home_decoration.empty .decoration_remove").hide();
					}
				});
				$(".decoration_remove").click((e)=>{
					let target = $(e.target).closest(".home_decoration");
					target.css("background-image","");
					for (key in target[0].dataset)if(key!="type") delete target[0].dataset[key];
					target.addClass("empty");
					target.show();
					$(".home_decoration.empty .decoration_remove").hide();
				});

				$("#menu_saloon").click(()=>{
					$(".page").hide();
					$("#saloon_page").show();
				});

				$("#menu_shop").click(()=>{
					$(".page").hide();
					$("#shop_page").show();
				});

				$("#new_item_display").click(()=>$("#new_item_display").hide());

				$("#menu_home").click(()=>{
					$(".page").hide();
					$("#home_page").show();
				});

				$(".close_page").on("click",(e)=>{
					$(".page:visible .close_holder:visible").click();
					$(".page").hide();
					$("#game_menu_page").show();
					$(".chosen").removeClass("chosen");
				});

				$(".close_holder").on("click",(e)=>{
					$(e.target).parent().hide();
				});

				$(".collapser").click((e)=>{
					$(e.currentTarget).parent().toggleClass("collapse");
				});

				$("#home_decoration_choice .close_holder").on("click",()=> {
					$(".home_decoration_choice > div:not(:first-of-type)").hide();
					$(".home_decoration > div").hide();
					$(".home_decoration.empty").hide();
					let decor_text=[];
					$(".home_decoration").each((i,elem)=>{
						decor_text[i] = elem.dataset.itemId ? elem.dataset.itemId : 0;
					});
					$.post("php/set_misc.php",{
						decorations: decor_text.join(" ")
					},(response)=>{
						alert_this(response);
					});
				});

				$("#home_skills_choice .close_holder").on("click",()=>{
					let list_skills=[];
					$(".home_skill_chosen").each((i,elem)=>{
						list_skills[i]= elem.dataset.skillId ? elem.dataset.skillId : 0;
					});
					$.post("php/set_skills.php",{
						skills : list_skills.join(" ")
					},(response)=>{
						alert_this(response);
						request_user_gear();
					});

				});

				$("#menu_sheriff").click((e)=>{
					e.stopPropagation();
					if(e.target.id=="menu_sheriff")$("#quest_log_block").show();
				});

				$("#home_wall_decoration").click((e)=> {
					if (!$(".home_choice").is(":visible")) {
						if (e.target.id=="home_wall_decoration") {
							$("#home_decoration_choice  > div:not(:first-of-type)").hide();
							$("#home_decoration_choice_wall").show();
							$(".home_decoration.wall > div").show();
							$(".home_decoration.empty .decoration_remove").hide();
							$(".home_decoration .decoration_replace").hide();
						}
						$("#home_decoration_choice").show();
						$(".home_decoration.empty").show();
					}
				});

				$(".home_table_decoration").click(()=>{
					if(!$(".home_choice").is(":visible")) {
						if (!$("#home_decoration_choice > div:not(:first-of-type)").is(":visible")) $("#home_decoration_choice_table").show();
						$("#home_decoration_choice").show();
						$(".home_decoration.empty").show();
						$(".home_decoration.table > div").show();
						$(".home_decoration.empty .decoration_remove").hide();
						$(".home_decoration .decoration_replace").hide();
					}
				});

				$(".home_decoration").click((e)=>{
					decor_type=e.currentTarget.dataset.type;
					$(".home_decoration > div").hide();
					$(".home_decoration."+decor_type+" > div").show();
					$(".home_decoration .decoration_replace").hide();
					$(".home_decoration .decoration_remove").show();
					$(".home_decoration.empty .decoration_remove").hide();
					$("#home_decoration_choice div:not(."+decor_type+") .home_item").removeClass("chosen");
					$("#home_decoration_choice > div:not(.close_holder)").hide();
					$("#home_decoration_choice_"+decor_type).show();
				});

				$("#shop_misc").mousewheel((e,delta)=>{
					$("#shop_misc_list")[0].scrollLeft -= (delta * 50);
					e.preventDefault();
				});
				$("#home_weapon_choice").mousewheel((e,delta)=>{
					$("#home_weapon_choice .loader")[0].scrollLeft -= (delta * 50);
					e.preventDefault();
				});

				$("#shop_weapon_btn").click(()=>$("#shop_weapon").show());
				$("#shop_skin_btn").click(()=>$("#shop_skin").show());
				$("#shop_misc_btn").click(()=>$("#shop_misc").show());




				//load duels function
				var duel_timer;
				var game_timer;
				var animate_step = (obj,checker)=>{
					if(!checker){
						var game_time=obj.time-3;
						if(game_time>=0){
							if(game_time<10)$(".game_timer span").text("0"+game_time); else $(".game_timer span").text(game_time);
							game_time--;
						}else{
							$(".game_timer span").text("00");
						}
						clearInterval(game_timer);
						game_timer = setInterval(()=>{

							if(game_time>=0){
								if(game_time<10)$(".game_timer span").text("0"+game_time); else $(".game_timer span").text(game_time);
								game_time--;
							}else{
								$(".game_timer span").text("00");
							}
							let skill_cell=$(".skill.cell:not(#reload_skill)");
							for(let i=0;i<skill_cell.length;i++){
								if(obj.skill_cd[i]){
									skill_cell[i].innerText=obj.skill_cd[i];
								}else{
									skill_cell[i].innerText="";
								}
							}


							var attack = 0;
							var enemy_cells = document.getElementsByClassName("enemy cell");
							for (let i=1;i<enemy_cells.length;i++){
								if(document.getElementsByClassName("enemy cell chosen")[0]==enemy_cells[i])attack=i;
							}
							var def = 0;
							var player_cells = document.getElementsByClassName("player cell");
							for (let i=1;i<player_cells.length;i++){
								if(document.getElementsByClassName("player cell chosen")[0]==player_cells[i])def=i;
							}
							var skill = 0;
							var skill_cells = document.getElementsByClassName("skill cell");
							for (let i=0;i<skill_cells.length;i++){
								if(document.getElementsByClassName("skill cell chosen")[0]==skill_cells[i])skill=skill_cells[i].dataset.skillId;
							}
							$.post("php/duel_step.php",{
								attack: attack,
								def: def,
								skill: skill
							},(response)=>{

							});

							if(+game_time<0)clearInterval(game_timer);

						},1000);
					}

					if(obj.player_attack>0&&!obj.player_event.find((elem)=>{return elem=="No ammo"||elem=="reload"})){
						let enemy_cell= $(".enemy.cell");
						let player_cell=$(".player.cell");
						let player =$("#the_player");
						var p_target=$(enemy_cell[obj.player_attack]);
						$("#game_page").append('<div class="bullet_shot" style="left:'+ (+player.offset().left + +player_cell[0].offsetWidth/2) +'px;top:'+ (+player.offset().top + +player_cell[0].offsetHeight/2) +'px;"></div>');
						$(".bullet_shot:last").css({"left":(p_target.offset().left + +enemy_cell[0].offsetWidth/2),"top":(p_target.offset().top + +enemy_cell[0].offsetHeight/2)});
					}

					if(obj.enemy_attack>0&&!obj.enemy_event.find((elem)=>{return elem=="No ammo"||elem=="reload"})){
						let enemy_cell= $(".enemy.cell");
						let player_cell=$(".player.cell");
						let enemy =$("#the_enemy");
						var e_target=$(player_cell[obj.enemy_attack]);
						$("#game_page").append('<div class="bullet_shot" style="left:'+ (+enemy.offset().left + +enemy_cell[0].offsetWidth/2) +'px;top:'+ (+enemy.offset().top + +enemy_cell[0].offsetHeight/2) +'px;"></div>');
						$(".bullet_shot:last").css({"left":(e_target.offset().left + +player_cell[0].offsetWidth/2),"top":(e_target.offset().top + +player_cell[0].offsetHeight/2)});
					}

					$("#bullet_amount span").text(obj.ammo);
					let log = $(".combat_log .loader");
					let enemy =$("#the_enemy");
					let player =$("#the_player");
					let top_shift=Math.round(Math.random()*50+25);
					if(obj.enemy_event.forEach)obj.enemy_event.forEach((elem,i)=>{
						elem=elem.replace(" ","&nbsp;");
						$("#the_enemy").append('<div class="floating_text" style="top:'+(top_shift-=screen.height*0.025)+'%;left:'+Math.round(Math.random()*100)+'%">'+elem+'</div>');
					});
					top_shift=Math.round(Math.random()*50+25);
					if(obj.player_event.forEach)obj.player_event.forEach((elem,i)=>{
						$("#the_player").append('<div class="floating_text" style="top:'+(top_shift-=screen.height*0.025)+'%;left:'+Math.round(Math.random()*100)+'%">'+elem+'</div>');
					});
					$("#game_page .floating_text").animate({marginTop:-1*screen.height/10},5000,"linear",()=>$("#game_page .floating_text").remove());

					log.append("<div>["+obj.name+"]: "+(obj.enemy_event=="" ? obj.enemy_event : obj.enemy_event.join(", "))+"</div>");
					log.append("<div>["+$(".user_name").text()+"]: "+(obj.player_event=="" ? obj.player_event : obj.player_event.join(", "))+"</div>");

					let log_scroll=0;
					log=log[0];
					log.onscroll = ()=>{
						log_scroll=1;
						if(log.scrollTop+log.clientHeight>=log.scrollHeight-10)log_scroll=0;
					}
					if(!log_scroll)log.scrollTop=log.scrollHeight - log.clientHeight;
					let player_cell=$(".player.cell");
					player_cell.removeClass("place");
					$(player_cell[obj.player_position]).addClass("place");
					if(player[0].offsetLeft!=player_cell[obj.player_position].offsetLeft)player.css("left",(player_cell[obj.player_position].offsetLeft/$("#player_side").width())*100+"%");
					player.css("transform","translateY(-50%)");
					$("#the_player > .hp_bar > .hp").css("width",obj.player_hp/(obj.player_max_hp/100)+"%");
					$("#the_player > .hp_bar > span").text(obj.player_hp+"/"+obj.player_max_hp);
					if(enemy[0].offsetLeft!=$(".enemy.cell")[obj.enemy_position].offsetLeft)enemy.css("left",($(".enemy.cell")[obj.enemy_position].offsetLeft/$("#enemy_side").width())*100+"%");
					enemy.css("transform","translateY(-50%)");
					$("#the_enemy > .hp_bar > .hp").css("width",obj.enemy_hp/(obj.enemy_max_hp/100)+"%");
					$("#the_enemy > .hp_bar > span").text(obj.enemy_hp+"/"+obj.enemy_max_hp);
					setTimeout(()=>{$(".bullet_shot").remove()},600);

				};
				//check if duel accepted

				var duel_finished=0;
				var no_response_checker;

				var check_duel = ()=>{
					clearInterval(duel_timer);
					duel_timer = setInterval(()=>{
						$.get("php/check_duel.php",(response)=>{
							console.log(response);
							if(response!=0){	//duel found

								$("#find_duel_text").css("visibility","visible");
								$("#cancel_duel_text").hide();
								clearTimeout(no_response_checker);
								clearInterval(duel_timer);
								if(response.time>=0&&response.time<25){

									if( $(".page:not(#game_page)").is(":visible") ){
										$("#enemy_name").text(response.name);
										$("#the_enemy").css("background-image","url(img/player/front/"+response.skin+")");
										$(".combat_log .loader > div").remove();
										$(".page:not(#game_page)").hide();
									}

									if( !$("#game_page").is(":visible") )$("#game_page").show();
									if( $("#loading_screen").is(":visible") )$("#loading_screen").hide();
									animate_step(response);
									duel_finished=0;

									if(response.player_hp <=0 || response.enemy_hp<=0){
										//window.location.reload();
										console.log(2);
									} else {
										no_response_checker = setTimeout(() => window.location.reload(), response.time * 1000 + 6000);
										if (response.time < 0) {
											window.location.reload();
											check_duel();
											return;
										}
										setTimeout(step_manipulator, response.time * 1000 - 3000);
										setTimeout(() => {

											check_duel();
										}, response.time * 1000 + 500);

										$(".enemy.cell:not(#the_enemy)").click((e) => {
											if(+$("#bullet_amount span").text()>0) {
												if (e.currentTarget != $(".enemy.cell.chosen")[0]) {
													$(".enemy.cell").removeClass("chosen");
													$(e.currentTarget).toggleClass("chosen");
												} else {
													$(e.currentTarget).toggleClass("chosen");
												}
											}else{
												alert_this("You don't have enough ammo. Please reload.");
												$("#reload_skill").css("transform","scale(1.5)");
												setTimeout(()=>$("#reload_skill").css("transform",""),750);
											}
										});

										$(".player.cell:not(#the_player)").click((e) => {
											$(".player.cell").removeClass("chosen");
											$(e.currentTarget).addClass("chosen");
										});

										$(".skill.cell").click((e) => {
											if (!$(e.target).hasClass("empty") && !$(e.target).hasClass("inactive")) {
												if (e.target != $(".skill.cell.chosen")[0]) {
													$(".skill.cell").removeClass("chosen");
													$(e.target).toggleClass("chosen");
												} else {
													$(e.target).toggleClass("chosen");
												}
											}
										});
									}
								}else{
									check_duel();
								}
							}else{
								$.get("php/check_duel_end.php",(response)=>{
									console.log(response);
									if(response!=0&&duel_finished==0){
										clearTimeout(no_response_checker);
										duel_finished=1;
										animate_step(response,1);
										clearInterval(duel_timer);
										$("#game_page .overall_block").show();
										if((response.player_hp>0&&response.enemy_hp>0&&+response.player_hp>+response.enemy_hp)||(response.player_hp>0&&response.enemy_hp<=0)){
											$("#game_page .overall_block .result.window .title_block").text("You Won!");
										}
										if((response.player_hp>0&&response.enemy_hp>0&&+response.player_hp<+response.enemy_hp)||(response.player_hp<=0&&response.enemy_hp>0)){
											$("#game_page .overall_block .result.window .title_block").text("You lost");
										}
										if((response.player_hp>0&&response.enemy_hp>0&&+response.player_hp==+response.enemy_hp)||(response.player_hp<=0&&response.enemy_hp<=0)){
											$("#game_page .overall_block .result.window .title_block").text("Tie");
										}
										$("#result_enemy_name").text(response.name);
										window.onclick = window.onkeypress = ()=>{
											$("#game_page").hide();
											$("#game_page .overall_block").hide();
											$("#game_menu_page").show();
											let player = $("#the_player");
											let enemy = $("#the_enemy")
											player.css("transform","translateX(-50%)translateY(-50%)");
											player.css("left","50%");
											enemy.css("transform","translateX(-50%)translateY(-50%)");
											enemy.css("left","50%");
											$("#the_player > .hp_bar > .hp").css("width","100%");
											$("#the_enemy > .hp_bar > .hp").css("width","100%");
											request_user_data();
											request_items();
											window.onclick = window.onkeypress = ()=>{};
										}


									}else{
										$("#loading_screen").hide();
									}
								});
							}
						});
					},1000);
				};


				var step_manipulator = ()=>{
					$(".enemy.cell").off("click");
					$(".player.cell").off("click");
					$(".skill.cell").off("click");
					var attack = 0;
					var enemy_cells = document.getElementsByClassName("enemy cell");
					for (let i=1;i<enemy_cells.length;i++){
						if(document.getElementsByClassName("enemy cell chosen")[0]==enemy_cells[i])attack=i;
					}
					var def = 0;
					var player_cells = document.getElementsByClassName("player cell");
					for (let i=1;i<player_cells.length;i++){
						if(document.getElementsByClassName("player cell chosen")[0]==player_cells[i])def=i;
					}
					var skill = 0;
					var skill_cells = document.getElementsByClassName("skill cell");
					for (let i=0;i<skill_cells.length;i++){
						if(document.getElementsByClassName("skill cell chosen")[0]==skill_cells[i])skill=skill_cells[i].dataset.skillId;
					}
					$.post("php/duel_step.php",{
						attack: attack,
						def: def,
						skill: skill
					},(response)=>{

					});
					setTimeout(()=>{
						let tw = $("#game_tw");
						tw.hide();
						tw.css("left","-5em");
						$(".player.cell").removeClass("chosen");
						$(".enemy.cell").removeClass("chosen");
						$(".skill.cell").removeClass("chosen");
					},3000);
					let tw = $("#game_tw");
					tw.show();
					tw.css("left","100%");

				};



				//leave session

				$("#exit").click(()=>{
					$.get("php/quit_session.php", (response)=>{
						location.reload();
					});
				});


			}
		});
		e.preventDefault();
	});

	let last_protocol = 0;
	let protocol_affects_hp=false;
	async function protocol(x,sheriff_text){
		if(+last_protocol!=+x)switch(+x){
			case 0:
				if($("#game_page").is(":visible")) {
					$("#game_menu_page").show();
					$("#game_page").hide();
				}
				$("#add_request").removeClass("disabled");
				alert_this("You are offline");
				break;
			case 1:
				$("#add_request").removeClass("disabled");
				break;
			case 2:
				location.reload(true);
				break;
			case 3:
				$("#add_request").addClass("disabled");
				if($("#game_page").is(":visible")){
					$("#game_menu_page").show();
					$("#game_page").hide();
					alert_this("Sorry, but Server is down");
				}
				//console.log('server down');
				break;
			case 4:
				$("#add_request").addClass("disabled");
				console.log('server down by dev');
				break;
			case 5:
				$("#game_menu_page").show();
				$("#login_page").hide();
				$("body").addClass("highlight_inside");
				sheriff_says("Oh hey there...");
				sheriff_says("My name is James and I'm sheriff here...");
				sheriff_says("How about you? What's your name?",1);
				$("#sheriff_form").submit((e)=> {
					sheriff_says('', -1);
					sheriff_says('Let me think...', 1);
					e.preventDefault();
					grecaptcha.execute('6LeNlKMUAAAAAKCXe6coNW1ommMpCGcxsRVGR23B', {action: 'register'}).then((token)=> {
						$.post("php/register.php", {
							name: $("#sheriff_form input").val(),
							token: token
						}, (r) => {
							if (r == 1) {
								$("#sheriff_form").off('submit');
								$("#login").submit();
								sheriff_says('', -1);
								$("#sheriff_input").hide();
								$("body").removeClass("highlight_inside");
							}
							if (!+r && r != 0) {
								sheriff_says('', -1);
								sheriff_says(r, 1);
							}
						});
					});
				});
				$("#sheriff_form .button").click((e)=>{
					e.stopPropagation();
					$("#sheriff_form").submit();
				});
				break;
			case 6:
				$("body").addClass("highlight_inside");
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$(".the_button").addClass("highlight_block");
					$("body").addClass("darken");
					$("#pseudo_find_button").click((e) => {
						e.stopPropagation();
						$("body").removeClass("highlight_inside");
						$(".the_button").removeClass("highlight_block");
						sheriff_says('',-1);
						$("#find_duel_text").css("visibility","hidden");
						$("#pseudo_find_button").text("Searching");
						setTimeout(()=>{
							$(".page").hide();
							$("#game_page").show();
							$("#pseudo_find_button").hide();
							$("#find_duel_text").css("visibility","");
							$("#pseudo_find_button").text("Find a Duel");
							alert_this("Choose your enemy as your target.");
							$(".enemy.cell:nth-of-type(4)").addClass("highlight_block");
							$(".enemy.cell:nth-of-type(4)").click((e) => {
								pseudo_animate(3,3,3,2,["PartHit"],["Hit"],28,82);
								$(".enemy.cell:nth-of-type(4)").removeClass("highlight_block");
								$(".enemy.cell").removeClass("chosen");
								//$(e.currentTarget).toggleClass("chosen");
								$(".enemy.cell:not(#the_enemy)").off("click");
								$(".player.cell:not(#the_player)").off("click");
								alert_this("",1);
								alert_this("Lesson 1. Keep moving so you are harder to hit.");
								$(".player.cell:not(#the_player):nth-of-type(2n-1)").addClass("highlight_block");
								$(".player.cell:not(#the_player):nth-of-type(2n-1)").click((e) => {
									$(".player.cell").off("click");
									$(".player.cell:not(#the_player):nth-of-type(2n-1)").removeClass("highlight_block");
									$(".player.cell").removeClass("chosen");
									$(e.currentTarget).addClass("chosen");
									alert_this("",1);
									alert_this("Now try to predict where does your enemy is going.");
									$(".enemy.cell:nth-of-type(2n):not(:nth-of-type(6))").addClass("highlight_block");
									$(".enemy.cell:nth-of-type(2n):not(:nth-of-type(6))").click((e) => {
										$(".enemy.cell").off("click");
										$(".enemy.cell:nth-of-type(2n):not(:nth-of-type(6))").removeClass("highlight_block");
										alert_this("",1);
										alert_this("You can always change your decision until time runs out.");
										$(".game_timer").addClass("highlight_block")
										setTimeout(()=>$(".game_timer").removeClass("highlight_block"),3000);
										$(".game_timer span").text("10");
										game_time=9;
										let pseudo_timer=setInterval(()=>{
											if(game_time<10)$(".game_timer span").text("0"+game_time); else $(".game_timer span").text(game_time);
											if(game_time==0){
												clearInterval(pseudo_timer);
												var attack = 0;
												var enemy_cells = document.getElementsByClassName("enemy cell");
												for (let i=1;i<enemy_cells.length;i++){
													if(document.getElementsByClassName("enemy cell chosen")[0]==enemy_cells[i])attack=i;
												}
												var def = 0;
												var player_cells = document.getElementsByClassName("player cell");
												for (let i=1;i<player_cells.length;i++){
													if(document.getElementsByClassName("player cell chosen")[0]==player_cells[i])def=i;
												}
												$(".player.cell").removeClass("chosen");
												$(".enemy.cell").removeClass("chosen");
												pseudo_animate(attack,3,def,attack,["Hit"],["PartHit"],10,10);
												alert_this("",1);
												alert_this("Predicting does way more damage.");
												setTimeout(()=>{
													alert_this("",1);
													alert_this("Now your are both on critical hp.");
													alert_this("Thats where skills come in.");
													$(".enemy.cell").off("click");
													$(".player.cell").off("click");
													$(".skill.cell:nth-of-type(1)").addClass("highlight_block");
													$(".skill.cell:nth-of-type(1)").click((ev) => {
														alert_this("",1);
														alert_this("",1);
														$(".skill.cell:nth-of-type(1)").removeClass("highlight_block");
														let p_place= def==2 ? 5 : 3;
														let e_place= attack==3 ? 4 : 2;
														$(`.player.cell:nth-of-type(${p_place})`).addClass("highlight_block");
														$(`.player.cell:nth-of-type(${p_place})`).click(()=> {
															$(`.player.cell:nth-of-type(${p_place})`).removeClass("highlight_block");
															$(`.enemy.cell:nth-of-type(${e_place})`).addClass("highlight_block");
															$(`.enemy.cell:nth-of-type(${e_place})`).click((event)=>{
																event.stopPropagation();
																$(`.enemy.cell:nth-of-type(${e_place})`).removeClass("highlight_block");
																pseudo_animate(attack,def,p_place-1,2,["PartHit"],["Miss"],10,-8);
																$("#game_page .overall_block").show();
																$("#game_page .overall_block .result.window .title_block").text("You Won!");
																window.onclick = window.onkeypress = ()=>{
																	$('.player.cell').off("click");
																	$('.enemy.cell').off("click");
																	$('.skill.cell').off("click");
																	$('.cell').removeClass("chosen");
																	$("#game_page").hide();
																	$("#game_page .overall_block").hide();
																	$("#game_menu_page").show();
																	let player = $("#the_player");
																	let enemy = $("#the_enemy")
																	player.css("transform","translateX(-50%)translateY(-50%)");
																	player.css("left","50%");
																	enemy.css("transform","translateX(-50%)translateY(-50%)");
																	enemy.css("left","50%");
																	$("#the_player > .hp_bar > .hp").css("width","100%");
																	$("#the_enemy > .hp_bar > .hp").css("width","100%");
																	$.get("php/shift_tutorial.php",(r)=>{
																		if (r == 1) request_user_data();
																	});
																	window.onclick = window.onkeypress = ()=>{};
																}
															});
														});
														$(".skill.cell").removeClass("chosen");
														$(ev.currentTarget).toggleClass("chosen");
													});
												},3000);
											}
											game_time--;
										},1000);
										$(".enemy.cell").removeClass("chosen");
										$(e.currentTarget).toggleClass("chosen");

										$(".enemy.cell:nth-of-type(2n):not(:nth-of-type(6))").click((ev) => {
											$(".enemy.cell").removeClass("chosen");
											$(ev.currentTarget).toggleClass("chosen");
										});
										$(".player.cell:not(#the_player):nth-of-type(2n-1)").click((ev) => {
											$(".player.cell").removeClass("chosen");
											$(ev.currentTarget).toggleClass("chosen");
										});
									});
								});
							});
						},3000);
						$("#pseudo_find_button").off("click");
						$("#pseudo_find_button").click((e)=>e.stopPropagation());
					});
					$("#pseudo_find_button").show();
				});
				break;

			case 7:
				$("body").addClass("highlight_inside");
				$("#buy_hp").hide();
				protocol_affects_hp=true;
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				let temp_max_hp=120;
				let temp_hp=10;
				let temp_hp_time=110/1;
				$(".user_hp_bar > .user_hp").css("width", temp_hp / (temp_max_hp / 100) + "%");
				$(".user_hp_bar > span").text(temp_hp + "/" + temp_max_hp);
				if (+temp_hp < +temp_max_hp) {
					$("#hp_price").hide();
					let hp_time_sec = temp_hp_time % 60 >= 10 ? temp_hp_time % 60 : "0" + temp_hp_time % 60;
					let hp_time_min = Math.floor(temp_hp_time / 60) >= 10 ? Math.floor(temp_hp_time / 60) : "0" + Math.floor(temp_hp_time / 60);
					$("#time_till_full_hp").text(hp_time_min + ":" + hp_time_sec);
					$("#buy_hp_holder").show();
				}

				let protocol_timer=setInterval(()=>{
					let temp_reducer=$("#saloon_page").is(":visible") ?  10 : 1;
					if(temp_hp_time<0)temp_hp_time=0;
					temp_hp+=temp_reducer;
					if(temp_hp>temp_max_hp)temp_hp=temp_max_hp;
					temp_hp_time=Math.ceil((temp_max_hp-temp_hp)/temp_reducer);
					$(".user_hp_bar > .user_hp").css("width", temp_hp / (temp_max_hp / 100) + "%");
					$(".user_hp_bar > span").text(temp_hp + "/" + temp_max_hp);
					hp_time_sec = temp_hp_time % 60 >= 10 ? temp_hp_time % 60 : "0" + temp_hp_time % 60;
					hp_time_min = Math.floor(temp_hp_time / 60) >= 10 ? Math.floor(temp_hp_time / 60) : "0" + Math.floor(temp_hp_time / 60);
					$("#time_till_full_hp").text(hp_time_min + ":" + hp_time_sec);
					if(temp_hp_time<=0){
						$("body").removeClass("highlight_inside");
						clearInterval(protocol_timer);
						$("#buy_hp_holder").hide();
						protocol_affects_hp=false;
						$("#buy_hp").show();
						$.get("php/shift_tutorial.php",(r)=> {
							if (r == 1) request_user_data();
						});
						setTimeout(()=>$("#saloon_page .close_page").click(),2000);
					}
				},1000);
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$("#menu_saloon").addClass("highlight_block");
					$("body").addClass("darken");
					$("#menu_saloon").on("click",()=>{
						sheriff_says("",-1);
						$("#menu_saloon").removeClass("highlight_block");
						$("body").removeClass("darken");
						$("body").removeClass("highlight_inside");
						alert_this("You regenerate hp faster inside the saloon");
						$("#menu_saloon").off("click");
						$("#menu_saloon").click(()=>{
							$(".page").hide();
							$("#saloon_page").show();
						});
					});
				});
				break;

			case 8:
				$("#game_menu_page .close_holder").click();
				$("body").addClass("highlight_inside");
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$("body").addClass("darken");
					menu_shop=$("#menu_shop");
					menu_shop.addClass("highlight_block");
					menu_shop.on("click",()=>shop_func());
					let shop_func=()=>{
						sheriff_says("",-1);
						menu_shop.removeClass("highlight_block");
						alert_this("Let's buy you a new weapon");
						$("#shop_weapon_btn").addClass("highlight_block");
						$("#shop_weapon_btn").on("click",()=>{
							$("body").removeClass("darken");
							$("#shop_weapon_btn").removeClass("highlight_block");
							$("#shop_weapon_buy").addClass("highlight_block");

							let buy_wep_func=()=>{
								$("#shop_weapon_buy").removeClass("highlight_block");
								$("#shop_weapon .close_holder").click();
								alert_this("",1);
								alert_this("And something for your new home will be nice too");
								$("#shop_misc_btn").addClass("highlight_block");
								let misc_buy_func = ()=> {

									$(".shop_misc_buy").addClass("highlight_block no_shadow");
									$(".shop_misc_buy").on("click", () => {
										$("body").removeClass("highlight_inside");
										$("#shop_page .close_page").click();
										$.get("php/shift_tutorial.php", (r) => {
											if (r == 1) request_user_data();
										});
									});
									misc_buy_func = ()=> {};
								}
								if(!$("#shop_misc").hasClass("empty")){
									$("#shop_misc_btn").on("click", () => {
										$("#shop_misc_btn").removeClass("highlight_block");
										misc_buy_func();
									});
								}else{
									$("#shop_misc_btn").removeClass("highlight_block");
									misc_buy_func();
									$("body").removeClass("highlight_inside");
									$("#shop_page .close_page").click();
									$.get("php/shift_tutorial.php", (r) => {
										if (r == 1) request_user_data();
									});
								}

								buy_wep_func=()=>{};
							}

							if(!$("#shop_weapon").hasClass("empty")){
								$("#shop_weapon_buy").on("click",()=>buy_wep_func());
							}else{
								buy_wep_func();
							}

						});
						shop_func=()=>{};
					}
				});
				break;

			case 9:
				$("#game_menu_page .close_holder").click();
				$("body").addClass("highlight_inside");
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$("body").addClass("darken");
					$("#menu_home").addClass("highlight_block");
					$("#menu_home").on("click",()=>home_func());
					let home_func = ()=>{
						$("#menu_home").removeClass("highlight_block");
						sheriff_says("",-1);
						alert_this("This is your home");
						setTimeout(()=>{
							alert_this("",1);
							alert_this("You can set your skills here");
							$("#home_skills").addClass("highlight_block");
							$("#home_skills").css("transform","scale(1.6)");
							setTimeout(()=>$("#home_skills").css("transform",""),1000);
							setTimeout(()=>{
								$("#home_skills").click();
								setTimeout(()=>{
									$("#home_skills_choice .close_holder").click();
									alert_this("Now let's equip your new weapon");
									$("#home_skills").removeClass("highlight_block");
									$("#home_weapon_holder").css({"z-index":"910"});
									$("#home_weapon").addClass("highlight_block");
									$(".home_item:not(.equipped) .home_item_equip").addClass("highlight_block no_shadow");
									$(".home_item:not(.equipped) .home_item_equip").on("click",()=>wep_click());
									let wep_click = ()=>{
										$(".home_item:not(.equipped) .home_item_equip").removeClass("highlight_block no_shadow");
										$("#home_weapon_holder").css({"z-index":""});
										$("#home_weapon").removeClass("highlight_block");
										$("#home_weapon_choice .close_holder").click();
										alert_this("Your new home looks quite empty");
										alert_this("Let's add that decoration you bought");
										setTimeout(()=>{
											$("#home_wall_decoration").addClass("highlight_block");
											$("#home_wall_decoration").on("click",()=>wall_decor_func());
											let wall_decor_func = ()=>{
												$("body").removeClass("darken");
												$("#home_wall_decoration").removeClass("highlight_block");
												$("#home_decoration_choice > div:not(.close_holder)").hide();
												$("#home_decoration_choice > div:not(.close_holder):not(.empty):first").show()
												$("#home_decoration_choice .home_item").addClass("highlight_block no_shadow");
												$("#home_decoration_choice .home_item").on("click",()=>decor_func())
												let decor_func = ()=>{
													$("#home_decoration_choice .home_item").removeClass("highlight_block no_shadow");
													$(".decoration_replace:visible").addClass("highlight_block no_shadow");
													$(".decoration_replace:visible").on("click",()=>decor_item_func());
													let decor_item_func = ()=>{
														$(".decoration_replace").removeClass("highlight_block no_shadow");
														$("#home_decoration_choice .close_holder").click();
														alert_this("",1);
														alert_this("Looks better now");
														setTimeout(()=>{
															$("#home_page .close_page").click();
															alert_this("",1);
															$("body").removeClass("highlight_inside");
															$.get("php/shift_tutorial.php", (r) => {
																if (r == 1) request_user_data();
															});
														},3000);
														decor_item_func= ()=>{};
													}
													decor_func = ()=>{};
												}
												wall_decor_func = ()=>{};
											}
										},2000);
										wep_click = ()=>{};
									}
								},2000);
							},3000);
						},1000);
						home_func=()=>{};
					}
				});
				break;

			case 10:
				$("#game_menu_page .close_holder").click();
				$("body").addClass("highlight_inside");
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$("body").addClass("darken");
					$("#menu_player").addClass("highlight_block");
					$("#menu_player").on("click",()=>level_block_func());
					let level_block_func = ()=>{
						sheriff_says("",-1);
						$("body").removeClass("darken");
						$("#menu_player").removeClass("highlight_block");
						$("#buy_level").addClass("highlight_block");

						$("#pseudo_buy_level").show();
						$("#pseudo_buy_level").on("click",(e)=>buy_level_func(e));
						let buy_level_func=(e)=>{
							e.stopPropagation();
							$("#buy_level").removeClass("highlight_block");
							$("#level_up_holder .close_holder").click();
							$.get("php/buy_level.php",(response)=>{
								alert_this(response);
								if(response==1) {
									$.get("php/shift_tutorial.php",(r)=>{
										request_user_data();
										request_user_gear();
										request_items();
									});
									$("#pseudo_buy_level").hide();
								}else{
									sheriff_says("Looks like you don't have enough gold...");
									sheriff_says("But I guess it's your lucky day...");
									sheriff_says("I have a quest for you that will cover your little problem",2,()=>{
										$("body").addClass("darken");
										$("#menu_sheriff").addClass("highlight_block");
										$("#menu_sheriff").on("click",(e)=>sher_func(e));
										let sher_func = (e)=>{
											if(e.target.id!="menu_sheriff")$("#menu_sheriff").click();
											sheriff_says("", -1);
											$("body").removeClass("darken");
											$("#menu_sheriff").removeClass("highlight_block");
											setTimeout(() => {
												$("#quest_log_block .close_holder").click();
												$("body").addClass("darken");
												$("#add_request").addClass("highlight_block");
												$("#add_request").on("click",()=>add_func());
												let add_func = ()=>{
													$("body").removeClass("darken");
													$("#add_request").removeClass("highlight_block");
													setTimeout(()=>{
														$("body").removeClass("highlight_inside");
													},12000);
													add_func = ()=>{};
												};
											}, 4000);
											sher_func = () => {};
										}
									});
									buy_level_func=()=>{};
								}
							});
						}
						level_block_func = ()=>{};
					}
				});
				break;

			case 11:
				$("#game_menu_page .close_holder").click();
				$("body").addClass("highlight_inside");
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length-1;i++){
					sheriff_says(sheriff_text[i]);
				}
				sheriff_says(sheriff_text[sheriff_text.length-1],2,()=> {
					$("body").addClass("darken");
					$("#user_block").addClass("highlight_block");
					$("#user_block").on("click",()=>user_func());
					let user_func = ()=>{
						sheriff_says("",-1);
						$("#user_block").removeClass("highlight_block");
						$("body").removeClass("highlight_inside darken");
						$.get("php/shift_tutorial.php");
						user_func = ()=>{};
					}
				});
				break;

			case 12:
			case 13:
				sheriff_text=sheriff_text.split("$nxt$");
				for(let i=0;i<sheriff_text.length;i++){
					sheriff_says(sheriff_text[i]);
				}
				$.get("php/shift_tutorial.php", (r) => {
					if (r == 1) request_user_data();
				});
				break;

			default:
				break;
		}
		last_protocol=x;
	}

	let text_timer=null;
	let fire_now=[];

	async function sheriff_says(text,wait_answer=0,callback=()=>{console.log(2);}){
		$("#menu_sheriff").css("z-index",901);
		$("#menu_sheriff_block").show();
		if(text!='')$("#menu_sheriff_block .loader").append('<div class="sheriff_text" data-answer="'+wait_answer+'" data-fire="'+fire_now.length+'">'+text+'</div>');
		fire_now[fire_now.length]=callback;
		if(wait_answer==0){
			if(text_timer==null)text_timer=setTimeout(()=>{
				text_timer=null;
				$("#menu_sheriff_block .loader .sheriff_text:first-of-type").remove();
				last_text=$("#menu_sheriff_block .loader .sheriff_text:first-of-type");
				if(last_text.length>0){
					sheriff_says('',last_text.data("answer"));
					if(last_text.data("answer")==1)$("#sheriff_input").show();
					if(last_text.data("answer")==2)fire_now[last_text.data("fire")]();
				}else{
					fire_now=[];
					$("#menu_sheriff_block").hide();
					$("#menu_sheriff").css("z-index","");
				}
			},$("#menu_sheriff_block .loader .sheriff_text:first-of-type").text().length*100);
		}

		if(wait_answer==-1){
			clearTimeout(text_timer);
			text_timer=null;
			$("#menu_sheriff_block .loader .sheriff_text:first-of-type").remove();
			let last_text=$("#menu_sheriff_block .loader .sheriff_text:first-of-type");
			if(last_text.length>0){
				sheriff_says('',last_text.data("answer"));
				if(last_text.data("answer")==1)$("#sheriff_input").show();
				if(last_text.data("answer")==2)fire_now[last_text.data("fire")]();
			}else{
				fire_now=[];
				$("#menu_sheriff_block").hide();
				$("#menu_sheriff").css("z-index","");
			}
		}
	}

	$("#menu_sheriff_block .loader").click(()=>{
		if($("#menu_sheriff_block .loader .sheriff_text:first-of-type").data("answer")==0){
			sheriff_says('',-1);
		}
	});

}catch(error){
	console.log(error);
}

alert_text_timer=null;

async function alert_this(text,fast_forward=0){

	if(text!=''&&isNaN(+text))$("#alerter .loader").append('<div class="alert_text">'+text+'</div>');
	let empty_check=$("#alerter .loader .alert_text:first-of-type").length;
	if(empty_check>0)$("#alerter").show();
	if(alert_text_timer==null&&empty_check>0)alert_text_timer=setTimeout(()=>{
		alert_text_timer=null;
		$("#alerter .loader .alert_text:first-of-type").remove();
		last_text=$("#alerter .loader .alert_text:first-of-type");
		if(last_text.length>0){
			alert_this('');
		}else{
			$("#alerter").hide();
		}
	},$("#alerter .loader .alert_text:first-of-type").text().length*150);

	if(fast_forward==1){
		clearTimeout(alert_text_timer);
		alert_text_timer=null;
		$("#alerter .loader .alert_text:first-of-type").remove();
		let last_text=$("#alerter .loader .alert_text:first-of-type");
		if(last_text.length>0){
			alert_this('');
		}else{
			$("#alerter").hide();
		}
	}
}

function pseudo_animate(p_attack,e_attack,p_pos,e_pos,p_event,e_event,p_hp,e_hp) {
	let enemy_cell = $(".enemy.cell");
	let player_cell = $(".player.cell");
	let player = $("#the_player");
	var p_target = $(enemy_cell[p_attack]);
	$("#game_page").append('<div class="bullet_shot" style="left:' + (+player.offset().left + +player_cell[0].offsetWidth / 2) + 'px;top:' + (+player.offset().top + +player_cell[0].offsetHeight / 2) + 'px;"></div>');
	$(".bullet_shot:last").css({
		"left": (p_target.offset().left + +enemy_cell[0].offsetWidth / 2),
		"top": (p_target.offset().top + +enemy_cell[0].offsetHeight / 2)
	});

	let enemy = $("#the_enemy");
	var e_target = $(player_cell[e_attack]);
	$("#game_page").append('<div class="bullet_shot" style="left:' + (+enemy.offset().left + +enemy_cell[0].offsetWidth / 2) + 'px;top:' + (+enemy.offset().top + +enemy_cell[0].offsetHeight / 2) + 'px;"></div>');
	$(".bullet_shot:last").css({
		"left": (e_target.offset().left + +player_cell[0].offsetWidth / 2),
		"top": (e_target.offset().top + +player_cell[0].offsetHeight / 2)
	});

	let top_shift = Math.round(Math.random() * 50 + 25);
	if (e_event.forEach) e_event.forEach((elem, i) => {
		elem = elem.replace(" ", "&nbsp;");
		$("#the_enemy").append('<div class="floating_text" style="top:' + (top_shift -= screen.height * 0.025) + '%;left:' + Math.round(Math.random() * 100) + '%">' + elem + '</div>');
	});
	top_shift = Math.round(Math.random() * 50 + 25);
	if (p_event.forEach) p_event.forEach((elem, i) => {
		$("#the_player").append('<div class="floating_text" style="top:' + (top_shift -= screen.height * 0.025) + '%;left:' + Math.round(Math.random() * 100) + '%">' + elem + '</div>');
	});
	$("#game_page .floating_text").animate({marginTop: -1 * screen.height / 10}, 5000, "linear", () => $("#game_page .floating_text").remove());

	$("#the_player > .hp_bar > .hp").css("width", p_hp / (100 / 100) + "%");
	$("#the_player > .hp_bar > span").text(p_hp + "/" + 100);
	player_cell.removeClass("place");
	$(player_cell[p_pos]).addClass("place");
	if(player[0].offsetLeft!=player_cell[p_pos].offsetLeft)player.css("left",(player_cell[p_pos].offsetLeft/$("#player_side").width())*100+"%");
	player.css("transform", "translateY(-50%)");
	if (enemy[0].offsetLeft != $(".enemy.cell")[e_pos].offsetLeft) enemy.css("left", ($(".enemy.cell")[e_pos].offsetLeft / $("#enemy_side").width()) * 100 + "%");
	enemy.css("transform", "translateY(-50%)");
	$("#the_enemy > .hp_bar > .hp").css("width", e_hp / (100 / 100) + "%");
	$("#the_enemy > .hp_bar > span").text(e_hp + "/" + 100);
	$("#bullet_amount span").text(+$("#bullet_amount span").text()-1);
	setTimeout(() => {
		$(".bullet_shot").remove()
	}, 600);
}

$(".focusable").click((e)=>{
	$(".focusable.focused").removeClass("focused");
	$(e.currentTarget).addClass("focused");
});

$("#alerter .loader").click(()=>alert_this('',1));