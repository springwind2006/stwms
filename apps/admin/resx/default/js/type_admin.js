$(function(){
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

function typeAdmin(a,cid,doid){
	switch(a){
		case 'add':
			top.win.diag(ROUTE_C+'.add','isadmin=1&cid='+cid+'&pid='+doid,{'tl':(cid?'添加子分类':'添加一级栏目'),'w':460,'h':245});
		break;
		case 'edit':
		  top.win.diag(ROUTE_C+'.edit','isadmin=1&cid='+cid+'&id='+doid,{'tl':'编辑分类','w':460,'h':245});
		break;
		case 'del':
      art.dialog.confirm("确定要删除此分类吗？请谨慎操作！",function(){
         deal(ROUTE_C+'.del','isadmin=1&cid='+cid+'&id='+doid);
      });		
		break;
	}
}