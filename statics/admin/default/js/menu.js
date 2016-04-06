$(function(){
	$(".select_icons img").click(function(){
		top.win.fresh=-1;
		$(".select_icons img").attr("class","");
		$(this).attr("class","selected_icon");
		var cIcon=$(this).attr("src").match(/([^\/]+$)/)[1];
		$(".select_icons input[name='info[icon]']").val(cIcon);
	});
 
	$("#c").change(function(){
	  var cls=$(this).val();
	  if(cls){
	  	$.ajax({
			  url: "?"+ADMIN_INI+"&c="+ROUTE_C+"&a=init&get_action=1&cls="+cls,
			  cache: true,
			  dataType: "json",
			  success: function(arr){
			    $("#a").children().slice(1).remove();
			    $.each(arr,function(dx,vl){
			      arr[dx]="<option "+(vl['isuse']!=0 ? 'disabled="disabled" ' : '')+" value=\""+vl['a']+"\">"+vl['a']+"</option>";
			    });
			    $("#a").append(arr.join(""));
			  }
			});
	  }else{
	  	$("#a").val("");
	  }
	});
  
  $("tbody tr[id^='row']").each(function(dx,n){
  	var cid=$(this).attr("id"),
  			cds=$("tbody tr[id^='"+cid+"_']");
  	
		if(cds.length){
		  $("td:eq(2)",this).toggle(function(){
		  	cds.hide();
		  	$(this).attr("title","点击打开子菜单");
		  },function(){
		  	cds.filter(function(){
						  return ($(this).attr("id").substr(cid.length+1).indexOf("_")==-1);
						}).show();
		  	$(this).attr("title","点击关闭子菜单");
		  }).css("cursor","pointer").attr("title","点击关闭子菜单");
		}
  });
 
	 /*待添加或修改内容有改变则刷新*/
	if(ROUTE_A=='edit'||ROUTE_A=='add'){
		$("input,textarea,select").change(function(){top.win.fresh=-1;});
	}
});

function menu(act,para){
  switch(act){
    case 'add':
      top.win.diag(ROUTE_C+'.add',{'tl':'添加菜单','w':540,'h':300});
    break;
    case 'sub':
      top.win.diag(ROUTE_C+'.add','pid='+para,{'tl':'添加子菜单','w':540,'h':300});
    break;
    case 'edit':
      top.win.diag(ROUTE_C+'.edit','id='+para,{'tl':'编辑菜单','w':540,'h':300});
    break;    
  }
}
