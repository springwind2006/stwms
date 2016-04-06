$(function(){
	$(".container-tab li:first").attr("class","on");
  $(".container-cnt").hide();
  $(".container-cnt:eq(0)").show();
  $(".container-tab li").click(function(){
    var dx=$(".container-tab li").index(this);
    $(".container-tab li:not("+dx+")").attr("class","");
    $(this).attr("class","on");
    $(".container-cnt:not("+dx+")").hide();
    $(".container-cnt:eq("+dx+")").show();
		$.formValidator.reloadAutoTip();
  });

  if(ROUTE_A=='add'||ROUTE_A=='edit'){
  	var getCid=function(idstr){return idstr.substr(idstr.lastIndexOf("_"));};
  	$.formValidator.initConfig({formID:"myform",autoTip:true,mode:'AutoTip'});
    $("input[id^='type_']").each(function(){
				var ctp=$(this).val(),cid=getCid($(this).attr("id"));
				if(ctp=="0"){
				  $("#model"+cid).formValidator({onShow:"请选择模型",onFocus:"请选择模型"}).inputValidator({min:1,onError:"请选择模型"});
			    if(ROUTE_A=='edit'){$("#model"+cid).defaultPassed();}
		  	}
		    $("#name"+cid).formValidator({onShow:"请输入栏目名称"}).inputValidator({min:1,onError:"不能为空,请确认"});
		    $("#url"+cid).formValidator({onShow:"请输入外链地址"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"类型名称两边不能有空符号"},onError:"不能为空,请确认"});

		    $("#cdir"+cid).formValidator({onShow:"请输入英文目录"}).
		               inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"类型名称两边不能有空符号"},onError:"不能为空,请确认"}).
		               regexValidator({regExp:"^[\\w]+$",onError:"英文目录只允许字母、数字或下划线"}).
		             	 ajaxValidator({
										  type : "get",
											url : "?"+ADMIN_INI+"&c=category&a=init&check=1&type=0&mcid="+cid,
											dataType : "html",
											cached:false,
											getData:{cdir:'cdir'+cid,pid:'pid'+cid,id:'id'+cid},
											async:'false',
											success : function(data){
												  var rs=$.trim(data),cid="",ckRes="0",cdir="";
												  if(rs.indexOf('_')!=1){return false;}
												  cid=rs.substr(1);
												  ckRes=rs.substr(0,1);
												  cdir=$("#cdir"+cid).val();
												  if(ROUTE_A=='edit' && $("#cdir"+cid).data("default")==cdir){
												    return true;
												  }
							            if(ckRes == "1"){
							            	//开始检查为保存的页面同级目录
							            	var pid=$('#pid'+cid).val(),cdir=$("#cdir"+cid).val(),isPass=true;
							            	$("select[id^='pid']").each(function(){
							            		var eid=$(this).attr("id"),ecdir='';
							            		eid=eid.substr(eid.lastIndexOf("_"));
							            		ecdir=$("#cdir"+eid).val();
							            		if($(this).val()==pid&&cid!=eid&&ecdir==cdir){
							            			isPass=false;
							            		}
							            	});
							              return isPass;
													} else {
										        return false;
													}
											},
											buttons: $("#dosubmit"),
											onError : "同级目录存在！",
											onWait : "正在连接...请稍等"
									 });
				$("#simple_url"+cid).formValidator({onShow:"为空则不简化地址"}).
				 					 regexValidator({regExp:"(?:^\\w[\\w/]+/$)|(?:^\\w[\\w/]+\\.php$)|(?:^$)",onError:"填写目录名(以/结尾)或文件名(以.php结尾)"}).
		             	 ajaxValidator({
										  type : "get",
											url : "?"+ADMIN_INI+"&c=category&a=init&check=1&type=1&mcid="+cid,
											dataType : "html",
											cached:false,
											getData:{curl:'simple_url'+cid,pid:'pid'+cid,id:'id'+cid},
											async:'false',
											success : function(data){
												  var rs=$.trim(data),cid="",ckRes="0",cdir="";
												  if(rs.indexOf('_')!=1){return false;}
												  cid=rs.substr(1);
												  ckRes=rs.substr(0,1);
												  curl=$("#simple_url"+cid).val();
												  if(ROUTE_A=='edit' && $("#simple_url"+cid).data("default")==curl){
												    return true;
												  }
							            if(ckRes == "1"){
							            	//开始检查为保存的页面同级目录
							            	var isPass=true;
							            	$("input[id^='simple_url']").each(function(){
							            		var eid=$(this).attr("id"),ecdir='';
							            		eid=eid.substr(eid.lastIndexOf("_"));
							            		ecurl=$("#simple_url"+eid).val();
							            		if(cid!=eid&&ecurl==curl&&curl!=""){
							            			isPass=false;
							            		}
							            	});
							              return isPass;
													} else {
										        return false;
													}
											},
											buttons: $("#dosubmit"),
											onError : "同级目录存在！",
											onWait : "正在连接...请稍等"
									 });
        $("#simple_url"+cid).defaultPassed();
		  	if(ROUTE_A=='edit'){//编辑状态默认验证成功
		  		$("#name"+cid+",#url"+cid+",#cdir"+cid).defaultPassed();
	  			$("#cdir"+cid).data("default",$("#cdir"+cid).val());//设置默认值
	  			$("#simple_url"+cid).data("default",$("#simple_url"+cid).val());//设置默认值
		  	}

		  	//设置模板选择
				$("#template_style"+cid).change(function(){
						var cid=getCid($(this).attr("id")),cStyle=$(this).val();
						if(cStyle==""){
						  $("#template_category"+cid+" option:gt(0),#template_list"+cid+" option:gt(0),#template_show"+cid+" option:gt(0)").remove();
						}else{
							var cType=$("#type"+cid).val();
							$.ajax({
					  		dataType:"json",url:act_url("category","init","get_template=1&style="+cStyle+"&type="+cType+"&mcid="+cid),
					  		success: function(obj){
					  			var cid=obj["mcid"];
					  			$.each(obj,function(dx,vl){
					  				if(dx=="mcid")return true;
					  				var sels="";
					  				$.each(vl,function(i,v){
					  					vl[i]='<option value="'+v+'">'+v+'</option>';
					  				});
					  				$("#template_"+dx+cid+" option:gt(0)").remove();
					  				$("#template_"+dx+cid).append(vl.join(""));
					  			});
							  }
							});
						}
				});
		    $("#category_ishtml_0"+cid+",#category_ishtml_1"+cid+",#show_ishtml_0"+cid+",#show_ishtml_1"+cid).
		    click(function(){
				    var mid=$(this).attr('id'),cid=getCid(mid),
				    		tp=mid.substr(0,mid.indexOf("_")),
				    		isShow=(mid.substr(mid.indexOf("ishtml")+7,1));
				    $("#"+tp+"_ishtml_show"+cid).toggle(isShow==1);
				    $("#simple_url_show"+cid).toggle(!!($("#category_ishtml_0"+cid).attr("checked")||($("#show_ishtml_0"+cid).length >0 ? $("#show_ishtml_0"+cid).attr("checked"):false)));
					  $("#auto_html_show"+cid).toggle(!!($("#category_ishtml_1"+cid).attr("checked")||($("#show_ishtml_1"+cid).length >0 ? $("#show_ishtml_1"+cid).attr("checked"):false)));
				    $.formValidator.reloadAutoTip();
		  	});
    });
  }else{
//列表管理
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

	  $("#select_all").click(function(){
	  	$("input[name^='id']").attr("checked",$(this).attr("checked")?true:false);
	  });

	  $("#bat_act").click(function(){
	  	var act_type=$("#act_type").val(),
	  			selLen=$("input[name^='id']:checked").length;
	  	if(selLen< 1){
	  		art.dialog.alert("请选择栏目后操作！");
	  	  return false;
	  	}
	  	if(act_type=="del"){
				art.dialog.confirm("您确定要删除选择的"+selLen+"个栏目吗？删除后不可恢复！",function(){
					$("form[name='myform']").attr("action",act_url("category","del"));
					$("form[name='myform']").submit();
				});
				return true;
	  	}else if(act_type=="edit"&&selLen> 20){
	  		art.dialog.alert("批量编辑栏目数不能多于20个！");
	  		return false;
	  	}
			$("form[name='myform']").attr("action",act_url("category","edit"));
			$("form[name='myform']").submit();

	  });

  }
});

function category(act,cid,tp){
  switch(act){
  	case 'init':
      deal('category.init');
    break;
    case 'add':
      var url=(typeof(cid)!="undefined"&&cid!=""?"pid="+cid:"");
      url = url+(typeof(tp)!="undefined"&&tp!=""?(url!=""?"&":"")+"type="+tp:"");
      deal('category.add',url);
    break;
    case 'edit':
      deal('category.edit','id='+cid);
    break;
    case 'del':
      confirmurl('?'+ADMIN_INI+'&c=category&a=del&id='+cid);
    break;
    case 'batedit':
      deal('category.batedit');
    break;
  }
}


