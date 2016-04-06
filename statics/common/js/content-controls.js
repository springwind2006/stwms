/**
说明：
此函数库为控件所用函数，在加载控件时必须加载此函数库控件才能正常运行
*/

/**
功能：参数flash上传对话框
参数：(uploadid 对话框id,name 对话框标题,textareaid 编辑器id,funcName 回调函数,
       args url参数,model 模块名称,catid 目录id,authkey 授权id,isfile 是否是文件,isadmin 是否为管理模式)
说明：args url参数
*/
function flashupload(uploadid,name,textareaid,funcName,args,model,catid,authkey,isfile,isadmin){
	var args = args?'&args='+p.enCode(args):'';
	var setting = '&model='+model+'&catid='+catid+'&authkey='+authkey+'&isfile='+(isfile?1:0)+'&isadmin='+(isadmin?1:0);
	art.dialog.open(
	  SYS_ENTRY+"?c=attachment&a=swfupload"+args+setting,
		{
			title:name,id:uploadid,width:500,height:450,fixed:true,
			ok:
				function(){
					if(funcName){
						 funcName.apply(this,[uploadid,textareaid]);
					}else{
						 submit_ckeditor(uploadid,textareaid);
					}
	      },
      cancel: true,
      close:
        function(){
        	var d = art.dialog.list[uploadid].iframe.contentWindow,
        	    del_ids = d.$("#att-del").html();
        	if(del_ids!=""){
        	  $.get(SYS_ENTRY+"?c=attachment&a=swfdelete&data="+del_ids.substr(1));
        	}
        	return true;
        }
	  }
	);
}

/**
功能：将附件插入到编辑器中
参数：(uploadid 上传id,textareaid 编辑器id)
*/
function submit_ckeditor(uploadid,textareaid){
	var d = art.dialog.list[uploadid].iframe.contentWindow;
	var in_content = d.$("#att-path").html();
	var in_name = d.$("#att-name").html();
	insert2editor(textareaid,in_content,in_name);
}

/**
功能：插入内容到编辑器
参数：(id 编辑器id,in_content 插入内容地址,in_name 插入内容名称)
*/
function insert2editor(id,in_content,in_name) {
	if(in_content == '') {return false;}
	var data = in_content.substring(1).split('|');
	var img = '';
	for (var n in data) {
		img += IsImg(data[n]) ?
		'<img src="'+data[n]+'" /><br />' :
		(
    		IsSwf(data[n]) ?
    		'<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"><param name="quality" value="high" /><param name="movie" value="'+data[n]+'" /><embed pluginspage="http://www.macromedia.com/go/getflashplayer" quality="high" src="'+data[n]+'" type="application/x-shockwave-flash" width="460"></embed></object>' :
    		'<a href="'+data[n]+'" />'+data[n]+'</a><br />'
		) ;
	}

	CKEDITOR.instances[id].insertHtml(img);
}


/*******【image】单图上传控件******/
/**
功能：image单图上传控件回调函数(文本框形式)
参数：(uploadid 上传id,returnid 返回id)
*/
function image_input(uploadid,returnid){
	var d = art.dialog.list[uploadid].iframe.contentWindow;
	var in_content = d.$("#att-path").html().substring(1);
	var in_content = in_content.split('|');
	IsImg(in_content[0]) ? $('#'+returnid).attr("value",in_content[0]) : alert('选择的类型必须为图片类型');
}
/**
功能：image单图上传控件回调函数(缩略图形式)
参数：(uploadid 上传id,returnid 返回id)
*/
function image_thumb(uploadid,returnid) {
	var d = art.dialog.list[uploadid].iframe.contentWindow;
	var in_content = d.$("#att-path").html().substring(1);
	if(in_content=='') return false;
	if(!IsImg(in_content)) {
		alert('选择的类型必须为图片类型');
		return false;
	}

	if($('#'+returnid+'_preview').length) {
		var url=act_url("api","thumb","w=120&h=100&f="+encodeURIComponent(in_content),1);
		$('#'+returnid+'_preview').css("background-image","url("+url+")");
	}
	$('#'+returnid).val(in_content);
}

function image_cancel(field){
	$("#"+field+"_preview").css("background-image","url("+STATIC_URL+"common/images/upload-pic.png)");
	$("#"+field).val("");
}

