$.get("get_types.php",(response)=>{
	response.forEach((item,i)=>{
		$("#search_type").append('<option value="'+item.type+'">'+item.type+'</option>');
		$("#chosen_type select").append('<option value="'+item.type+'">'+item.type+'</option>');
	});
});

$("#search_type").change(()=>{
	$("#search_type").val()=="weapon" ? $("#twohand_block").show() : $("#twohand_block").hide();
});

$("#searcher").click(()=>$("#search").submit());

$("#add_item").click(()=>{
	$("#printo").hide();
	$(".clicked").removeClass("clicked");
	$("#chosen_item").css("visibility","unset");
	$(".chosen_icon_set").hide();
	$("#add_subtype").hide();
	$("#cancel").show();
	$("#add").show();
	$("#edit").hide();
	$("#chosen_type").show();
	$("#chosen_type select").val()=="weapon" ? $("#chosen_stats").show() : $("#chosen_stats").hide();
	$("#chosen_type select").val()=="title" ? $("#add_icon").hide() : $("#add_icon").show();
	$("#chosen_type select").val()=="misc" ? $("#add_subtype").show() : $("#add_subtype").hide();
	let items=$("#chosen_item *:not(option):disabled");
	items.each((i,item)=>{
		item.value="";
		if(item.tagName=="SELECT") $(item).find("option")[0].selected=true;
		item.disabled=false;
	});
});

$("#chosen_type select").change(()=>{
	$("#chosen_type select").val()=="weapon" ? $("#chosen_stats").show() : $("#chosen_stats").hide();
	$("#chosen_type select").val()=="title" ? $("#add_icon").hide() : $("#add_icon").show();
	$("#chosen_type select").val()=="misc" ? $("#add_subtype").show() : $("#add_subtype").hide();
});

$("#search").submit((e)=>{
	$("#chosen_item").css("visibility","hidden");
	e.preventDefault();
	let data = new FormData();
	data.append("main",$("#to_search").val());
	data.append("type",$("#search_type").val());
	if($("#search_type").val()=="weapon")data.append("twohand",$("#twohand").val());
	data.append("min-level",$("#min-level").val());
	data.append("max-level",$("#max-level").val());
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
				if(r[i].picture)icon_txt='<div class="found_icon" style="background-image:url(../img/'+r[i].type+'/'+r[i].picture+')"></div>';
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
				$(e.currentTarget).find(".item_delete").css("width","0");
				$(e.currentTarget).find(".item_edit").animate({width: '100%'});
				$(e.currentTarget).find(".item_delete").animate({width: '100%'});
			},(e)=>{
				$(e.currentTarget).find(".item_edit").css("width","auto");
				$(e.currentTarget).find(".item_delete").css("width","auto");
				$(e.currentTarget).find(".item_edit").animate({width: '0'});
				$(e.currentTarget).find(".item_delete").animate({width: '0'});
			});

			$(".found_item").click((e)=>{
				$("#add_icon").hide();
				$("#printo").hide();
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
						$("#chosen_item img")[0].src="../img/"+item.type+"/"+item.picture;						
					}
				}else{
					$(".chosen_icon_set").hide();
				}
				$("#chosen_icon input").val(item.picture);
				$("#chosen_name input").val(item.name);
				if(item.type=="weapon"){
					$("#chosen_stats").show();
					$("#chosen_dmg_min").val(item.min_dmg);
					$("#chosen_dmg_max").val(item.max_dmg);
					$("#chosen_crit").val(item.chance_to_crit);
					$("#chosen_twohand")[0].checked = !!item.twohand;
				}else{
					$("#chosen_stats").hide();
				}
				$("#chosen_desc textarea").val(item.description);
				$("#chosen_price input").val("");
				$("#chosen_price span").text("");
				!!+item.price ? $("#chosen_price input").val(item.price) : $("#chosen_price span").text("not sellable"); 
				if(item.level){
					$("#chosen_level").show();
					$("#chosen_level input").val(item.level);
				}else{
					$("#chosen_level").hide();
				}
				$("#add").hide();
				if(e.target.className!="item_edit"){
					$("#printo").show();
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
					$.post("delete.php",{item:item.id},(r)=>{if(r)$("#search").submit()});
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
	if($("#chosen_item")[0].dataset.type=="weapon")fd.append("twohand",+$("#chosen_twohand")[0].checked);
	let inputs=$("#chosen_item input:not([type=checkbox]):visible");
	for(let i=0;i<inputs.length;i++){
		fd.append($(inputs[i]).attr("name"),inputs[i].value);
	}

	$.ajax({
		url:"edit.php",
		data:fd,
		processData: false,
		contentType: false,
		type:"POST",
			success:(r)=>{
			if(r)$("#search").submit();
		}
	});
});

$("#add").click(()=>{
	let data=new FormData();
	data.append("type",$("#chosen_item select").val());
	data.append("description",$("#chosen_desc textarea").val());
	if($("#chosen_item")[0].dataset.type!="title")data.append("icon",$("#add_icon input")[0].files[0]);
	if($("#chosen_item")[0].dataset.type=="weapon")data.append("twohand",+$("#chosen_twohand")[0].checked);
	console.log(+$("#chosen_twohand")[0].checked);
	if($("#chosen_item")[0].dataset.type=="misc")data.append("subtype",+$("#chosen_subtype").val());
	let inputs=$("#chosen_item input:not([type=checkbox]):not([type=file]):visible");
	for(let i=0;i<inputs.length;i++){
		data.append($(inputs[i]).attr("name"),inputs[i].value);
	}
	$.ajax({
		url:"add.php",
		data:data,
		processData: false,
		contentType: false,
		type:"POST",
		success: (r)=>{
			if(r)$("#search").submit();
		}
	});

});

$(".close_page").click(()=>{
	window.location.href = "/tester";
});