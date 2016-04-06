$(function(){
	$('#jsddm > li').hover(function(){
		$('#jsddm ul').css('visibility','visible');
	},function(){
		$('#jsddm ul').css('visibility','hidden');
	});	
	$('#leftcolumn').height($(document).height()-69);
	$('#content').width($(document).width()-226-3).height($(document).height()-105-3);	
  $('.mainmenu ul:first').show();

  
  //设置操作界面容器的高度
  setWindowSize();
  $(window).resize(setWindowSize);
    
  //快捷菜单设置
  $("#addToFavor").click(function(){
  	if(top.currentId){  		
  		var menuid=top.currentId;
    	$.ajax({
    		dataType:"json",
    		url:act_url("admin","fastmenu","act=add&menuid="+menuid),
    		success:function(r){
    			var htmls=[];
    			$(r).each(function(dx,fm){
    				htmls[dx]='<a class="fava_c" href="javascript:">'+
    										'<b onclick="doMenu('+fm['id']+',\''+fm['type']+'\',\''+fm['c']+'\',\''+fm['a']+'\',\''+fm['data']+'\')">'+fm['name']+'</b> '+
    										'<span class="fava_m" id="fm_'+fm['id']+'"></span>'+
    									'</a>';
    			});
    			$("#fastMenu").html(htmls.join(''));
    		}
    	});  		
    }                   
    return false;
  });
  $("#fastMenu span").live({
    mouseover:function(){
      $(this).attr("class","fava_m_on");
  	},
  	mouseout:function(){
      $(this).attr("class","fava_m");
  	},
  	click:function(){
      var menuid=this.id.substr(3);
      $.ajax({
    		dataType:"json",
    		url:act_url("admin","fastmenu","act=del&menuid="+menuid),
    		success:function(r){
    			var htmls=[];
    			$(r).each(function(dx,fm){
    				htmls[dx]='<a class="fava_c" href="javascript:">'+
    										'<b onclick="doMenu('+fm['id']+',\''+fm['type']+'\',\''+fm['c']+'\',\''+fm['a']+'\',\''+fm['data']+'\')">'+fm['name']+'</b> '+
    										'<span class="fava_m" id="fm_'+fm['id']+'"></span>'+
    									'</a>';
    			});
    			$("#fastMenu").html(htmls.join(''));
    		}
    	});
      return false;
  	}
  });
  
  //每隔3分钟自动请求服务器，延续会话时间
  setInterval(function(){$.get(SYS_ENTRY+"?"+ADMIN_INI+"&c=api&a=custom");},1000*60*3);    
});

function submenu(mid){
  $(".submenu").slideUp();
  $("#submenu"+mid).slideDown();
}

function switch_act(c,a){
  if(c=="content"&&a=="init"){
  	$("#centerMenu").show().height($(document).height()-105-3);
  	$("#content").width($(document).width()-226-3-180).css("left","406px");
  	top.doCenterFrame.location=act_url(c,"category");
  }else{
  	$("#centerMenu").hide();
  	$("#content").width($(document).width()-226-3).css("left","226px");
  }
}


function setWindowSize(){
  var pageInfo=getPageInfo();
  $("#doMainFrame").css("height",pageInfo['ch']+"px");
  $.get(act_url("api","custom","lsize="+pageInfo['lsize']+"&tsize="+pageInfo['tsize']));
}


function getPageInfo(){
	var res={},
	page_height=40,//分页高度
	list_height=30,//列表高度
	thumb_width=160,//缩略图宽度
	thumb_height=170,//缩略图高度
	preSize;
	res['ch']=$(window).height()-135;
	res['cw']=$("#doMainFrame").width();	
  preSize=Math.floor((res['ch']-80-page_height)/list_height);
	res['lsize']=Math.floor((res['ch']-80-page_height-preSize)/list_height);	
	res['tsize']=Math.floor((res['ch']-40-page_height)/thumb_height)*Math.floor((res['cw']-20)/thumb_width);
	return res;
}

function admin_map(){
	art.dialog.open(
	act_url("admin","map"),{
		lock: true,
		width:800,
		height:300,
		title:'后台地图',
		cancelVal:"关闭",
		cancel:true
	});
}

function lockscreen(){
	var logout=function(){$.ajax({url:act_url("admin","logout"),async:false});}
	art.dialog({
	    lock: true,
	    background: '#000', // 背景色
	    opacity: 0.97,	// 透明度
	    title:'提示',
	    width:350,
	    height:80,
	    padding:"20px 5px 2px 5px",
	    content: '密 码：<input id="lockscreen_pass"  class="input-text" type="password" /><p id="lockscreen_status" style="line-height:30px;color:#444444;">提 示：输入登录密码解锁</p>',
	    ok: function (){
	    	 var isClose=true,_this=this,pass=$.trim($("#lockscreen_pass").val());
	    	 if(pass==""){
	    	 		$("#lockscreen_pass").focus();
	    	 		return false;
	    	 }
	    	 $("#lockscreen_status").html('提 示：密码验证中...');
	    	 $.ajax({
						url:act_url("admin","login"),
						cache:false,
						data:{"password":pass,"dosub":1},
						async:false,
						success: function(r){
							var res=$.trim(r)==1;
							if(res){
								art.dialog.data("allow_close",1);	    	
								$(window).unbind("unload",logout);
								_this.close();								
							}else{
								$("#lockscreen_status").html('提 示：<font color="red">密码错误！</font>');
								$("#lockscreen_pass").val("");
							}
						}
	    	 });
	       return false;
	    },
	    close: function(){
	    	var isClose=art.dialog.data("allow_close")==1;
	    	if(isClose){
	    		art.dialog.removeData("allow_close");
	    	}
	    	return isClose;
	    },
	    init: function(){
	    	$(window).bind("unload",logout);
	    	$("#lockscreen_pass").focus(function(){
	    		$("#lockscreen_status").html('提 示：输入登录密码解锁');
	    	});
	    }
	});
}