$.get("get_types.php",(response)=>{
	response.forEach((item,i)=>{
		$("#search_effect").append('<option value="'+item.id+'">'+item.name+'</option>');
		$("#effect_list select").append('<option value="'+item.id+'">'+item.name+'</option>');
	});
});

$("#search_type").change(()=>{
	$("#search_type").val()=="effect" ? $("#offensive_block").show() : $("#offensive_block").hide();
	if($("#search_type").val()=="skill"){
		$("#passive_block").show();
		$("#effect_block").show();
	}else{
		$("#passive_block").hide();
		$("#effect_block").hide();
	}
	if($("#search_type").val()=="any"){
		$("#passive_block").show();
		$("#effect_block").show();
		$("#offensive_block").show();
	}
});

$("#searcher").click(()=>$("#search").submit());

$("#add_item").click(()=>{
	$("#printo").hide();
	$("#add_icon").show();
	$(".clicked").removeClass("clicked");
	$("#chosen_item").css("visibility","unset");
	$(".chosen_icon_set").hide();
	$("#add_subtype").hide();
	$("#cancel").show();
	$("#add").show();
	$("#edit").hide();
	$("#chosen_type").show();
	$("#chosen_percentage").parent().hide();
	$("#chosen_offensive").parent().hide();
	$("#chosen_level").parent().show();
	$("#chosen_cd").parent().show();
	$("#chosen_passive").parent().show();
	$("#effect_list").show();
	let items=$("#chosen_item *:not(option):disabled");
	items.each((i,item)=>{
		item.value="";
		if(item.tagName=="SELECT") $(item).find("option")[0].selected=true;
		item.disabled=false;
	});
});

$("#search").submit((e)=>{

	$("#chosen_item").css("visibility","hidden");
	e.preventDefault();
	let data = new FormData();
	data.append("main",$("#to_search").val());
	data.append("type",$("#search_type").val());
	data.append("min-level",$("#min-level").val());
	data.append("max-level",$("#max-level").val());
	if($("#search_offensive").val()!="any")data.append("offensive",$("#search_offensive").val());
	if($("#search_passive").val()!="any")data.append("passive",$("#search_passive").val());
	if($("#search_passive").val()!="any")data.append("passive",$("#search_passive").val());
	if($("#search_effect").val()!="0")data.append("effect",$("#search_effect").val());

	$.ajax({
		url:"find.php",
		data:data,
		processData: false,
		contentType: false,
		type:"POST",
		success: (r)=>{
			$("#found_holder").empty();
			for(let i=0;i<r.length;i++){
				let lvl_txt="";
				let icon_txt="";
				if(r[i].level>0)lvl_txt='<div class="found_level">Level: '+r[i].level+'</div>';
				if(r[i].picture)icon_txt='<div class="found_icon" style="background-image:url(../img/'+r[i].type+'s/'+r[i].picture+')"></div>';
				$("#found_holder").append('<div class="found_item"><div style="display:flex;"><div>'
					+'<div class="item_edit">Edit</div>'
					+'<div class="item_delete">Delete</div>'
					+'</div>'+icon_txt+'</div>'
					+'<div style="width:100%;"><div class="found_name">'+r[i].name+'<i>#'+r[i].id+'</i></div>'
					+lvl_txt+'<div class="found_type">'
					+r[i].type+'</div></div></div>');
				let last_item=$(".found_item")[$(".found_item").length-1];
				for(key in r[i])if(r[i][key]!=0)last_item.dataset[key]=r[i][key];
			}
		
			$(".found_item").hover((e)=>{
				$(e.currentTarget).find(".item_edit").css("width","0");
				if(e.currentTarget.dataset.type!="effect")$(e.currentTarget).find(".item_delete").css("width","0");
				$(e.currentTarget).find(".item_edit").animate({width: '100%'});
				if(e.currentTarget.dataset.type!="effect")$(e.currentTarget).find(".item_delete").animate({width: '100%'});
			},(e)=>{
				$(e.currentTarget).find(".item_edit").css("width","auto");
				if(e.currentTarget.dataset.type!="effect")$(e.currentTarget).find(".item_delete").css("width","auto");
				$(e.currentTarget).find(".item_edit").animate({width: '0'});
				if(e.currentTarget.dataset.type!="effect")$(e.currentTarget).find(".item_delete").animate({width: '0'});
			});

			$(".found_item").click((e)=>{
				$("#printo").hide();
				$("#add_icon").hide();
				$("#add_subtype").hide();
				$("#chosen_type").hide();
				$("#chosen_item").css("visibility","unset");
				$(".clicked").removeClass("clicked");
				$(e.currentTarget).addClass("clicked");
				let item=e.currentTarget.dataset;
				$("#chosen_item")[0].dataset.id=item.id;
				$("#chosen_item")[0].dataset.type=item.type;
				
				if(item.picture){
					$(".chosen_icon_set").show();
					if(item.type=="skin"){
						$("#chosen_item img")[0].src="../img/player/front/"+item.picture;
					}else{
						$("#chosen_item img")[0].src="../img/"+item.type+"s/"+item.picture;						
					}
				}else{
					$(".chosen_icon_set").hide();
				}
				$("#chosen_icon input").val(item.picture);
				$("#chosen_name input").val(item.name);
				if(item.type=="skill"){
					$("#chosen_percentage").parent().hide();
					$("#chosen_offensive").parent().hide();
					$("#chosen_level").parent().show();
					$("#chosen_chance").val(item.chance || 0);
					$("#chosen_cd").parent().show();
					$("#chosen_cd").val(item.cd || 0);
					$("#chosen_passive").parent().show();
					$("#chosen_passive")[0].checked=!!item.passive;
					$("#effect_list").show();
					for(let i=1;i<=5;i++){
						item["effect"+i] ? $($("#effect_list select")[i-1]).find("option[value="+item["effect"+i]+"]")[0].selected = true : $($("#effect_list select")[i-1]).find("option[value=0]")[0].selected = true
					}

				}else if(item.type=="effect"){
					$("#effect_list").show().hide();
					$("#chosen_cd").parent().hide();
					$("#chosen_passive").parent().hide();
					$("#chosen_offensive").parent().show();
					$("#chosen_offensive")[0].checked=!!item.offensive;
					$("#chosen_percentage").parent().show();
					$("#chosen_percentage").val(item.percentage || 0);
					$("#chosen_chance").val(item.chance || 0);
				}
				$("#chosen_desc textarea").val(item.description);
				if(item.level){
					$("#chosen_level").parent().show();
					$("#chosen_level").val(item.level);
				}else{
					$("#chosen_level").parent().hide();
				}
				$("#add").hide();
				if(e.target.className!="item_edit"){
					if(e.currentTarget.dataset.type=="skill")$("#printo").show();
					$("#edit").hide();
					$("#cancel").hide();
					let items=$("#chosen_item *:not(option):enabled");
					items.each((i,item)=>{
						item.disabled=true;
					});
				}
			});
			$(".item_edit").click(()=>{
				$("#edit").show();
				$("#cancel").show();
				let items=$("#chosen_item *:not(option):disabled");
				items.each((i,item)=>{
					item.disabled=false;
				});
			});
			$(".item_delete").click((e)=>{
				e.stopPropagation();
				let item=$(e.currentTarget).closest(".found_item")[0].dataset;
				let checker=confirm("Are you sure you want to delete "+item.name);
				if(checker){
					$.post("delete.php",{item:item.id},(r)=>{console.log(r);if(r)$("#search").submit()});
				}
			});
		}
	});
});