/*******【images】多图上传控件******/
/**
功能：images多图上传控件回调函数
参数：(uploadid 上传id,returnid 返回id)
*/
function images_change(uploadid,returnid){
	var d = art.dialog.list[uploadid].iframe.contentWindow;
	var in_content = d.$("#att-path").html().substring(1);
	var in_filename = d.$("#att-name").html().substring(1);
	var in_mid = d.$("#att-id").html().substring(1);

	var str = $('#'+returnid).html();
	var contents = in_content.split('|');
	var filenames = in_filename.split('|');
	var in_mids = in_mid.split('|');

	$('#'+returnid+'_tips').css('display','none');
	if(contents=='') return true;
	$.each( contents, function(i, url) {
				var ids = parseInt(Math.random() * 10000 + 10*i);
				var filename = filenames[i].substr(0,filenames[i].indexOf('.'));
				str += "<li id='image"+ids+"'>"+
				         "<input type='text' name='"+returnid+"_url[]' value='"+url+"' style='width:300px;' ondblclick='view_images(this.value);' class='input-text' />"+
				         "<input type='text' name='"+returnid+"_alt[]' value='"+filename+"' style='width:380px;' class='input-text' onblur=\"if(this.value.replace(' ','') == '') this.value = this.defaultValue;\" />"+
				         "<a href=\"javascript:\" onclick=\"remove_upload(this,"+in_mids[i]+",'"+url+"','"+filenames[i]+"')\">移除</a>"+
				       "</li>";
	   });
	$('#'+returnid).html(str);
}

/*******【downfiles】多文件下载控件******/
/**
功能：downfiles多文件上传回调函数
参数：(uploadid 上传id,returnid 返回id)
*/
function downfiles_change(uploadid,returnid){
	var d = art.dialog.list[uploadid].iframe.contentWindow;
	var in_content = d.$("#att-path").html().substring(1);
	var in_filename = d.$("#att-name").html().substring(1);
	var in_mid = d.$("#att-id").html().substring(1);

	var str = '';
	var contents = in_content.split('|');
	var filenames = in_filename.split('|');
	var in_mids = in_mid.split('|');

	$('#'+returnid+'_tips').css('display','none');
	if(contents=='') return true;
	$.each(contents,function(i, url) {
				var ids = parseInt(Math.random() * 10000 + 10*i);
				var filename = filenames[i].substr(0,filenames[i].indexOf('.'));
				str += "<li id='downfiles"+ids+"'>"+
				         "<input type='text' name='"+returnid+"_fileurl[]' value='"+url+"' style='width:300px;' class='input-text' />"+
				         "<input type='text' name='"+returnid+"_filename[]' value='"+filename+"' style='width:380px;' class='input-text' onblur=\"if(this.value.replace(' ','') == '') this.value = this.defaultValue;\" />"+
				         "<a href=\"javascript:\" onclick=\"remove_upload(this,"+in_mids[i]+",'"+url+"','"+filenames[i]+"')\">移除</a>"+
				       "</li>";
		 });
	$('#'+returnid).append(str);
}
/**
功能：添加附件控件
参数：(控件id)
*/
function downfiles_add(returnid) {
	var ids = parseInt(Math.random() * 10000);
	var str = "<li id='downfiles"+ids+"'>"+
		          "<input type='text' name='"+returnid+"_fileurl[]' value='' style='width:300px;' class='input-text' />"+
		          "<input type='text' name='"+returnid+"_filename[]' value='附件说明' style='width:380px;' class='input-text' />"+
		          "<a href=\"javascript:\" onclick=\"remove_upload(this,-1)\">移除</a>"+
	          "</li>";
	$('#'+returnid).append(str);
}

function remove_upload(obj,delId,src,filename){
  $(obj).parent().remove();
  if(delId!=-1){
    $.get(SYS_ENTRY+"?c=attachment&a=swfupload_json&aid="+delId+"&src="+encodeURIComponent(src)+"&filename="+encodeURIComponent(filename));
  }
}

