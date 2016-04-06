
/*
功能：文件上传完毕，生成文件缩略图
参数：(serverData 服务器返回数据,file 当前文件信息)
*/
function att_show(serverData,file){
	var img,serverData = serverData.replace(/<div.*?<\/div>/g,''),
			data = serverData.split(','),
			id = data[0],
			src = UPLOAD_URL+data[1],
			ext = data[2],
			filename = data[3].replace(/"'/g,''),
			isupload = data[4],
			authKey=data[5];
	if(id == 0) {
		return false;
	}
	img = '<a href="javascript:;" onclick="att_cancel(this,'+id+',\''+authKey+'\')" class="on">'+
          '<div class="icon"></div>'+
          '<img width="80" imgid="'+id+'" isupload="'+isupload+'"  path="'+src+'" title="'+filename+'" src="'+(ext == 1 ? src : STATIC_URL+'common/images/ext/'+ext+'.png')+'" />'+
        '</a>';
	$('#fsUploadProgress').append('<li><div id="attachment_'+id+'" class="img-wrap"></div></li>');
	$('#attachment_'+id).html(img);
	$('#att-path').append('|'+src);
	$('#att-name').append('|'+filename);
	$('#att-id').append('|'+id);
}

/*
功能：取消/选择已经上传的文件，同时联动服务器端标记是否为未处理
参数：(obj 当前对象,id 文件ID)
说明：当点击取消的时候就将此文件标记为未处理文件
*/
function att_cancel(obj,id,authKey){
	var src = $(obj).children("img").attr("path"),
	    filename = $(obj).children("img").attr("title"),
	    isupload = $(obj).children("img").attr("isupload"),
	    act="",
	    get_status=function(tp,isdel){
			  var stp=(tp!="on"?"class!='on'":"class='on'"),rObj={},
			      paths = names = ids = '';
				$("#fsUploadProgress a["+stp+"]").children("img"+(isdel?"[isupload='1']":"")).each(function(){
				  paths += '|'+$(this).attr('path');
					names += '|'+$(this).attr('title');
					ids += '|'+$(this).attr('imgid');
				});
				rObj['path']=paths;
				rObj['name']=names;
				rObj['id']=ids;
				return rObj;
		  };

	var onObj,offObj;
	if($(obj).hasClass('on')){
		$(obj).removeClass("on");
		onObj=get_status("on");
		$('#att-path').html(onObj['path']);
		$('#att-name').html(onObj['name']);
		$('#att-id').html(onObj['id']);
	} else {
		$(obj).addClass("on");
		$('#att-path').append('|'+src);
		$('#att-name').append('|'+filename);
		$('#att-id').append('|'+id);
		act="_del";
	}

	if(isupload!=1){
		$.get(SYS_ENTRY+'?attachment&a=swfupload_json'+act+'&aid='+id+'&src='+src+'&filename='+filename+"&authkey="+authKey);
  }
	offObj=get_status("off",1);
	$('#att-del').html(offObj['id']);
}



////////////////////////////////////////////////////////////////////////////
//////////////////////////////文件上传处理事件//////////////////////////////
function fileDialogStart() {
	/* I don't need to do anything here */
}
function fileQueued(file) {
	if(file!= null){
		try {
			var progress = new FileProgress(file, this.customSettings.progressTarget);
			progress.toggleCancel(true, this);
		} catch (ex) {
			this.debug(ex);
		}
	}
}
function fileDialogComplete(numFilesSelected, numFilesQueued){
	try {
		if (this.getStats().files_queued > 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = false;
		}
		/* I want auto start and I can do that here */
		//this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}
function uploadStart(file){
	var progress = new FileProgress(file, this.customSettings.progressTarget);
	progress.setStatus(swfu_load_lang("onwait"));
	return true;
}
function uploadProgress(file, bytesLoaded, bytesTotal){
	var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
	var progress = new FileProgress(file, this.customSettings.progressTarget);
	progress.setProgress(percent);
	progress.setStatus(swfu_load_lang("onprogress",{'percent':percent}));
}
function uploadSuccess(file, serverData){
	att_show(serverData,file);//文件上传成功，显示缩略图
	var progress = new FileProgress(file, this.customSettings.progressTarget);
	progress.setComplete();
	progress.setStatus(swfu_load_lang("onsuccess"));
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
			msg = swfu_load_lang("onerror_http");
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			msg = swfu_load_lang("onerror");
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			msg = swfu_load_lang("onerror_io");
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			msg = swfu_load_lang("onerror_auth");
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			msg = swfu_load_lang("onerror_safe_check");
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			msg = swfu_load_lang("onerror_cancel");
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			msg = swfu_load_lang("onerror_stop");
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			msg = swfu_load_lang("onerror_file_limit",{'limit':swfu.settings.file_upload_limit});
			break;
		default:
			msg = message;
			break;
	}
	var progress = new FileProgress(file,this.customSettings.progressTarget);
	progress.setError();
	progress.setStatus(msg);
}
function fileQueueError(file, errorCode, message){
	var errormsg;
	switch (errorCode) {
	case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
		errormsg = swfu_load_lang("onerror_empty_file");
		break;
	case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
		errormsg = swfu_load_lang("onerror_much_file");
		break;
	case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
		errormsg = swfu_load_lang("onerror_large_file");
		break;
	case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
		errormsg = swfu_load_lang("onerror_format_file");
	default:
		errormsg = swfu_load_lang("onerror");
		break;
	}
	var progress = new FileProgress('file',this.customSettings.progressTarget);
	progress.setError();
	progress.setStatus(errormsg);
}



var swfu_scriptSrc = document.getElementsByTagName('script')[document.getElementsByTagName('script').length - 1].src;
var swfu_get_para=function(url,ky,dft){
	if(url.indexOf("?")!=-1){
		var params=url.substr(url.indexOf("?")+1).split("&"),mths;
		for(var i=0;i< params.length;i++){
			mths=params[i].split("=");
			if(mths[0]==ky){
				return mths[1];
			}
		}
	}
	return dft;
};
var swfu_load_lang=function(ky,para){
	if(typeof(ky)=="undefined"){
		var scriptSrcArray = swfu_scriptSrc.split('/'),
				jsName = scriptSrcArray[scriptSrcArray.length-1],
				themedir = swfu_scriptSrc.replace(jsName,''),
				lang=swfu_get_para(swfu_scriptSrc,'lang','en');
		jQuery.ajax({async:false,cache:true,type: "GET",url: themedir + "lang/"+lang+".js",dataType: "script",
			error :function(){
				jQuery.ajax({async:false,cache:true,type: "GET",url: themedir + "lang/en.js",dataType: "script"});
			}
		});
	}else{
		var cLang=typeof(SWFupload_LANG[ky])!="undefined" ? SWFupload_LANG[ky] : ky;
		if(typeof(para)=="object"){
			for(var ckey in para){
				cLang=cLang.replace("{"+ckey+"}",para[ckey]);
			}
		}
		return cLang;
	}
};
swfu_load_lang();
