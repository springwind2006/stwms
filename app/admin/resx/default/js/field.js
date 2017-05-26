$(function(){
  if(ROUTE_A=='add'||ROUTE_A=='edit'){
	 	$.formValidator.initConfig({formID:"myform",autoTip:true,mode:'AutoTip'});
		if(!$("#formtype").attr("disabled")){
		  $("#field").formValidator({onShow:"请输入字段名",onFocus:"字段名长度必须为1-20位"}).
			    regexValidator({regExp:"^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){0,19}$",onError:"字段名称不正确"}).
			    inputValidator({min:1,max:20,onError:"字段名长度必须为1-20位"}).
			    ajaxValidator({
				  type : "get",
					url : "?"+ADMIN_INI+"&c=field&a=init&check=1"+(ROUTE_A=='edit'?"&id="+p.$_GET("id"):"")+"&tbname="+p.$_GET("tbname"),
					dataType : "html",
					cached:false,
					async:'false',
					success : function(data){
	            if( data == "1" ){
	              return true;
							} else {
				        return false;
							}
					},
					buttons: $("#dosubmit"),
					onError : "字段名已经存在",
					onWait : "正在连接...请稍等"
			});



			$("#formtype").formValidator({onShow:"类型名称",onFocus:"选择一种字段类型"}).inputValidator({min:1,onError:"请选择字段类型,请确认"});
	    $("#formtype").change(function(){
			   var cv=$(this).val();
			   if(cv!=""){
			   	  $.ajax({
							  url:"?"+ADMIN_INI+"&c=field&a=init&get_setting_form=1&formtype="+cv,
							  dataType:"xml",
							  cache: false,
							  success: function(r, textStatus, jqXHR){
								  	var addForm=$("add_form:eq(0)",r).text();
								  	$("#setting td").html(addForm);
								    if(p.trim(addForm)!=""){
						   	      $("#setting").show();
						   	    }else{
						   	      $("#setting").hide();
						   	    }                  
	                  
								  	if($("msetting:eq(0)",r).attr("isshow")==1){
								  	  $("#msetting").show();
								  	}else{
								  	  $("#msetting").hide();
								  	}
								  	if($("msetting:eq(0)",r).attr("istolist")==0){
								  	  $("#msetting_istolist input[type='radio']:eq(1)").attr('checked','checked');
								  	  $("#msetting_istolist input[type='radio']:lt(2)").attr('disabled',true);
								  	  $("#msetting_istolist span:eq(0)").css('visibility','hidden');
								  	}else{
								  	  $("#msetting_istolist input[type='radio']:lt(2)").attr('disabled',false);
								  	}
								  	if($("msetting:eq(0)",r).attr("isorder")==0){
								  	  $("#msetting_isorder input[type='radio']:eq(1)").attr('checked','checked');
								  	  $("#msetting_isorder input[type='radio']:lt(2)").attr('disabled',true);
								  	}else{
								  	  $("#msetting_isorder input[type='radio']:lt(2)").attr('disabled',false);
								  	}
								  	if($("msetting:eq(0)",r).attr("issearch")==0){
								  	  $("#msetting_issearch input[type='radio']:eq(1)").attr('checked','checked');
								  	  $("#msetting_issearch input[type='radio']:lt(2)").attr('disabled',true);
								  	}else{
								  	  $("#msetting_issearch input[type='radio']:lt(2)").attr('disabled',false);
								  	}
	                   
	                  /*数据库设置*/
	                  $("#dsetting").show();
	                  var field_default=$("dsetting:eq(0)",r).attr("field_default"),
	                      field_index=$("dsetting:eq(0)",r).attr("field_index"),
	                      field_maxlen=$("dsetting:eq(0)",r).attr("field_maxlen"),
	                      field_ismlen=$("dsetting:eq(0)",r).attr("field_ismlen");
	                      
	                  if(ROUTE_A=='add' && typeof(field_default)!="undefined"){
	                     $("#setting input[name='setting[defaultvalue]']").val(field_default);
	                  }
	                  if(typeof(field_maxlen)!="undefined"){
	                    $("#maxlength").data("maxlen",field_maxlen);
	                  }
	                  
	                  if(typeof(field_ismlen)!="undefined"&&field_ismlen==1){
	                    $("input",$("#ismlen").show()).val(field_maxlen);
	                  }else{
	                  	$("#ismlen").hide();
	                  }	  
	                                  
	                  if(typeof(field_index)!="undefined"){
	                  	$("#dsetting input[name='dsetting[isindex]']").attr("disabled",false);
	                    $("#dsetting input[name='dsetting[isindex]']:eq("+(1-field_index)+")").attr("checked",true);
	                  }else{
	                  	$("#dsetting input[name='dsetting[isindex]']:eq(1)").attr("checked",true);
	                    $("#dsetting input[name='dsetting[isindex]']").attr("disabled",true);
	                  }
							  }
						});
			   }else{
			      $("#setting,#msetting,#dsetting").hide();
			   }
			});
			if(ROUTE_A=='edit'){$("#field,#formtype").defaultPassed();}
	  }
		$("#name").formValidator({onShow:"字段别名",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"类型名称两边不能有空符号"},onError:"类型名称不能为空,请确认"});

	  if(ROUTE_A=='edit'){$("#name").defaultPassed();}


	  $("#msetting_istolist input[type='radio']:lt(2)").click(function(){
        var selectedDx=$("#msetting_istolist input[type='radio']:lt(2)").index($(this));
        $("#msetting_istolist span:eq(0)").css('visibility',selectedDx==0?'visible':'hidden');
    });


		$("input,textarea").change(function(){top.win.fresh=-1;});
		$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});

		$("#minlength,#maxlength").change(function(){
			var cv=$(this).val().replace(/[^\d]+/gi,"");
			$(this).val(cv);
		  if($(this).attr("id")=="maxlength"){
		  	var minCV=$("#minlength").val().replace(/[^\d]+/gi,""),
		  	    maxLen=$("#maxlength").data("maxlen");		  	
		    if(cv!==""){
		    	 if(minCV&&parseInt(cv)< parseInt(minCV)){
		    	   $(this).val(minCV);
		    	 }
		    	 if(maxLen && parseInt(cv) > parseInt(maxLen)){
		    	   $(this).val(maxLen);
		    	 }
		    }
		  }
		});

		$("#iscore1").click(function(){
			var cv=$("#minlength").val().replace(/[^\d]+/gi,"");
		  if(cv==0){
		    $("#minlength").val("1");
		  }
		});
		
		$("#pattern_select").change(function(){
			var cVL=$(this).val();
			$("#pattern").val(cVL);
			$("#pattern").attr("readonly",cVL!="");
		});		

		$("#setting input[name='setting[ispassword]']").live('click',function(){
			$(this).nextAll("span").css("visibility",($(this).val()!=0 ? "visible" : "hidden"));
		});		
		
  }
});


