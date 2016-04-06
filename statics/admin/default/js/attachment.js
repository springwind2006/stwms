$(function(){
  $("#s_begintime,#s_endtime").dblclick(function(){
   $(this).val("");
  }); 
  $("#start_search").click(function(){
  	window.location=p.aPara(act_url("attachment","init"),"search_form");
  });
  $("#conform_del").click(function(){
	  var selectedLen=$(".table-list tbody input:checked").length || $(".thumb input:checked").length;
	  if(!selectedLen){
	    top.art.dialog({content: '请选择需要删除的图片！',ok:true}).show();
	  }else{
	    top.art.dialog({
					content: '是否要删除选定的'+selectedLen+"项？删除后无法恢复",
					ok: function () {
						  $("#myform").submit();
					    return true;
					},
					cancelVal: '取消',
					cancel: true
				}).show();
	  }
  });
  $("#setViewType div[id$='_off']").click(function(){
  	var cid=$(this).attr("id"),rtp=(cid=="list_off"?1:2);
  	if(location.href.match(/&view_type=\d/)){
  		window.location=location.href.replace(/&view_type=\d/,"&view_type="+rtp);
  	}else{
  		window.location=location.href+"&view_type="+rtp;
  	}
  });
  
  $(".thumb-list .thumb").hover(function(){
  	$(this).css("border","1px solid #f6a802");
  },function(){
  	$(this).css("border","1px solid #eeeeff");
  }).click(function(){
  	$("div:eq(0)",this).toggleClass("thumb_on");
  	$("div:eq(1)",this).toggleClass("thumb_check");
  	$("input:checkbox",this).attr("checked",$("div:eq(1)",this).hasClass("thumb_check"));
  });
  
});

function isSelectAll(obj,viewType){
	if(viewType!=2){
		if(obj.tagName.toLowerCase()=="a"){
			if($(obj).html()=="全选"){
			  $(obj).html("取消");
			  $("tbody :checkbox").attr("checked",true);
			}else{
			  $(obj).html("全选");
			  $("tbody :checkbox").attr("checked",false);
			}	  
		}else{
		  $("tbody :checkbox").attr("checked",($(obj).attr("checked")?true:false));
		}	
	}else{
			if($(obj).html()=="全选"){
				$(obj).html("取消");
				$(".thumb-list .thumb").each(function(){
					$("div:eq(0)",this).addClass("thumb_on");
					$("div:eq(1)",this).addClass("thumb_check");
					$("input:checkbox",this).attr("checked",true);
				});
			}else{
			  $(obj).html("全选");
				$(".thumb-list .thumb").each(function(){
					$("div:eq(0)",this).removeClass("thumb_on");
					$("div:eq(1)",this).removeClass("thumb_check");
					$("input:checkbox",this).attr("checked",false);
				});
			}		
	}
}

function update_url(){
	art.dialog.prompt('请输入原上传URL路径：', function(data){
	   var url=$.trim(data);
	   if(url.match(/(?:^http:\/\/)|(?:\/$)/gi)){
	   		deal("attachment.update","old_url="+encodeURIComponent(url));
	   }
	});
}
