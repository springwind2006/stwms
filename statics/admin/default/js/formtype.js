$(function(){
  $(".container-tab li:first").attr("class","on");
  $(".container-cnt").hide();
  $(".container-cnt:first").show();
  $(".container-tab li").click(function(){
    var dx=$(".container-tab li").index(this);
    $(".container-tab li:not("+dx+")").attr("class","");
    $(this).attr("class","on");
    $(".container-cnt:not("+dx+")").hide();
    $(".container-cnt:eq("+dx+")").show();
    if(dx!=0){
      $("#typeTip,#nameTip").hide();
    }else{
      $("#typeTip,#nameTip").show();
    }
  });

  if(ROUTE_A=='add'||ROUTE_A=='edit'){
	 	$.formValidator.initConfig({formID:"myform",autoTip:true,mode:'AutoTip'});
	 	if(ROUTE_A=='add' || !$("#type").attr("readonly")){
			$("#type").formValidator({onShow:"请输入类型，添加成功后不可修改类型名称",onFocus:"用户名至少1个字符,最多15个字符"})
			        .regexValidator({regExp:"^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){0,14}$",onError:"字段名称不正确"})
			        .ajaxValidator({
			        	type : "get",
			        	cached:false,
								dataType : "html",
								async : true,
								url : act_url("formtype","init","check=1"),
								success : function(data){
			            if($("#type").data("default")===$("#type").val()||data=='1')return true;
			            if(data=='0')return false;
									return false;
								},
								buttons: $("#dosubmit"),
								error: function(jqXHR, textStatus, errorThrown){alert("服务器没有返回数据，可能服务器忙，请重试"+errorThrown);},
								onError : "该类型已经该类型，请更换类型",
								onWait : "正在对类型进行合法性校验，请稍候..."
							}).data("default",$("#type").val());
			if(ROUTE_A=='edit'){$("#type").defaultPassed();}
		}
		$("#name").formValidator({onShow:"类型名称",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"类型名称两边不能有空符号"},onError:"类型名称不能为空,请确认"});
    if(ROUTE_A=='edit'){$("#name").defaultPassed();}
    /*类型设置*/
    $("#field_type").change(function(){setTypeInfo($(this).val());});
	  setTypeInfo($("#field_type").val());  
  }

	  /*待添加或修改内容有改变则刷新*/
  if(ROUTE_A=='edit'||ROUTE_A=='code'){
		$("input,select").change(function(){top.win.fresh=-1;});
		$("textarea").change(function(){$(this).data("ischeck",1);top.win.fresh=-1;});
		$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
	}else{
		$("textarea").change(function(){$(this).data("ischeck",1);});
	}
	
	$("#dosubmit").click(function(){
		if(ROUTE_A=="add"||(ROUTE_A!="add"&&top.win.fresh==-1)){
			var isPass=false,cTestId="",
					codeObj={add_form:"添加模板",edit_form:"编辑模板",form:"表单代码",input:"输入代码",output:"输出代码",update:"更新代码"};
			$("#resultState").html("正在对代码进行运行测试...请稍候");
			$("#add_form,#edit_form,#form,#input,#output,#update").each(function(){
				cTestId=$(this).attr("id");
				var ischeck=$(this).data("ischeck")==1,
						testCode=$.trim($(this).val()),
						isphp=(cTestId=="add_form"||cTestId=="edit_form"?0:1);				
				if(!ischeck||testCode===""){return isPass=true;}				
				if(ROUTE_A=="code"){
					testCode="class TestClass"+(new Date()).getTime()+"{"+testCode+"}";
				}
				$.ajax({
				   type:"POST",
				   url:act_url("api","ctest","isphp="+isphp),
				   cache:false,async:false,
				   data:{code:testCode},
				   success: function(r){
				   	 isPass=($.trim(r)==="1");
				   }
				});
				$(this).data("ischeck",isPass?0:1);
				return isPass;
			});
			if(!isPass){
				$("#resultState").html("“"+codeObj[cTestId]+"”未通过运行检测，请认真检查代码语法！");
				return top.win.fresh=1;
			}else{
				$("#resultState").html("检查完毕，正在保存...");
				(ROUTE_A=="add" ? $("#myform").submit():POST("myform","dosubmit","resultState",function(r){
					var res=$.trim(r);
					if(res==1){
						var restType=$("#type").val();
						$("#myform").attr("action",act_url("formtype","edit","type="+restType));
						$("#type").data("default",restType);
					}
					return res==1 ? '修改操作成功！' : '修改操作失败！';
				}));
			}
		}  	
  });  

});

function setTypeInfo(k){
  var attrs=fdObj[k],html="",onchangestr="";
  if(typeof(attrs['maxlen'])!='undefined'){
    html+='最大长度：<input type="text" class="txt" name="setting[field_maxlen]" id="field_maxlen" style="width:50px" value="'+attrs['maxlen']+'" />　提示: 设置为0或空则不限制最大长度<br/>';
  }
  if(typeof(attrs['ismlen'])!='undefined'){
    html+='长度修改：<input type="radio" name="setting[field_ismlen]" value="1" '+(attrs['ismlen']==1?'checked':'')+'> 允许　<input type="radio" name="setting[field_ismlen]" value="0"  '+(attrs['ismlen']==0?'checked':'')+'> 禁止　提示: 字段设置时修改长度<br/>';
  }  
  if(typeof(attrs['default'])!='undefined'){
    var onchangestr="";
    if(k.indexOf("int")!=-1){
      onchangestr='onchange="this.value=this.value.replace(/[^\\d]/gi,\'\')"';
    }else if(k=="float"){
      onchangestr='onchange="this.value=this.value.replace(/[^\\d\\.]/gi,\'\')"';
    }else{
      onchangestr="";
    }
    html+='　默认值：<input type="text" '+onchangestr+' class="txt" name="setting[field_default]" id="field_default" value="'+attrs['default']+'" /><br/>';
  }
  if(typeof(attrs['unsigned'])!='undefined'){
    html+='不带符号：<input type="radio" name="setting[field_unsigned]" value="1" '+(attrs['unsigned']==1?'checked':'')+'> 是　<input type="radio" name="setting[field_unsigned]" value="0"  '+(attrs['unsigned']==0?'checked':'')+'> 否<br/>';
  }
  if(typeof(attrs['index'])!='undefined'){
    html+='允许索引：<input type="radio" name="setting[field_index]" value="1" '+(attrs['index']==1?'checked':'')+'> 是　<input type="radio" name="setting[field_index]" value="0"  '+(attrs['index']==0?'checked':'')+'> 否<br/>';
  }
 $("#type_info").html(html);
 if($.trim(html)==''){
   $(".type_info").hide();
 }else{
   $(".type_info").show();
 }
}

