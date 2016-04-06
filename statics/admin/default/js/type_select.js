$(function(){
	if(TYPE_EDIT_MODE){
		//点击名称编辑
		$(".table-list .c_name").live('click',function(evt){
		  var dVl=$.trim($(this).html());
		 	$(this).replaceWith('<input type="text" class="c_name_edit" defaultValue="'+dVl+'" value="'+dVl+'"/>');
		});

		//名称编辑模式鼠标移开后保存并返回
		$(".table-list .c_name_edit").live('mouseout',function(evt){
			var cVL=$.trim($(this).val()),dVl=$.trim($(this).attr('defaultValue'));
			if(cVL==""){
				cVL=dVl;
			}else	if(dVl!=cVL){
			  var cTr=$(this).parents("tr"),
			  		updateID=$("td:eq(0)",cTr).html(),
			  		listorder=$("input[type='text']:eq(0)",cTr).val(),
			  		dt={"id":updateID,"name":cVL,"listorder":listorder};
			  top.win.state("正在保存数据...");
			  editClass(dt,function(r){
			  	top.win.state(r=="success"?"保存成功！":"保存失败！");
			  });
			}
			if(cVL!=""){
				$(this).replaceWith('<span class="c_name">'+cVL+'</span>');
			}
		});

		//鼠标移动在上面显示子分类，移开隐藏子分类
		$(".table-list tbody tr").live("mouseover mouseout",function(evt){
			if(evt.type=="mouseover"){
				$(".link_add",this).show();
			}else{
			  $(".link_add",this).hide();
			}
		});

		//添加子分类事件
		$(".table-list .link_add").live("click",function(){
			var cTr=$(this).parents("tr"),pid=parseInt($("input[type='hidden']:eq(0)",cTr).val()),
					spacer=$(".c_spacer",cTr).html()+"&nbsp;&nbsp;&nbsp;&nbsp;└─ ",
					dt={"pid":pid,"name":"分类名称","listorder":getListorder(),"cid":p.$_GET('cid')};
			top.win.state("正在添加分类...");
			addClass(dt,function(r){
				if(r['id']!=-1){
					dt['id']=r['id'];
					cTr.after(getHTML(spacer,dt,r['addsub']));
				}
				top.win.fresh=1;
				top.win.state(r['id']!=-1?"添加成功！":"添加失败！");
			});
		});

		//添加顶级分类事件
		$(".add_top a").click(function(){
			var dt={"pid":0,"name":"分类名称","listorder":0,"cid":p.$_GET('cid')};
			top.win.state("正在添加分类...");
			addClass(dt,function(r){
				if(r['id']!=-1){
					dt['id']=r['id'];
					$(".table-list tbody").append(getHTML('',dt,r['addsub']));
				}
				top.win.fresh=1;
				top.win.state(r['id']!=-1?"添加成功！":"添加失败！");
			});
		});
	}

	if(!TYPE_MANAGE_MODE){
		//鼠标点击选择事件
		$(".table-list tbody tr").live("click",function(evt){
			var sid=$("td:eq(0)",this).html();
			$(".table-list tbody td").css("background","transparent");
			$("td",this).css("background","#c0c9d0");
			art.dialog.data("selectCid",sid);
		}).css("cursor","pointer");
	}

});

//添加分类
function addClass(obj,fn){
	$.ajax({
	   type: "POST",
	   dataType:"json",
	   url: act_url(ROUTE_C,"add","cid="+p.$_GET('cid')),
	   data: {"info":obj},
	   success:fn
	});
}
//编辑分类
function editClass(obj,fn){
	$.ajax({
	   type: "POST",
	   url: act_url(ROUTE_C,"edit","cid="+p.$_GET('cid')),
	   data: {"info":obj},
	   success:fn
	});
}
//删除分类
function delClass(delid,fn){
	$.ajax({
	   type: "GET",
	   url: act_url(ROUTE_C,"del","id="+delid+"&cid="+p.$_GET('cid')),
	   success:fn
	});
}

function doAct(act,obj){
	var cTr=$(obj).parents("tr"),
			cid=$("td:eq(0)",cTr).text();
  switch(act){
    case 'desc':
    	if(TYPE_EDIT_MODE){
	      art.dialog.load(act_url(ROUTE_C,"desc","id="+cid+"&cid="+p.$_GET('cid')),{
	      	fixed:true,lock:true,opacity:0.01,padding:'1px 2px',width:270,height:120,
	      	ok:function (){
	      		var cnt=$.trim($("#describe_area",top.document).val());
	      		top.win.state("正在编辑描述...");
	    		  editClass({"id":cid,"describe":cnt},function(r){
	    		  	top.win.state(r=="success"?"描述保存成功！":"描述保存失败！");
	    		  });
		        return true;
			    },
			    cancel: true
	      },false);
    	}else{
	      art.dialog.load(act_url(ROUTE_C,"desc","id="+cid+"&cid="+p.$_GET('cid')+"&isedit="+TYPE_EDIT_MODE),{
	      	fixed:true,lock:true,opacity:0.01,padding:'1px 2px',width:270,height:120,
			    okVal: '关闭',
			    ok:true
	      },false);
    	}
    break;
    case 'del':
      top.win.state("正在删除分类...");
      delClass(cid,function(r){
      	if(r=="success"){
      		cTr.remove();
      	}
      	top.win.fresh=1;
      	var info={"success":"删除成功！",'failed':"删除失败！",'haschild':"存在子分类，删除失败！"};
      	top.win.state(info[r]);
      });
    break;
  }
}

function getHTML(spacer,dt,addsub){
	return '<tr>'+
		'<td align="center">'+dt['id']+'</td>'+
		'<td>'+
			'<input type="hidden" value="'+dt['id']+'"/><input type="hidden" value="'+dt['pid']+'"/>'+
			'<span class="c_spacer">'+spacer+'</span>'+
			'<input type="text" size="3" name="listorders['+dt['id']+']"  value="'+dt['listorder']+'" class="input-text-c"/>&nbsp;&nbsp;'+
			'<input type="text" class="c_name_edit" defaultValue="'+dt['name']+'" value="'+dt['name']+'"/>&nbsp;&nbsp;'+
			(addsub ? '<a href="javascript:"  class="link_add">'+
				'<img src="'+STATIC_URL+'common/images/link_add.gif" align="absmiddle"/>添加子分类'+
			'</a>' : '')+
		'</td>'+
		'<td align="center">'+
			'<a href="javascript:" onclick="doAct(\'desc\',this)">描述</a> | '+
			'<a href="javascript:" onclick="doAct(\'del\',this)">删除</a>'+
		'</td>'+
	'</tr>';
}

function getListorder(){
	var reID=0;
  $(".table-list tbody tr").each(function(){
  	var cid=parseInt($("input[type='text']:eq(0)",this).val());
  	if(cid > reID){
  		reID=cid;
  	}
  });
  return reID+1;
}