$(function(){
	$("#comePlace").load(act_url("api","ipwhere","ip="+$("#comeIP").val(),1));
});
function message(act,para){
	switch(act){
		case 'show':
      top.art.dialog.open(plugin_url("message","show",para),{
      		title:"信息查看",width:620,height:230,lock:true,opacity:0.25,ok:true      		
      });		
		break;
		case 'del':
			top.art.dialog.confirm("确定要删除吗?删除后不可恢复！",function(){
			  window.location=plugin_url("message",act,para);
			});
		break;
		case 'isdeal':
		break;
	}
}