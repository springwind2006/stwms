var win={};
win.winId="showContentWin";//对话框的唯一ID
win.fresh=0;//是否刷新，此参数可以由外部设置，从而决定是否刷新窗口
win.forceFresh=0;//是否强制刷新

/*
功能：关闭对话框
参数：(fn 回调函数,ifc 是否关闭对话框)
*/
win.close=function(fn,ifc){
    	 if(typeof(fn)=="function"){fn.call(top.art.dialog.list[win.winId],ifc);}
    	 if(typeof(ifc)=="undefined"||ifc){
    	 	 top.art.dialog.list[win.winId].close();
    	 }    	 
    	 if((win.forceFresh >0||win.fresh >0)&&typeof(doMainFrame)!="undefined"){doMainFrame.location.reload();}
    };

/*
功能：弹出对话框
参数：(ca string 操作类和方法,dt string URL系列,obj object 对话框设置参数,s number 强制刷新父窗口)
说明：此参数具有能够按类型或格式传递
*/
win.diag=function(ca,dt,obj,s){
	win.fresh=0;
  var url,ok="保存",
      wW=$(top.document).outerWidth(true),wH=$(top.document).outerHeight(true),      
      fresh=typeof(s)=="number"?s:(typeof(obj)=="number"?obj:(typeof(dt)=="number"?dt:-1)),/*1:强制刷新父窗口*/
      fn=typeof(s)=="function"?s:(typeof(obj)=="function"?obj:(typeof(dt)=="function"?dt:false)),
      obj=typeof(dt)=="object" ? dt : obj,
      dt=(typeof(dt)=="string"&&dt!=="" ? (dt.indexOf("&")==0?dt:"&"+dt):""),
      cw=typeof(obj)=="object" && typeof(obj['w'])=='number'?obj['w']:0,
      ch=typeof(obj)=="object" && typeof(obj['h'])=='number'?obj['h']-30:0,
      tl=typeof(obj)=="object" && typeof(obj['tl'])=='string'?obj['tl']:'操作窗口',      
      cancel=typeof(obj)=="object" && typeof(obj['cancel'])=='string'?obj['cancel']:'关闭',
      sclose=typeof(obj)=="object" && typeof(obj['sclose'])=='number'?obj['sclose']:0,
      success=typeof(obj)=="object" && typeof(obj['success'])=='function'?obj['success']:false,
      fn=typeof(obj)=="object" && typeof(obj['close'])=='function'?obj['close']:fn;
      
   
  cw=(cw< 200&&cw >0)?200:(cw> wW?wW:cw);
  ch=(ch< 120&&ch >0)?120:(ch> wH?wH:ch);
  
  if(typeof(ca)=="string"&&ca.match(/=/gi)){
    url=ca+dt;
  }else{
    var ca=typeof(ca)=="undefined"?"admin.init":ca,caArr=ca.split(/\./),
	      c=caArr[0],a=typeof(caArr[1])=="undefined"?"init":caArr[1];
	  url=SYS_ENTRY+'?'+ADMIN_INI+"&c="+c+"&a="+a+dt;	  
  }
  ok=typeof(obj)=="object" && typeof(obj['ok'])=='string' ? obj['ok'] : (a=="add"?"添加":ok);
  
  win.forceFresh=fresh;
  top.art.dialog.open(
    url, {
	  	id:win.winId,title:tl,fixed:true,lock:true,
	  	width:(cw >0 ? cw : "auto"),height:(ch >0 ? ch : "auto"), 	
	  	button: [
				        {
				            name: ok,
				            callback: function () {
				            	  var _this = this,
				            	  		iframe = _this.iframe.contentWindow,
				            	      statusId = _this.config.statusId;				            	  
				            	  if(iframe.doPost){
				            	  	$("#"+statusId).html("数据提交中......请稍候!");
				            	  	iframe.doPost("myform",function(r,isServer){
				            	  		if(typeof(success)=="function"&&isServer){
				            	  			r=success.call(_this,r);
				            	  		}
				            	  	  $("#"+statusId).html(r);
				            	  	},fresh);  
			            	  	}
				                return !!sclose;
				            },
				            focus: true,
				            disabled: true
				        },
				        {
				            name: cancel,
				            callback: true
				        }
			        ],
	  	close:function(){
	  		win.close(fn,0);	  	  
	  	},
	  	init:function(){
	  		var bandFn=function(){win.fresh=-1;};
	  		$("input,select,textarea",this.iframe.contentWindow.document).unbind("change",bandFn).bind("change",bandFn);
	      this.button({name: ok,disabled: false});	    
	      this.config.statusId=this.config.id+"_btStatus";
	      $(this.DOM.buttons[0]).prepend('<span style="color:red;font-size:12px;margin-right:15px;line-height:18px;" id="'+this.config.statusId+'"></span>'); 
			}
    }
  );
}
win.state=function(s){
 $("#"+win.winId+"_btStatus").html(s);
}

win.open=function(ca,dt,obj,s){
	win.fresh=0;
  var url,fresh=typeof(s)=="number"?s:(typeof(obj)=="number"?obj:(typeof(dt)=="number"?dt:0)),/*1:强制刷新父窗口*/
      fn=typeof(s)=="function"?s:(typeof(obj)=="function"?obj:(typeof(dt)=="function"?dt:function(){})),
      obj=typeof(dt)=="object" ? dt : obj,
      cw=typeof(obj)=="object" && typeof(obj['w'])=='number'?obj['w']:0,
      ch=typeof(obj)=="object" && typeof(obj['h'])=='number'?obj['h']-30:0,
      tl=typeof(obj)=="object" && typeof(obj['tl'])=='string'?obj['tl']:'操作窗口',

      /*对话框样式定义*/
      dt=(typeof(dt)=="string"&&dt!=="" ? (dt.indexOf("&")==0?dt:"&"+dt):""),
      wW=$(document).outerWidth(true),
      wH=$(document).outerHeight(true);
  
  cw=(cw< 150&&cw >0)?150:(cw> wW?wW:cw);
  ch=(ch< 200&&ch >0)?200:(ch> wH?wH:ch);
  if(typeof(ca)=="string"&&ca.match(/=/gi)){
    url=ca+dt;
  }else{
    var ca=typeof(ca)=="undefined"?"admin.init":ca,caArr=ca.split(/\./),
	      c=caArr[0],a=typeof(caArr[1])=="undefined"?"init":caArr[1];
	  url=SYS_ENTRY+'?'+ADMIN_INI+"&c="+c+"&a="+a+dt;
  }
  
  win.forceFresh=fresh;   
  top.art.dialog.open(
    url, {
	  	id:win.winId,title:tl,fixed:true,lock:true,
	  	width:(cw >0 ? cw : "auto"),height:(ch >0 ? ch : "auto"),	  	
	  	close:function(){
	  		win.close(fn,0);	  	  
	  	}
    }
  );
};