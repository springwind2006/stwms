$(function(){
	if(ROUTE_A=="authority"){
		$("#dnd-example").treeTable({
			indent: 20
		});
	}else if(ROUTE_A=="plugin"){
		$("#dnd-example").treeTable({
			indent: 20
		});	
	}
  if(ROUTE_A!='init'){
		$("input,textarea,select").change(function(){top.win.fresh=-1;});
		$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
	}	
});


function roleSetup(){
  top.win.fresh=0;
}

function role(act,cid){
	switch(act){
		case 'init':
			deal('role.init');
		break;
		case 'view':
			deal('user.view',"roleid="+cid);
		break;
		case 'del':
			deal('role.del','id='+cid,1);
		break;
		case 'edit':
			top.win.diag('role.edit','id='+cid,{'tl':'修改角色','w':480,'h':220});
		break;
		case 'add':
			top.win.diag('role.add',{'tl':'添加角色','w':480,'h':220});
		break;
		case 'authority':
			top.win.diag('role.authority','id='+cid,{'tl':'权限设置','w':500,'h':500});
		break;
		case 'category':
			top.win.diag('role.category','id='+cid,{'tl':'栏目权限','w':800,'h':500});
		break;
		case 'plugin':
			top.win.diag('role.plugin','id='+cid,{'tl':'插件权限','w':500,'h':500});
		break;
		case 'menu':
			deal('rolemenu.init','roleid='+cid);
		break;		
	}
}

function checknode(obj,display) {
	if(display==1){
	  var chk = $("input[type='checkbox']");
	  var count = chk.length;
	  var num = chk.index(obj);
	  var level_top = level_bottom = chk.eq(num).attr('level');
	  
	  for (var i = num; i >= 0; i--) {
	    var le = chk.eq(i).attr('level');
	    if (eval(le) < eval(level_top)) {
	      chk.eq(i).attr("checked", true);
	      var level_top = level_top - 1;
	    }
	  }
	  
	  for (var j = num + 1; j < count; j++) {
	    var le = chk.eq(j).attr('level');
	    if (chk.eq(num).attr("checked") == true) {
	      if (eval(le) > eval(level_bottom)) chk.eq(j).attr("checked", true);
	      else if (eval(le) == eval(level_bottom)) break;
	    } else {
	      if (eval(le) > eval(level_bottom)) chk.eq(j).attr("checked", false);
	      else if (eval(le) == eval(level_bottom)) break;
	    }
	  }
  }
}

function select_all_auth(name, obj){
	  top.win.fresh=-1;
		$("input[type='checkbox'][name='priv["+name+"][]']").each(function(){
			if(!$(this).attr("disabled")){
		  	$(this).attr('checked', (obj.checked ? true : false));
			}
		});
}
function select_all_cat(obj){
	top.win.fresh=-1;
  var dx=$(obj).parent().parent().find("th").index($(obj).parent()),_this=this;  
  $(_this).data("checked",$(_this).data("checked") ? false:true);  
  $(".table-list tbody tr").each(function(){
		if(!$("input:eq("+(dx-1)+")",this).attr("disabled")){
			$("input:eq("+(dx-1)+")",this).attr("checked",$(_this).data("checked"));
		}
  });
}