var swfu,msg={
	'0':"上传文件失败！",
	'1':"不允许上传的文件类型！",
	'2':"解压失败！",
	'3':"解压成功！",
	'4':"插件已经存在！",
	'5':"插件安装失败！",
	'6':"插件安装成功！",
	'7':"请勿重复安装插件！",
	'8':"插件文件损坏，安装失败！",
	'9':"权限不足或服务器未知错误！"
};
$(function(){
	if(ROUTE_A=="install" && $('#addnew')[0]){
		swfu = new SWFUpload({
				flash_url:(SYS_PLUGIN_URL.indexOf("://")!=-1 ? STATIC_URL+"common/others/" : SYS_PLUGIN_URL+"swfupload/")+"swfupload.swf?"+Math.random(),
				upload_url:act_url('plugin','install','type=upload'),
				file_post_name : "Filedata",
				post_params:{
					"SWFUPLOADSESSID":PHPSESSID,
					"PHPSESSID":PHPSESSID,
					"dosubmit":"1",
					"filetype_post":"zip"
				},
				file_size_limit:"0",
				file_types:"*.zip",
				file_types_description:"Allowed Files",
				file_upload_limit:0,
		 		button_image_url: ADMIN_STATIC_URL+"images/swfbt.png",
				button_width: 64,
				button_height: 20,
				button_placeholder_id: button_placeholder_id,
				button_text:"<span class=\"bt_style\">选择插件</span>",
				button_text_style:".bt_style{font-size:12px;color:red;}",
				button_text_top_padding: 1,
				button_text_left_padding: 7,
				button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
				button_cursor: SWFUpload.CURSOR.HAND,
		    button_action:SWFUpload.BUTTON_ACTION.SELECT_FILE,
				file_dialog_start_handler : fileDialogStart,
				file_queued_handler : fileQueued,
				file_queue_error_handler:fileQueueError,
				file_dialog_complete_handler:fileDialogComplete,
				upload_progress_handler:uploadProgress,
				upload_error_handler:uploadError,
				upload_success_handler:uploadSuccess,
				upload_complete_handler:uploadComplete
		});
		art.dialog.data("ifCloseFresh",false);
		art.dialog.open.api.button({name: '确定',disabled:false});
	}
	if(ROUTE_A=="urlset"){
		$.formValidator.initConfig({formID:"myform",autoTip:true,mode:'AutoTip'});
		$("#simple_url").formValidator({onFocus:"为空则不简化地址"}).
		 					 regexValidator({regExp:"(?:^\\w[\\w/]+/$)|(?:^$)",onError:"填写目录名(以/结尾)"}).
		         	 ajaxValidator({
								  type : "get",
									url : "?"+ADMIN_INI+"&c=plugin&a=urlset&check=1",
									dataType : "html",
									cached:false,
									async:'false',
									success : function(data){
										return data==1;
									},
									onError : "同级目录存在！",
									onWait : "正在连接...请稍等"
							 });
		$("#simple_url").defaultPassed().change(function(){top.win.fresh=-1;});
	}
});


function plugin(act,para){
  switch(act){
    case 'install':
      top.art.dialog.open(act_url(ROUTE_C,'install'),{
      		title:'插件安装',width:620,height:128,lock:true,opacity:0.25,
      		ok:true,
      		close:function(){
      			if(art.dialog.data("ifCloseFresh")){
      				top.doMainFrame.location.reload();
      			}
      		  return true;
      		}
      	}
      );
    break;
    case 'uninstall':
      var cstr="确定要卸载此插件吗？此插件相关的所有数据将会被删除！<br/>【此过程不可还原，请谨慎操作！】";
       art.dialog.confirm(cstr, function(){
          deal(ROUTE_C+'.uninstall',"id="+para);
       });
    break;
    case 'setmenu':
      top.art.dialog.open(act_url(ROUTE_C,'setmenu'),{
      		title:'设置菜单',width:620,height:400,lock:true,opacity:0.25,
      		ok:true,
      		close:function(){
      		  return true;
      		}
      	}
      );
    break;
    case 'urlset':
      top.win.diag(ROUTE_C+'.urlset',"id="+para,{'tl':'URL简化设置','w':500,'h':160});
    break;
    default:
      deal(ROUTE_C+'.init');
    break;
  }
}