/*******【title】标题控件******/
/**
功能：设置标题是否为加粗
参数：(fd 字段)
*/
function title_switch_bold(fd){
	var isBold=$("#"+fd).data("isbold");
	if(isBold){
	  title_set_style(fd,'font-weight','');
	}else{
	  title_set_style(fd,'font-weight','bold');
	}
}
/**
功能：设置标题样式
参数：(fd 字段,ky 设置的类型,vl 设置的值)
*/
function title_set_style(fd,ky,vl){
  var f=$("#"+fd).data("font-weight"),
      c=$("#"+fd).data("color"),
      fc={},fcArr=[],cval=$("#"+fd).val();
  if(f){fc['font-weight']=f;}
  if(c){fc['color']=c;}
  if(ky){fc[ky]=vl;}

  if(typeof(fc['font-weight'])!="undefined"){
    if(fc['font-weight']){
    	fcArr.push("font-weight:"+fc['font-weight']);
    	$("#"+fd).data("isbold",1);
    }else{
      $("#"+fd).data("isbold",0);
    }
    $("#"+fd).css("font-weight",fc['font-weight']);
  }
  if(typeof(fc['color'])!="undefined"){
    if(fc['color']){
    	fcArr.push("color:"+fc['color']);
    }
    $("#"+fd).css("color",fc['color']);
  }
  if(fcArr.length&&cval){
    $("#"+fd+"_val").val("<span style=\"display:inline;"+fcArr.join(";")+"\">"+cval+"</span>");
  }else{
    $("#"+fd+"_val").val(cval);
  }
  $("#"+fd).data("font-weight",fc['font-weight']);
  $("#"+fd).data("color",fc['color']);
}
/**
功能：检查标题是否重复
参数：(fd 字段,url 服务端地址)
*/
function title_check_repeat(obj,fd,catid,cid){
  $.post(act_url("content","manage","type=check_title&catid="+catid+"&field="+fd+"&id="+cid),
        {title:$("#"+fd).val()},
        function(data){
          if(data=="1") {
            $(obj).val("标题重复");
          } else if(data=="0") {
            $(obj).val("标题不重复");
          }
          setTimeout(function(){
          	$(obj).val("检查重复");
          },1000);
        },false);
}


/************常用工具控件************/
//判断是否是图片
function IsImg(url){
  var sTemp;
  var b=false;
  var opt="jpg|gif|png|bmp|jpeg";
  var s=opt.toUpperCase().split("|");
  for (var i=0;i<s.length ;i++ ){
    sTemp=url.substr(url.length-s[i].length-1);
    sTemp=sTemp.toUpperCase();
    s[i]="."+s[i];
    if (s[i]==sTemp){
      b=true;
      break;
    }
  }
  return b;
}
//判断是否是flash
function IsSwf(url){
  var sTemp;
  var b=false;
  var opt="swf";
  var s=opt.toUpperCase().split("|");
  for (var i=0;i<s.length ;i++ ){
    sTemp=url.substr(url.length-s[i].length-1);
    sTemp=sTemp.toUpperCase();
    s[i]="."+s[i];
    if (s[i]==sTemp){
      b=true;
      break;
    }
  }
  return b;
}

function classid_select(obj,cid,isedit,fd){
	var w=isedit?750:600,h=isedit?400:350;
	if(isedit){
		top.win.diag(
		'type.init',
		'cid='+cid+'&isedit='+isedit,
		{'tl':'分类管理','w':w,'h':h,'ok':'确定',sclose:1},
		function(){
			var classid=art.dialog.data("selectCid");
			if(typeof(classid)!="undefined"){
				$(obj).prevAll("input[name='info["+fd+"]["+cid+"]']").val(classid);
				$("span",obj).load(act_url('type','init','cid='+cid+'&classid='+classid));
				art.dialog.removeData("selectCid");
			}
    });
	}else{
		art.dialog.open(act_url('type','init','cid='+cid+'&isedit='+isedit),{
				title:'请选择分类',
				fixed:true,
				lock:true,
				opacity:0.05,
				width:w,
				height:h,
				id:'select_category',
				ok:function(){
					var classid=art.dialog.data("selectCid");
					if(typeof(classid)!="undefined"){
						$(obj).prevAll("input[name='info["+fd+"]["+cid+"]']").val(classid);
						$("span",obj).load(act_url('type','init','cid='+cid+'&classid='+classid));
						art.dialog.removeData("selectCid");
					}
				  return true;
				}
			});
	}
}

function catids_select(catid,field,tl){
	var deft=$("#"+field).val();
	art.dialog.open(act_url("content","push",'catid='+catid+'&catids='+deft),{
		width:650,
		height:300,
		title:tl,
		fixed:true,
		lock:true,
		opacity:0.05,
		ok:function(){
			var cw=this.iframe.contentWindow,sid=[];
			$("input[name='catids[]']:checked",cw.document).each(function(){
				sid.push($(this).val());
			});
			$("#"+field).val(sid.join(','));
			$("#"+field+"_selected").html(sid.length ? '已选择'+sid.length+'个栏目' : '选择需要发布的栏目');
		}
	});
}