$("#search").submit();

$("#chosen_price input").change(()=>{
	$("#chosen_price input").val() ? $("#chosen_price span").text("") : $("#chosen_price span").text("not sellable"); 
});

$("#cancel").click(()=>{
	$("#search").submit();
});

$("#edit").click(()=>{
	let fd= new FormData();
	fd.append("id",$("#chosen_item")[0].dataset.id);
	fd.append("type",$("#chosen_item")[0].dataset.type);
	fd.append("description",$("#chosen_desc textarea").val());
	let inputs=$("#chosen_item input:not([type=checkbox]):visible");
	for(let i=0;i<inputs.length;i++){
		fd.append($(inputs[i]).attr("name"),inputs[i].value);
	}
	let chbxs=$("#chosen_item input[type=checkbox]:visible");
	for(let i=0;i<chbxs.length;i++){
		fd.append($(chbxs[i]).attr("name"),+chbxs[i].checked);
	}
	$.ajax({
		url:"edit.php",
		data:fd,
		processData: false,
		contentType: false,
		type:"POST",
			success:(r)=>{
			console.log(r);
			if(r)$("#search").submit();
		}
	});
});

$("#add").click(()=>{
	let data=new FormData();
	data.append("description",$("#chosen_desc textarea").val());
	data.append("passive",+$("#chosen_passive")[0].checked);
	if($("#add_icon input")[0].files[0])data.append("icon",$("#add_icon input")[0].files[0]);
	let inputs=$("#chosen_item input:not([type=checkbox]):not([type=file]):visible");
	for(let i=0;i<inputs.length;i++){
		data.append($(inputs[i]).attr("name"),inputs[i].value);
	}
	let effects=$("#effect_list select");
	for(let i=0;i<effects.length;i++){
		data.append($(effects[i]).attr("name"),effects[i].value);
	}

	$.ajax({
		url:"add.php",
		data:data,
		processData: false,
		contentType: false,
		type:"POST",
		success: (r)=>{
			$("#search").submit();
			console.log(r);
		}
	});

});

$(".close_page").click(()=>{
	window.location.href = "/tester";
});