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
  if(ROUTE_A=='add'||ROUTE_A=='edit'||ROUTE_A=='import'){
	 	$.formValidator.initConfig({formID:"myform",mode:'AutoTip'});
	 	if(ROUTE_A=='add'||ROUTE_A=='import'){
			$("#tbname").formValidator({onShow:"请输入表名，添加成功后不可修改表名",onFocus:"表名至少1个字符,最多10个字符"})
			        .regexValidator({regExp:"^[a-z][\\w]*$",onError:"您输入的表名称字符不合法！"})
			        .ajaxValidator({
			        	type : "get",
			        	cached:false,
								dataType : "html",
								async : true,
								url : "?"+ADMIN_INI+"&c=model&a=init&check=1",
								success : function(data){
			            if(data=='1')return true;
			            if(data=='0')return false;
									return false;
								},
								buttons: $("#dosubmit"),
								error: function(jqXHR, textStatus, errorThrown){alert("服务器没有返回数据，可能服务器忙，请重试"+errorThrown);},
								onError : "该表名已经该存在，请重新输入",
								onWait : "正在对表名进行合法性校验，请稍候..."
							});

			$("#attr_type input").click(function(){
			  var ctype=$(this).val();
			  if(ctype==2){
			  	$("#attr_disabled input:eq(0),#applytocat input:eq(1)").attr("checked","checked");
			    $("#attr_disabled input,#applytocat input").attr("disabled",true);
			  }else{
			    $("#attr_disabled input,#applytocat input").attr("disabled",false);
			    $("#applytocat input:eq(0)").attr("checked","checked");
			  }
			});
		}
		$("#name").formValidator({onShow:"模型名称",onFocus:"至少1个长度"}).inputValidator({min:1,empty:{leftEmpty:false,rightEmpty:false,emptyError:"模型名称两边不能有空符号"},onError:"模型名称不能为空,请确认"});
    if(ROUTE_A=='edit'){$("#name").defaultPassed();}
	  /*待添加或修改内容有改变则刷新*/
	  if(ROUTE_A=='edit'||ROUTE_A=='add'){
			$("input,textarea").change(function(){top.win.fresh=-1;});
			$("#myform").submit(function(){if(top.win.fresh==-1){top.win.fresh=1};});
		}
		
		$("input[name='attr[iscat]']").click(function(){
			var isCat=($(this).val()==1);
			$(this).siblings("span").toggle(isCat);
		});

  }
  
	if(ROUTE_A=="import" && $('#addnew')[0]){
		swfu = new SWFUpload({
				flash_url:(SYS_PLUGIN_URL.indexOf("://")!=-1 ? STATIC_URL+"common/others/" : SYS_PLUGIN_URL+"swfupload/")+"swfupload.swf?"+Math.random(),
				upload_url:act_url('model','import','type=upload'),
				file_post_name : "Filedata",
				post_params:{
					"SWFUPLOADSESSID":PHPSESSID,
					"PHPSESSID":PHPSESSID,
					"dosubmit":"1",
					"filetype_post":"model"
				},
				file_size_limit:"0",
				file_types:"*.model",
				file_types_description:"Allowed Files",
				file_upload_limit:0,
		 		button_image_url: ADMIN_STATIC_URL+"images/swfbt.png",
				button_width: 64,
				button_height: 20,
				button_placeholder_id: button_placeholder_id,
				button_text:"<span class=\"bt_style\">选择模型</span>",
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
	}  
  
});


