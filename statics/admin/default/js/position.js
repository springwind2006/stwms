$(function(){
  if(ROUTE_A=='add'||ROUTE_A=='edit'){
	 	$.formValidator.initConfig({formID:"myform",mode:'AutoTip'});
		$("#name").formValidator({onShow:"推荐位名称",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"两边不能有空符号"},onError:"不能为空,请确认"});
    $("#maxnum").formValidator({onShow:"容纳的最大数据量",onFocus:"请输入正确的数字，0为不限数量"}).inputValidator({type:"number",onError:"请输入正确的数字，0为不限数量"});
    if(ROUTE_A=='edit'){$("#name,#maxnum").defaultPassed();}
	  /*待添加或修改内容有改变则刷新*/
	  if(ROUTE_A=='edit'||ROUTE_A=='add'){
			$("input,textarea").change(function(){top.win.fresh=-1;});
			$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
		}
		$("#model").change(function(){
			var cModel=$(this).val();
			$.ajax({
	  		dataType:"html",url:act_url("position","init","get_cats=1&model="+cModel),	  		
	  		success: function(dt){
	  			$("#catid option:gt(0)").remove();
	  			$("#catid").append(dt);
			  }
			});
		});
		
  }
});


function position(act,cid){
  switch(act){
  	case 'init':
  		deal(ROUTE_C+'.init');
  	break;
    case 'add':
      top.win.diag(ROUTE_C+'.add',{'tl':'添加推荐位','w':600,'h':270});
      if(ROUTE_C!='init'){
      	deal(ROUTE_C+'.init');
      }
    break;
    case 'edit':
      top.win.diag(ROUTE_C+'.edit','id='+cid,{'tl':'编辑推荐位','w':600,'h':270});
    break;
    case 'del':
      var cstr="确定要删除此推荐位吗？推荐位数据也将被删除！<br/>【此过程不可还原，请谨慎操作！】";
      art.dialog.confirm(cstr, function(){
          deal(ROUTE_C+'.del','id='+cid);
     	});
    break;
  }
}