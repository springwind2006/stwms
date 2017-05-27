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
    if(dx!=0){
      $("#modelTip,#nameTip,#cdirTip,#urlTip").hide();
    }else{
      $("#modelTip,#nameTip,#cdirTip,#urlTip").show();
    }
  });

  $("input,select,textarea").change(function(){top.win.fresh=-1;});
  
  $("#system_db_conn").change(function(){
  	 var dbtype=$(this).val();
  		$.ajax({
	  		dataType:"json",url:act_url("setting","core","dbtype="+dbtype),	  		
	  		success: function(obj){
	  			$("input[name='database[type]']").val(obj['type']);
	  			$("tr[id^='show_']").each(function(){
	  			  var cKey=this.id.substr(5);
	  			  if(typeof(obj[cKey])!="undefined"){
	  			  	$(this).show();
	  			  	if(cKey=='pconnect'){
	  			  		$("input[name='database["+cKey+"]']:eq("+(obj[cKey]?0:1)+")").attr("checked",true);
	  			  	}else{
	  			  		$("input[name='database["+cKey+"]']").val(obj[cKey]);
	  			  	}
	  			  }else{
	  			  	$(this).hide();
	  			  }	  			  
	  			});	  			
	  			if(dbtype.indexOf("mysql")!=-1||dbtype.indexOf("mssql")!=-1){
  			   	$("#test_connect_bt").show();
  			  }else{
  			  	$("#test_connect_bt").hide();
  			  }	  			
			  }
			});    
  });
  
	$("input[name='attachment[w_type]']").click(function(){
	  var isText=this.value==2,isPreview=this.value==0;
  	$("#w_type_image").toggle(!isText);
  	$("#w_type_text").toggle(isText);
  	$("#w_preview").attr("disabled",isPreview);
	});
	
	$("#select_w_color").click(function(){
		colorpicker(this,"title_colorpanel","set_w_color","w_color");
	});
  
  $("#w_preview").click(function(){
  	var water_paras=['w_type','w_minwidth','w_minheight','w_img','w_text','w_fontsize','w_color','w_pct','w_quality','w_pos'],
  	    getVal=function(cname){
  	    	var type=$("input[name='attachment["+cname+"]']").attr("type").toLowerCase();
  	    	return $("input[name='attachment["+cname+"]']"+(type=="radio"||type=="checkbox" ? ":checked":"")).val();
  	    };
  	$(water_paras).each(function(dx,vl){
  		water_paras[dx]="setting["+vl+"]="+encodeURIComponent(getVal(vl));
  	});  	
  	view_images(act_url("setting","web","type=preview&"+water_paras.join("&")+"&t="+(new Date()).getTime()));
  });
  
	if(ROUTE_A=="web" && $('#'+button_placeholder_id)[0]){
		swfu = new SWFUpload({
				flash_url:(SYS_PLUGIN_URL.indexOf("://")!=-1 ? STATIC_URL+"common/others/" : SYS_PLUGIN_URL+"swfupload/")+"swfupload.swf?"+Math.random(),
				upload_url:act_url('setting','web','type=upload'),
				file_post_name : "Filedata",
				post_params:{
					"SWFUPLOADSESSID":PHPSESSID,
					"PHPSESSID":PHPSESSID,
					"filetype_post":"jpg|jpeg|gif|png"
				},
				file_size_limit:"0",
				file_types:"*.jpg;*.jpeg;*.gif;*.png;",
				file_types_description:"Allowed Files",
				file_upload_limit:0,
		 		button_image_url: ADMIN_STATIC_URL+"images/swfbt.png",
				button_width: 64,
				button_height: 20,
				button_placeholder_id: button_placeholder_id,
				button_text:"<span class=\"bt_style\">上传图片</span>",
				button_text_style:".bt_style{font-size:12px;color:red;}",
				button_text_top_padding: 1,
				button_text_left_padding: 7,
				button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
				button_cursor: SWFUpload.CURSOR.HAND,
		    button_action:SWFUpload.BUTTON_ACTION.SELECT_FILE,
				file_queue_error_handler:fileQueueError,
				file_dialog_complete_handler:fileDialogComplete,
				upload_progress_handler:uploadProgress,
				upload_error_handler:uploadError,
				upload_success_handler:uploadSuccess
		});
	}	
	
});

function setting(act,cid,tp){
  switch(act){
  	case 'init':
      deal('setting.init');
    break;
    case 'add':
      var url=(typeof(cid)!="undefined"&&cid!=""?"pid="+cid:"");
      url = url+(typeof(tp)!="undefined"&&tp!=""?(url!=""?"&":"")+"type="+tp:"");
      deal('setting.add',url);
    break;
    case 'edit':
      deal('setting.edit','id='+cid);
    break;
    case 'del':
      confirmurl('?'+ADMIN_INI+'&c=setting&a=del&id='+cid);
    break;
    case 'batedit':
      deal('setting.batedit');
    break;
  }
}

function test_connect(){
  var paras=['DSN','user','pass','dbname'],
  		obj={'database':{'type':$("#system_db_conn").val()}};
  $(paras).each(function(i,v){
    obj['database'][v]=$("#database_"+v).val();
  });
  $("#connect_stat").html("正在连接中...");
  $.ajax({
  		dataType:"html",url:act_url("setting","core","test_connect=1"),
  		data:obj,
  		success: function(r){
				$("#connect_stat").html(r==1?'<font color="green">连接成功！</font>':'<font color="red">'+r+'</font>');
		  }
	});
}

function test_mail(){
  var paras=['type','server','port','from','user','password','to','auth'],
  		obj={'mail':{}};
  $(paras).each(function(i,v){
    obj['mail'][v]=(v=="type"||v=="auth" ? ($("#mail_"+v).attr("checked")?1:0) : $("#mail_"+v).val());
  });
  $("#connect_stat").html("正在测试发送中...");  
  $.ajax({
  		dataType:"html",url:act_url("setting","base","test_mail=1"),
  		data:obj,
  		type:"POST",
  		success: function(r){
				$("#connect_stat").html(r==1?'<font color="green">发送成功！</font>':'<font color="red">'+r+'</font>');
		  }
	});
}

function set_w_color(fd,tp,color){
	$("#"+fd).val(color);
	$("#select_w_color").css("background-color",color);
	top.win.fresh=-1;
}


////////////////////////////////////////////////////////////////////////////
//////////////////////////////文件上传处理事件//////////////////////////////

function fileDialogComplete(numFilesSelected, numFilesQueued){
	try {
		if (this.getStats().files_queued > 0) {
			swfu.setButtonDisabled(true);
		}
		$("#w_img").data("default",$("#w_img").val());
		this.startUpload();
	} catch (ex)  {
    this.debug(ex);
	}
}
function uploadProgress(file, bytesLoaded, bytesTotal){
	var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
	$("#w_img").val("已完成"+percent+"%");
}
function uploadSuccess(file, serverData){
	var res=$.trim(serverData);
	$("#w_img").val(res!='0' ? res : $("#w_img").data("default"));	
	swfu.setButtonDisabled(false);	
	top.win.fresh=-1;
}
function uploadError(file, errorCode, message) {
	swfu.setButtonDisabled(false);
	$("#w_img").val($("#w_img").data("default"));
}
function fileQueueError(file, errorCode, message){
	swfu.setButtonDisabled(false);
	$("#w_img").val($("#w_img").data("default"));
}