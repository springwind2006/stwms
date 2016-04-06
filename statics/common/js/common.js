//处理动作
function deal(sca,dt,s){
	var ca=typeof(sca)=="undefined"?"admin.init":sca,caArr=ca.split(/\./),
	    c=caArr[0],a=typeof(caArr[1])=="undefined"?"init":caArr[1],
	    dt=(typeof(dt)=="string"&&dt!="") ? dt:"",
      s=typeof(s)!="undefined" ? s : (typeof(dt)=="number" ? dt :0);
  if(!s||(s&&window.confirm("您确定要执行此操作吗？")))
  {
  	 top.switch_act(c,a);
  	 top.doMainFrame.location=act_url(c,a,dt);
  }
}
//处理菜单
function doMenu(cid,tp,c,a,dt){
	if(!cid){return false;}
	var a=(a ? a : 'init'),
			url=(tp==2 ? dt : act_url(c,a,dt));
	top.doMainFrame.location=url;
	top.currentId=cid;
	top.switch_act(c,a);
}

//提交表单，用于自主提交
function POST(formId,btId,statusId,preState,fn){
	var isPass=true,
	    fn=typeof(preState)=="function"?preState:(typeof(fn)=="function"?fn:function(r){return r;}),
	    preState=typeof(preState)=="string"?preState:0;

	if(top.win&&top.win.fresh!=-1){return false;}
	try{isPass=$.formValidator.pageIsValid('1');}catch(e){}
	if(!isPass){return false;}

  var url=p.aPara($("#"+formId).attr("action"),btId+"=1");
  $("#"+btId).attr("disabled",true);
  $("#"+statusId).html(preState?preState:"数据处理中...");
  p.POST(url,formId,function(r){
  	if(top.win&&top.win.fresh){top.win.fresh=1;}
    $("#"+btId).attr("disabled",false);
    $("#"+statusId).html((fn)(r));
  });
}

//提交表单，用于弹出对话框
function doPost(formId,fn,fresh){
	if(!$("#"+formId).length){return (fn)("表单不存在！",0);}
	var isPass=true,
	    url=p.aPara($("#"+formId).attr("action"),"dosubmit=1"),
	    callBack=$("#"+formId).attr("callBack");
	callBack=(callBack&&typeof(window[callBack])=="function") ? window[callBack]:false;
	if(top.win.fresh!=-1){
		(fn)("信息未修改！",0);
		return false;
	}
	try{isPass=$.formValidator.pageIsValid('1');}catch(e){}
	if(!isPass){
		(fn)("验证失败，请重新填写！",0);
		return false;
	}

  p.POST(url,formId,function(r){
  	top.win.fresh=typeof(fresh)!="undefined"&&fresh!=-1 ? fresh : 1;
  	if(callBack){(callBack)(r)}
    (fn)(r,1);
  });
}

//生成后台访问地址
function act_url(c,a,para,isOuter){
	var c=c?c:ROUTE_C,a=a?a:ROUTE_A,isOuter=(typeof(para)=="number" ? para : (typeof(isOuter) != "undefined" ? isOuter : 0));
  return p.aPara(ROOT_URL+SYS_ENTRY+"?"+(isOuter || typeof("ADMIN_INI")=="undefined" ? "": ADMIN_INI+"&")+"c="+c+"&a="+a,para);
}
//生成插件访问地址
function plugin_url(c,a,para,isOuter){
	var a=a?a:'index',isOuter=(typeof(para)=="number" ? para : (typeof(isOuter) != "undefined" ? isOuter : 0));
  return p.aPara(ROOT_URL+SYS_ENTRY+"?"+(isOuter || typeof("ADMIN_INI")=="undefined" ? "": ADMIN_INI+"&")+"plugin_c="+c+"&plugin_a="+a,para);
}

window.onerror=function(){
	//alert("信  息: "+arguments[0]+"\r\n文  件: "+arguments[1]+"\r\n错误行: "+arguments[2]);
	return false;
}