function field_preview(tb,opentype,ctl,w,h){
	var winW=$(top.document).width(),winH=$(top.document).height(),
	    w=w?w:(winW< 1200 ? 1000: 1200),h=h?h:winH;
	if(opentype==0){
	  top.doMainFrame.location=SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=preview&tbname="+tb+"&opentype="+opentype;
	}else if(opentype==1){
	  top.win.diag(ROUTE_C+'.preview','tbname='+tb+"&opentype="+opentype,{'tl':ctl,'w':w,'h':h})
	}else{
		var para='height='+h+', width='+w+',left='+((winW-w)/2)+',top='+((top.window.screen.availHeight-h)/2)+',toolbar=no, menubar=no,scrollbars=yes, resizable=yes, location=no, status=no';
	  window.open(SYS_ENTRY+'?'+ADMIN_INI+"&c="+ROUTE_C+"&a=preview&tbname="+tb+"&opentype="+opentype,'',para);
	}
}


function field(act,tb,fid,w,h){
  switch(act){
  	case 'preview':
      field_preview(tb,2,"预览模型-"+tb,w,h);
    break;
    case 'add':
      deal(ROUTE_C+'.add','tbname='+tb);
    break;
    case 'edit':
      deal(ROUTE_C+'.edit','tbname='+tb+"&id="+fid);
    break;
    case 'del':
      art.dialog.confirm("确定要删除此字段吗？请谨慎操作！",function(){
         deal(ROUTE_C+'.del','tbname='+tb+"&id="+fid);
      });
    break;
    default:
      deal(ROUTE_C+'.init','tbname='+tb);
    break;
  }
}