function lockState(state){
	art.dialog.open.api.button({name: '确定',disabled:state});
	swfu.setButtonDisabled(state);
}

function progressInstall(ctype,dt){
  switch(ctype){
  	case 'upload':
			var code=$.trim(dt);
			if(code.match(/^\d$/gi)){
				$("#upload_status").html(msg[code]);
				lockState(false);
			}else if(code.match(/^temp\d+$/gi)){
				var forceInstall=$("input[name='force_install']:eq(0)").attr('checked')?1:0;
  			$.ajax({
		  		dataType:"json",url:act_url('plugin','install','dosubmit=1&type=unzip&force_install='+forceInstall),
		  		data:{"sourceName":code},
		  		cache:false,
		  		success: function(obj){
		  			progressInstall('unzip',obj);
				  }
				});
				$("#upload_status").html("正在解压...");
			}else{
				lockState(false);
				$("#upload_status").html(msg['9']);
			}
  	break;
  	case 'unzip':
  		if(typeof(dt)=="object"&&typeof(dt['code'])!="undefined"){
				if(dt['code']==3){
					$("#upload_status").data("uploadpara",dt);
					$.ajax({
			  		dataType:"html",url:act_url('plugin','install','dosubmit=1&type=install'),
			  		data:dt,
			  		cache:false,
			  		success: function(r){
			  			progressInstall('install',r);
					  }
					});
					$("#upload_status").html("正在安装插件...");
				}else{
					$("#upload_status").html(msg[dt['code']]);
					lockState(false);
				}
			}else{
				lockState(false);
				$("#upload_status").html(msg['9']);
			}
  	break;
  	case 'install':
  	  var code=$.trim(dt);
  		if(code.match(/^[\d+]$/gi)){
				$("#upload_status").html(msg[code]);
				$("#upload_status").removeData("uploadpara");
				art.dialog.data("ifCloseFresh",code==6);
			}else{
				$("#upload_status").html(msg['8']);
				art.dialog.data("ifCloseFresh",false);
				//此次请求用于删除服务端缓存数据
				$.ajax({
			  		dataType:"html",url:act_url('plugin','install','dosubmit=1&type=clear'),
			  		data:$("#upload_status").data("uploadpara"),
			  		cache:false,
			  		success: function(r){
			  			$("#upload_status").removeData("uploadpara");
					  }
				});
			}
			lockState(false);
  	break;
  }
}



////////////////////////////////////////////////////////////////////////////
//////////////////////////////文件上传处理事件//////////////////////////////
function fileDialogStart() {
	/* I don't need to do anything here */
}
function fileQueued(file) {
	if(file!= null){
		try {
		} catch (ex) {
			this.debug(ex);
		}
	}
}
function fileDialogComplete(numFilesSelected, numFilesQueued){
	try {
		if (this.getStats().files_queued > 0) {
			lockState(true);
		}
		this.startUpload();
	} catch (ex)  {
    this.debug(ex);
	}
}
function uploadStart(file){
	return true;
}
function uploadProgress(file, bytesLoaded, bytesTotal){
	var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
	$("#upload_status").html("插件上传中......已上传"+percent+"%");
}
function uploadSuccess(file, serverData){
	progressInstall("upload",serverData);
}
function uploadComplete(file){
	if (this.getStats().files_queued > 0)	{
		 this.startUpload();
	}
}
function uploadError(file, errorCode, message) {
	var msg;
	switch (errorCode){
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			msg = "上传错误: " + message;
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			msg = "上传错误";
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			msg = "服务器 I/O 错误";
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			msg = "服务器安全认证错误";
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			msg = "附件安全检测失败，上传终止";
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			msg = '上传取消';
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			msg = '上传终止';
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			msg = '单次上传文件数限制为 '+swfu.settings.file_upload_limit+' 个';
			break;
		default:
			msg = message;
			break;
	}
}
function fileQueueError(file, errorCode, message){
	var errormsg;
	switch (errorCode) {
	case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
		errormsg = "请不要上传空文件";
		break;
	case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
		errormsg = "队列文件数量超过设定值";
		break;
	case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
		errormsg = "文件尺寸超过设定值";
		break;
	case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
		errormsg = "文件类型不合法";
	default:
		errormsg = '上传错误，请与管理员联系！';
		break;
	}
}