function typeid_select(catid,field,typeid){
	var pids=window['types_'+field+'_Obj']['pids'],
	    alltypes=window['types_'+field+'_Obj']['all'],
	    selectids=(pids=="" ? []: pids.split(",")),
	    checkids=(pids=="" ? []: pids.split(",")),
	    html="";
	selectids.unshift("0");
	checkids.push(typeid);

  for(var i=0;i< selectids.length;i++){
  	html+='<select size="10" onchange="typeid_change(this,\''+field+'\')" style="width:240px" id="select_'+field+'_'+i+'">';
  	for(var k in alltypes){
  		if(alltypes[k]['pid']==selectids[i]){
  			html+='<option '+(checkids[i]==k?'selected="selected"':'')+' value="'+k+'">'+alltypes[k]['name']+'</option>';
  		}
  	}
  	html+='</select>';
  }
  $("#container_"+field).append(html);
}

function typeid_change(obj,field){
	var cdx=$("#container_"+field+" select").index(obj),
	    cid=$(obj).val(),
	    alltypes=window['types_'+field+'_Obj']['all'],
	    html="",subnum=0;

  	html+='<select size="10" onchange="typeid_change(this,\''+field+'\')" style="width:240px" id="select_'+field+'_'+(cdx+1)+'">';
  	for(var k in alltypes){
  		if(alltypes[k]['pid']==cid){
  			subnum++;
  			html+='<option '+' value="'+k+'">'+alltypes[k]['name']+'</option>';
  		}
  	}
  	html+='</select>';
	  $("#container_"+field+" select:gt("+cdx+")").remove();
	  if(subnum){
	    $("#container_"+field).append(html);
	  }
	  $("#"+field).val(cid);
}


///////////////////////上传控件使用方法/////////////////////

//相册上传与取消
function album_cancel(obj,id,source,isop){
	var src = $(obj).children("img").attr("path"),act='';
	var filename = $(obj).attr('title');
	if($(obj).hasClass('on')){
		$(obj).removeClass("on");
		var imgstr = $("#att-path").html();
		var length = $("a[class='on']").children("img").length;
		var strs = filenames = mids = '';
		for(var i=0;i<length;i++){
			strs += '|'+$("a[class='on']").children("img").eq(i).attr('path');
			filenames += '|'+$("a[class='on']").children("img").eq(i).attr('title');
			mids += '|'+$("a[class='on']").children("img").eq(i).attr('imgid');
		}
		$('#att-path').html(strs);
		$('#att-name').html(filenames);
		$('#att-id').html(mids);
		act=isop?'_del':'';
	} else {
		var num = $('#att-path').html().split('|').length;
		if(file_upload_limit > 1){
			if(num > file_upload_limit) {alert('不能选择超过'+file_upload_limit+'个附件'); return false;}
			$(obj).addClass("on");
			$('#att-path').append('|'+src);
			$('#att-name').append('|'+filename);
			$('#att-id').append('|'+id);					
		}else{
			//alert($(obj).siblings().length);
			$(obj).addClass("on");
			$('#att-path').html('|'+src);
			$('#att-name').html('|'+filename);
			$('#att-id').html('|'+id);
		}
		act=isop?'':'_del';
	}
	$.get(SYS_ENTRY+'?'+ADMIN_INI+'&c=attachment&a=swfupload_json'+act+'&aid='+id+'&src='+source+'&filename='+filename);
}

//文件浏览上传与取消
function file_cancel(obj){
	var src = $(obj).children("a").attr("rel");
	var filename = $(obj).children("a").attr("title");
	if($(obj).hasClass('on')){
		$(obj).removeClass("on");
		var imgstr = $("#att-path").html();
		var length = $("a[class='on']").children("a").length;
		var strs = filenames  = mids = '';
		for(var i=0;i<length;i++){
			strs += '|'+$("a[class='on']").children("a").eq(i).attr('rel');
			filenames += '|'+$("a[class='on']").children("a").eq(i).attr('title');
			mids += '|-1';
		}
		$('#att-path').html(strs);
		$('#att-name').html(filenames);
		$('#att-id').html(mids);
	} else {
		var num = $('#att-path').html().split('|').length;
		$(obj).addClass("on");
		$('#att-path').append('|'+src);
		$('#att-name').append('|'+filename);
		$('#att-id').append('|-1');
	}
}

//图片浏览
function init_imgPreview(){
		var obj=$("#imgPreview a[rel]");
		if(obj.length>0) {
			$('#imgPreview a[rel]').imgPreview({
				srcAttr: 'rel',
			  imgCSS: { width: 200 }
			});
		}
}

//ajax分页
function ajax_page(url,act){
	 $("#loading_process").show();
	 if(act=="dirlist"){
	   $("#"+act+"_container").load(url,function(){
	     init_imgPreview();
	     $("#loading_process").hide();
	   });
	 }else{
	   $("#"+act+"_container").load(url,function(){
	     $("#loading_process").hide();
	   });
	 }
}