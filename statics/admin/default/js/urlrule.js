$(function(){
  if(ROUTE_A=='add'||ROUTE_A=='edit'){
	 	$.formValidator.initConfig({formID:"myform",mode:'AutoTip'});
		$("#name").formValidator({onShow:"输入规则名称",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"两边不能有空符号"},onError:"不能为空,请确认"});
    $("#example").formValidator({onShow:"输入规则示例",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"两边不能有空符号"},onError:"不能为空,请确认"});
    $("#urlrule").formValidator({onShow:"输入规则",onFocus:"至少1个长度"}).
                  inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"两边不能有空符号"},onError:"不能为空,请确认"}).
                  functionValidator({
                  	fun:function(vl,obj){
                  		var isList=$("input[name='info[type]']:checked").val()==0,matches,
                  				matchstr=(isList?"{$pdir},{$cdir},{$pid},{$cid},{$typeid},{$page},":
                  								  "{$pdir},{$cdir},{$pid},{$cid},{$year},{$month},{$day},{$id},{$page},");
                  		matches=vl.match(/(\{\$.*?\})/g);                  		
                  		if(matches==null){return false}
                  		for(var i=0;i< matches.length;i++){
                  			if(matchstr.indexOf(matches[i])==-1){
                  			  return false;
                  			}
                  		}
                  		return true;
                  	},
                  	onError:"变量输入不正确"
                  });
	  
	  if(ROUTE_A=='edit'){$("#name,#example,#urlrule").defaultPassed();}
	  /*待添加或修改内容有改变则刷新*/
	  if(ROUTE_A=='edit'||ROUTE_A=='add'){
			$("input,textarea").change(function(){top.win.fresh=-1;});
			$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
			$("input[name='info[type]']").click(function(){
				var dx=$("input[name='info[type]']").index(this);
			 	$(".urlvar div:eq("+(1-dx)+")").hide();
			 	$(".urlvar div:eq("+dx+")").show();
			});
		}
		$("#nameTip,#exampleTip,#urlruleTip").hide();
		setTimeout(function(){
			$("#nameTip,#exampleTip,#urlruleTip").show();
			$("#name,#example,#urlrule").each(function(){
			  var cL=$(this).offset(),cId=$(this).attr("id");
			  $("#"+cId+"Tip").css({"left":(cL.left+$(this).width()+10)});			  
			});		
		},100);		

  }
});


function urlrule(act,cid){
  switch(act){
    case 'add':
      top.win.diag(ROUTE_C+'.add',{'tl':'添加规则','w':910,'h':315})
    break;
    case 'edit':
      top.win.diag(ROUTE_C+'.edit','id='+cid,{'tl':'编辑规则','w':910,'h':315})
    break;
    case 'del':
    	art.dialog.confirm("确定要删除此规则吗？<br/>【此过程不可还原，请谨慎操作！】",function(){
			   deal(ROUTE_C+'.del',"id="+cid);
    	});
    break;
    default:
      deal(ROUTE_C+'.init');
    break;
  }
}