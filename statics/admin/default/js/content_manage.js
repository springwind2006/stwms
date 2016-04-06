$(function(){
  $("#start_search").click(function(){
  	var sArr=['s_begintime','s_endtime','s_position','s_kw'],issearch=false;  	
  	for(var i=0;i< sArr.length;i++){
  	  if($("#"+sArr[i])[0]&&(p.trim($("#"+sArr[i]).val())!=(sArr[i]=='s_position'?'-1':''))){
  	    issearch=true;
  	  }
  	}
    if(!issearch){
      window.location="?"+ADMIN_INI+"&c="+ROUTE_C+"&a="+ROUTE_A+"&catid="+p.$_GET("catid");
      return false;
    }
    window.location=p.aPara("?"+ADMIN_INI+"&c="+ROUTE_C+"&a="+ROUTE_A+"&catid="+p.$_GET("catid"),"search_form");
  });
  
  $("#s_begintime,#s_endtime").dblclick(function(){
   $(this).val("");
  });
  
  $("#setViewType div[id$='_off']").click(function(){
  	var cid=$(this).attr("id"),rtp=(cid=="list_off"?1:2);
  	if(location.href.match(/&view_type=\d/)){
  		window.location=location.href.replace(/&view_type=\d/,"&view_type="+rtp);
  	}else{
  		window.location=location.href+"&view_type="+rtp;
  	}
  });
  
  $("#trash_box").click(function(){
  	var ck=$("#trash_box:checked").length;
  	if(location.href.match(/&trash=\d/)){
  		window.location=location.href.replace(/&trash=\d/,"&trash="+ck);
  	}else{
  		window.location=location.href+"&trash="+ck;
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



function conform_exe(viewType){
	var sLen,act=$("select[name='a']").val();
	
	if(act!="listorder"){
		sLen = viewType!=2 ? $("tbody input:checked").length : $(".thumb input:checked").length;
		if(!sLen){
		 return art.dialog.alert('请选择要操作的信息！');   		 
		}	
	}
	if(act=="del"){
		art.dialog.confirm("您确定要删除选中的"+sLen+"项吗？删除后不可恢复！", function(){
		    $("#myform").submit();
		 });
	}else if(act=="push"){
		var vls=[];
		(viewType!=2 ? $("tbody input:checked") : $(".thumb input:checked")).each(function(){
			 vls.push($(this).val());
		});
		top.win.diag(
			ROUTE_C+'.push',
			'catid='+p.$_GET("catid")+'&ids='+vls.join(","),
			{
				'tl':'内容推送','w':650,'h':300,'ok':'推送','success':
				function(r){
					r=$.trim(r);
					if(r){
						this.iframe.contentWindow.location.reload();
					}
					return r?r:"推送失败！";
				}
			},
			0);
	}else{
		$("#myform").submit();
	}    
}

function setListOrder(field,oType){	
	var otype=(oType==0?1:(oType==1?2:0)),surl=location.href;
	if(surl.match(/&order_field=[\w]+&order_type=\d/gi)){
		window.location=surl.replace(/&order_field=[\w]+&order_type=\d/gi,"&order_field="+field+"&order_type="+otype);
	}else{
		window.location=surl+"&order_field="+field+"&order_type="+otype;		
	}
}

