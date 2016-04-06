$(function(){
	$(".select_icons img").click(function(){
		top.win.fresh=-1;
		$(".select_icons img").attr("class","");
		$(this).attr("class","selected_icon");
		var cIcon=$(this).attr("src").match(/([^\/]+$)/)[1];
		$(".select_icons input[name='info[icon]']").val(cIcon);
	});
	
  $("tbody tr[id^='row']").each(function(dx,n){
  	var cid=$(this).attr("id"),cds=$("tbody tr[id^='"+cid+"_']");
		if(cds.length){
		  $("td:eq(3)",this).toggle(function(){
		  	cds.hide();
		  	$(this).attr("title","点击打开子菜单");
		  },function(){
		  	cds.show();
		  	$(this).attr("title","点击关闭子菜单");
		  }).css("cursor","pointer").attr("title","点击关闭子菜单");
		}
  });
  
  $("#menu_type span").click(function(){
  	var cType=$("input",this).attr("checked",true).val();
    if(cType!=2){
    	$("#url").hide();
    	$("#cadata").show();
    	$("#cadata input").attr("readonly",cType!=0);
    	if(cType!=0){
    		$("#show_menu").hide();
    		$("#select_menu").show();
    	}else{
    		$("#show_menu").show();
    		$("#select_menu").hide();    	
    	}    	
    }else{
    	$("#url").show();
    	$("#cadata").hide();     
    }
  }).css({"cursor":"pointer"});
  
  $("#usableMenus").change(function(){
    $("#show_menu input").val($("option:selected",this).val());
    $("#c").val($("option:selected",this).attr("c"));
    $("#a").val($("option:selected",this).attr("a"));
    $("#data").val($("option:selected",this).attr("data"));  
  });
  
  $("#returnModify").click(function(){
  	$("#show_menu").show();
    $("#select_menu").hide();
  });
  
  $("#move2others").click(function(){
		if($("tbody input:checked").length){
	  	$.ajax({
	  		dataType:"html",url:act_url("admin","getmenu","roleid="+p.$_GET("roleid")),	  		
	  		success: function(res){
	  			var res="<select id=\"show_pid\"><option value=\"0\">作为一级菜单</option>"+res+"</select>";
			    art.dialog.through({
			    	  lock:true,
			    	  width:300,
			    	  height:80,
					    title: '选择父目录',
					    content: "选择父目录："+res,
					    ok:function(){
					    	var ids={id:[]},
					    			pid=$("#show_pid",art.dialog.top.document).val();
    	
					    	$("tbody input:checked").each(function(){
					    		ids.id.push($(this).val());
					    	});
					    	
					    	$.ajax({
					    		dataType:"html",
						    	url:act_url("rolemenu","move","roleid="+p.$_GET("roleid")+"&pid="+pid),
					    		data:ids,
					    		success:function(r){
					    			if(r){
					    			  location.reload();
					    			}
					    		}
					    	});
					      return false;
					    },
					    cancel:true
					});
			  }
			});
		}else{
			art.dialog.alert("请先选择要移动的菜单，然后操作！");
		}
  });
  
  $("#deleteAllBt").click(function(){
  	if($("tbody input:checked").length){
	    art.dialog.confirm(
	    	  "确定要删除吗？删除后不可恢复！",
			    function(){
			    	var ids={id:[]};
	
			    	$("tbody input:checked").each(function(){
			    		ids.id.push($(this).val());
			    	});
			    	
			    	$.ajax({
			    		dataType:"html",
			    		url:act_url("rolemenu","del","roleid="+p.$_GET("roleid")),
			    		data:ids,
			    		success:function(r){
			    			if(r){
			    			  location.reload();
			    			}
			    		}
			    	});
			      return false;
			    }
			);  
		}else{
			art.dialog.alert("请先选择要删除的菜单，然后操作！");
		}
  });
  
  $("#resetmenu").click(function(){
	    art.dialog.confirm(
	    	  "确定要重新生成菜单吗？现有菜单将会被删除！",
			    function(){
			    	deal('rolemenu.resetmenu',"roleid="+p.$_GET("roleid"));
			    }
			); 
  });

  
});



function rolemenu(act,roleid,cid){
	switch(act){
		case 'role':
			deal('role.init');
		break;
		case 'del':
			art.dialog.confirm("您确定要删除吗？删除后不可恢复！", function(){
		    deal('rolemenu.del','roleid='+roleid+'&id='+cid);
		  });			
		break;
		case 'edit':
			top.win.diag('rolemenu.edit','roleid='+roleid+'&id='+cid,{'tl':'修改菜单','w':570,'h':265});
		break;
		case 'add':
			top.win.diag('rolemenu.add','roleid='+roleid,{'tl':'添加菜单','w':570,'h':265});
		break;
		case 'sub':
			top.win.diag('rolemenu.add','roleid='+roleid+'&pid='+cid,{'tl':'添加菜单','w':570,'h':265});
		break;
	}
}
