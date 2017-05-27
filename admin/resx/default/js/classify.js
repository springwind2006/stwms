$(function(){
  if(ROUTE_A=='add'||ROUTE_A=='edit'){
	 	$.formValidator.initConfig({formID:"myform",mode:'AutoTip'});
		$("#name").formValidator({onShow:"类别名称",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"两边不能有空符号"},onError:"不能为空,请确认"});
    if(ROUTE_A=='edit'){$("#name").defaultPassed();}
	  /*待添加或修改内容有改变则刷新*/
	  if(ROUTE_A=='edit'||ROUTE_A=='add'){
			$("input,textarea").change(function(){top.win.fresh=-1;});
			$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
		}	
  }
});


function classify(act,cid){
  switch(act){
  	case 'init':
  		deal(ROUTE_C+'.init');
  	break;
    case 'add':
      top.win.diag(ROUTE_C+'.add',{'tl':'添加类别','w':750,'h':400});
    break;
    case 'edit':
      top.win.diag(ROUTE_C+'.edit','cid='+cid,{'tl':'编辑类别','w':750,'h':400});
    break;
    case 'manage':
      deal('type.init','isadmin=1&cid='+cid);
    break;
    case 'del':
      var cstr="确定要删除此类别吗？类别下的分类数据也将被删除！<br/>【此过程不可还原，请谨慎操作！】";
      art.dialog.confirm(cstr, function(){
          deal(ROUTE_C+'.del','cid='+cid);
     	});
    break;
  }
}