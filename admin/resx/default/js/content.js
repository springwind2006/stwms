$(function(){
  if(ROUTE_A=='push'){
		$("input,textarea").change(function(){top.win.fresh=-1;});
		$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
	}	
});

function add_content(catid,opentype,ctl,w,h){
	var winW=$(top.document).width(),winH=$(top.document).height(),
	    w=w?w:(winW< 1200 ? 1000: 1200),h=h?h:winH,ctl="添加内容 - "+ctl;
	if(opentype==0){
	  top.doMainFrame.location=SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=add&catid="+catid+"&opentype="+opentype;
	}else if(opentype==1){
	  top.win.open(ROUTE_C+'.add','catid='+catid+"&opentype="+opentype,{'tl':ctl,'w':w,'h':h-20})
	}else{
		var para='height='+h+', width='+w+',left='+((winW-w)/2)+',top='+((top.window.screen.availHeight-h)/2)+',toolbar=no, menubar=no,scrollbars=yes, resizable=yes, location=no, status=no';
	  ctl=p.enCode(ctl);
	  window.open(SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=add&catid="+catid+"&opentype="+opentype,'',para);
	}
}

function edit_content(catids,opentype,ctl,w,h){
	var winW=$(top.document).width(),winH=$(top.document).height(),
	    w=w?w:(winW< 1200 ? 1000: 1200),h=h?h:winH,ctl="编辑内容 - "+ctl,
	    catids=catids.toString().split(/\.+/gi);
	
	if(opentype==0){
	  top.doMainFrame.location=SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=edit&catid="+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype;
	}else if(opentype==1){
	  top.win.open(ROUTE_C+'.edit','catid='+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype,{'tl':ctl,'w':w,'h':h})
	}else{
		var para='height='+h+', width='+w+',left='+((winW-w)/2)+',top='+((top.window.screen.availHeight-h)/2)+',toolbar=no, menubar=no,scrollbars=yes, resizable=yes, location=no, status=no';
	  window.open(SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=edit&catid="+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype,'',para);
	}  
}

function manage_content(catids,opentype,ctl,w,h){
	var winW=$(top.document).width(),winH=$(top.document).height(),
	    w=w?w:(winW< 1200 ? 1000: 1200),h=h?h:winH,ctl="编辑内容 - "+ctl,
	    catids=catids.toString().split(/\.+/gi);
	
	if(opentype==0){
	  top.doMainFrame.location=SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=manage&catid="+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype;
	}else if(opentype==1){
	  top.win.open(ROUTE_C+'.manage','catid='+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype,{'tl':ctl,'w':w,'h':h})
	}else{
		var para='height='+h+', width='+w+',left='+((winW-w)/2)+',top='+((top.window.screen.availHeight-h)/2)+',toolbar=no, menubar=no,scrollbars=yes, resizable=yes, location=no, status=no';
	  window.open(SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=manage&catid="+catids[0]+(catids.length >1?"&id="+catids[1]:"")+"&opentype="+opentype,'',para);
	}  
}

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

function trash(catid,cid,trash){
	var trash=typeof(trash)!="undefined"&&trash==1 ? 1 : 0;
	if(!trash){		
		if(window.confirm("您确定要删除选中的项吗？删除后可以从回收站中恢复！")){
		  window.location=act_url("content","trash","catid="+catid+"&id="+cid+"&trash="+trash);
		};
	}else{
		window.location=act_url("content","trash","catid="+catid+"&id="+cid+"&trash="+trash);
	}
}

function audit(tp){
	var cLen=$("tbody input[name='ids[]']:checked").length;
	if(!cLen){
		return art.dialog.alert('请选择要操作的信息！');  
	}
	$("#audit_type").val(tp);
	$("#myform").submit();		
}