function model(act,tb){
  switch(act){
    case 'add':
      top.win.diag(ROUTE_C+'.add',{'tl':'添加模型','w':620,'h':373})
    break;
    case 'edit':
      top.win.diag(ROUTE_C+'.edit','tbname='+tb,{'tl':'编辑模型','w':620,'h':373})
    break;
    case 'del':
      var cstr="确定要删除此模型吗？表结构及数据也将被删除！<br/>【此过程不可还原，请谨慎操作！】";
      art.dialog.confirm(cstr, function(){
          deal(ROUTE_C+'.del',"tbname="+tb);
       });
    break;
    case 'import':      
      top.art.dialog.open(act_url(ROUTE_C,'import'),{
      		title:'导入模型',width:620,height:230,lock:true,opacity:0.25,
      		ok:function(){
      		  var iframe = this.iframe.contentWindow,isPass=false,
      		      btObj=$("button:eq(0)",this.DOM.buttons[0]),
      		      isFirst=btObj.html()=="下一步";
      		  if(isFirst){
	      		  try{isPass=iframe.$.formValidator.pageIsValid('1');}catch(e){}
	      		  if(isPass){	
	      		  	$("#upload_status",iframe.document).html($("#upload_status",iframe.document).data("defstatus"));      		  	
	       		  	$("#step1",iframe.document).hide();
	      		  	$("#step2",iframe.document).show();      		  	
	      		  	iframe.$.formValidator.reloadAutoTip();
	      		  	this.size(620,95);
	      		  	btObj.html("上一步");
	      		  	$("button:eq(1)",this.DOM.buttons[0]).html("关闭");
	      		  	//增加上传参数
	      		  	iframe.swfu.addPostParam('attr[tbname]',$("#tbname",iframe.document).val());
	      		  	iframe.swfu.addPostParam('attr[name]',$("#name",iframe.document).val());
	      		  	iframe.swfu.addPostParam('attr[desc]',$("#desc",iframe.document).val());
	      		  	iframe.swfu.addPostParam('attr[iscat]',$("input[name='attr[iscat]']:eq(0)",iframe.document).attr('checked')?1:0);
	      		  	iframe.swfu.addPostParam('attr[width]',$("input[name='attr[width]']:eq(0)",iframe.document).val());
	      		  	iframe.swfu.addPostParam('attr[height]',$("input[name='attr[height]']:eq(0)",iframe.document).val());
	      		  }
      			}else{
       		  	$("#step1",iframe.document).show();
      		  	$("#step2",iframe.document).hide();      		  	
      		  	iframe.$.formValidator.reloadAutoTip();
      		  	this.size(620,230);
      		  	btObj.html("下一步");
      		  	$("button:eq(1)",this.DOM.buttons[0]).html("取消");      				
      			}
      		  return false;
      		},
      		okVal:'下一步',
      		cancel:true,
      		cancelVal:'取消',
      		close:function(){
      			if(art.dialog.data("ifCloseFresh")){
      				top.doMainFrame.location.reload();
      			}
      			art.dialog.data("ifCloseFresh",false);
      		  return true;
      		},
      		init:function(){
      			var iframe=this.iframe.contentWindow;
      			$("#upload_status",iframe.document).data("defstatus",$("#upload_status",iframe.document).html());
      		}      		    		
      	}
      );    	
    break;
    case 'field':
    	deal('field.init',"tbname="+tb);
    break;
    case 'install':
    	deal(ROUTE_C+'.install',"tbname="+tb);
    break;
    case 'uninstall':
      var cstr="确定要卸载此模型吗？表结构及数据将被删除！<br/>【此过程不可还原，请谨慎操作！】";
       art.dialog.confirm(cstr, function(){
          deal(ROUTE_C+'.uninstall',"tbname="+tb);
       });
    break;
    default:
      deal(ROUTE_C+'.init');
    break;
  }
}

function lockState(state){
	art.dialog.open.api.button({name: '下一步',disabled:state});
	swfu.setButtonDisabled(state);
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
	$("#upload_status").html("正在安装......已完成"+percent+"%");
}
function uploadSuccess(file, serverData){
	if(serverData.match(/^\d$/)){
		var sid=parseInt(serverData),
				statuses=["安装失败！","已经存在此模型！","已经成功导入模型！","已经成功导入并安装模型！"];
		$("#upload_status").html(statuses[sid]);
	}else{
		$("#upload_status").html("安装失败，系统未知错误！");
	}
	art.dialog.data("ifCloseFresh",true);
	lockState(false);
}
function uploadComplete(file){
	if (this.getStats().files_queued > 0)	{
		 this.startUpload();
	}
	lockState(false);
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
	$("#upload_status").html(msg);
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
	$("#upload_status").html(errormsg);
}
