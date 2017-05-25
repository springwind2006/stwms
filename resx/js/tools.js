/*修正浏览器差异*/
if(document.implementation.hasFeature("XPath","3.0")){
   XMLDocument.prototype.selectNodes=function(cXPathString,xNode){
      if(!xNode){xNode=this;}
      var oNSResolver=this.createNSResolver(this.documentElement);
      var aItems=this.evaluate(cXPathString, xNode, oNSResolver,XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
      var aResult=[];
      for(var i=0;i< aItems.snapshotLength;i++){
         aResult[i]=aItems.snapshotItem(i);
      }
      return aResult;
   }
   Element.prototype.selectNodes=function(cXPathString){
      if(this.ownerDocument.selectNodes){
         return this.ownerDocument.selectNodes(cXPathString,this);
      }else{throw "For XML Elements Only";}
   }
}

function swfVersion(){
	 var f="-", n=navigator;
	 if (n.plugins && n.plugins.length){
	    for(var ii=0; ii < n.plugins.length; ii++){
	        if(n.plugins[ii].name.indexOf('Shockwave Flash') != -1) {
	           f=n.plugins[ii].description.split('Shockwave Flash ')[1];
	           break;
	        }
	    }
		}else if(window.ActiveXObject){
	     for(var ii=12; ii >= 2; ii--){
	         try{
	           var fl=eval("new ActiveXObject('ShockwaveFlash.ShockwaveFlash." + ii + "');");
	           if(fl){
	               f=ii + '.0';
	              break;
	           }
	         }catch (e){}
	     }
		}
		return f;
}

function sumMbChar(str){/*按双字节统计非汉字，两个字母或数字算一个*/
	if(!str){return 0;};
  var re=new RegExp("^[\\u4e00-\\u9fa5]$"),sNum=0,dNum=0;
  for(var ni=0;ni< str.length;ni++){
	  	if(!re.test(str.substr(ni,1))){
	      sNum++;
	  	}else{
	  	  dNum++;
	  	}
  }
  return dNum+(Math.ceil(sNum/2));
}

function fillNum(nStr,len){ /*给数字加入前导0,如5->005;*/
	 var len=(len==undefined ? 3 : len),nStr=nStr.toString(),sNum=nStr.length;
   if(sNum >= len){return nStr;}
   for(var fi=0;fi< len-sNum;fi++){
     nStr="0"+nStr;
   }
   return nStr;
}

function getSelection(){
	  return (window.getSelection ? window.getSelection():(
				       document.getSelection ? document.getSelection():(
				         document.selection ? document.selection.createRange().text:""
				       )
	          ));
}

/*
功能：动态添加内联样式
参数：([操作对象,]样式字符串)
*/
function addSheet() {
    var doc, cssCode;
    if (arguments.length == 1) {
        doc = document;
        cssCode = arguments[0]
    } else if (arguments.length == 2) {
        doc = arguments[0];
        cssCode = arguments[1];
    } else {
        alert("addSheet函数最多接受两个参数!");
    }
    if (! +"\v1") {//增加自动转换透明度功能，用户只需输入W3C的透明样式，它会自动转换成IE的透明滤镜  
        var t = cssCode.match(/opacity:(\d?\.\d+);/);
        if (t != null) {
            cssCode = cssCode.replace(t[0], "filter:alpha(opacity=" + parseFloat(t[1]) * 100 + ")")
        }
    }
    cssCode = cssCode + "\n"; //增加末尾的换行符，方便在firebug下的查看。  
    var headElement = doc.getElementsByTagName("head")[0];
    var styleElements = headElement.getElementsByTagName("style");
    if (styleElements.length == 0) {//如果不存在style元素则创建  
        if (doc.createStyleSheet) {    //ie  
            doc.createStyleSheet();
        } else {
            var tempStyleElement = doc.createElement('style'); //w3c  
            tempStyleElement.setAttribute("type", "text/css");
            headElement.appendChild(tempStyleElement);
        }
    }
    var styleElement = styleElements[0];
    var media = styleElement.getAttribute("media");
    if (media != null && !/screen/.test(media.toLowerCase())) {
        styleElement.setAttribute("media", "screen");
    }
    if (styleElement.styleSheet) {    //ie  
        styleElement.styleSheet.cssText += cssCode;
    } else if (doc.getBoxObjectFor) {
        styleElement.innerHTML += cssCode; //火狐支持直接innerHTML添加样式表字串  
    } else {
        styleElement.appendChild(doc.createTextNode(cssCode))
    }
}


/*加入收藏*/
function addFavorite(site,wname){
	if(document.all){
	  window.external.addFavorite(site,wname);
	}else if(window.sidebar){
	  window.sidebar.addPanel(wname,site,"");
	}
}

/*设为首页*/
function setHomepage(site){
	if (document.all){
		document.body.style.behavior='url(#default#homepage)';
		document.body.setHomePage(site);
	}else if(window.sidebar){
		if(window.netscape){
			try{
			  netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
			}catch(e){
			  alert("该操作被浏览器拒绝，假如想启用该功能，请在地址栏内输入 about:config,然后将项 signed.applets.codebase_principal_support 值该为true");
			}
		}
		var prefs=Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
		prefs.setCharPref('browser.startup.homepage',site);
	}
}

/*
用法示例:
var glideObj=new myGlide();
glideObj.sRoll(['#reco_box1'],{'pre':"#recoUpBt",'next':"#recoNextBt","handle":null},true,1,20,'left');
参数说明:
start(auto,oBoxs,oHandle,second,fstep,direc)
auto:是否自动播放
oBoxs:外部容器
oHandle:控制容器
second:动画完成时间(秒)
fstep:动画的速度,即是完成动画需要的步数
direc:自动播放的方
*/
function myGlide(){
	function ID(cid){
	   return document.getElementById(filter(cid));
	}
	function swing(p){/*加速效果函数*/
	   return( -Math.cos( p*Math.PI ) / 2 ) + 0.5;
	}
	function filter(str){
	  return str.replace(/[^\w_]+/gi,"");
	}
	this.sRoll=function(oBoxs,oHandle,settings,callBack){
	  var auto=typeof(settings['auto'])!='undefined'?settings['auto']:false,
	      second=typeof(settings['second'])!='undefined'?settings['second']:1,
	      fstep=typeof(settings['fstep'])!='undefined'?settings['fstep']:20,
	      direc=typeof(settings['direc'])!='undefined'?settings['direc']:'left',
	      fn=typeof(callBack)!='function'?function(){}:callBack,
	      oWidth=0,iWidth=0,sWidth=0,ratios=[],num=0,rNum=0,speed=(second/fstep)*1000,
	      step=fstep,timerId,autotimerId,alNum,
	      toLeft,toRight,run;

    oWidth=$(oBoxs[0]).width();
    iWidth=$(oBoxs[0]+" > :first-child").width();
    alNum=$(oBoxs[0]+" > :first-child").children().length;
    sWidth=iWidth/alNum;

    if(oWidth >= iWidth){
      return false;
    }

    for(var i=0;i< oBoxs.length;i++){
       $(oBoxs[i]+" > :first-child").width($(oBoxs[i]+" > :first-child").width()*2);
	     $(oBoxs[i]+" > :first-child").append($(oBoxs[i]+" > :first-child").html());
	     ratios[i]=$(oBoxs[i]).width()/$(oBoxs[0]).width();
    }
    iWidth*=2;

	  run=function(s,bm){
		  return function(){
				if(s==1){/*向左滑动*/
					num++;
					if(num >step){
					  num=0;
					  clearInterval(timerId);
					  rNum+=bm;
					  if(rNum >=(iWidth/sWidth/2)){/*当滑动等于半宽时设置为0*/
							for(var i=0;i< oBoxs.length;i++){
							    ID(oBoxs[i]).scrollLeft=0;
							}
							rNum=0;
						}
					  if(oHandle['handle']){
					    try{
					      $(oHandle['handle']+" a").attr("class",filter(oHandle['handle'])+"_out");
					      $(oHandle['handle']+" a:eq("+rNum+")").attr("class",filter(oHandle['handle'])+"_over");
					    }catch(e){}
					  }
					  (fn)(rNum);
					}else{
					  for(var i=0;i< oBoxs.length;i++){
					     var rw=(swing(num/step)*sWidth+rNum*sWidth)*ratios[i]*bm;
					    ID(oBoxs[i]).scrollLeft=rw;
					  }
					}
				}else{/*向右滑动*/
				  if(!rNum&&!num){/*当为0时设置半宽*/
				     for(var i=0;i< oBoxs.length;i++){
				       ID(oBoxs[i]).scrollLeft=(iWidth/2)*ratios[i];
				     }
				     rNum=iWidth/sWidth/2;
				  }
				  num++;
				  if(num >step){
				    num=0;
				    clearInterval(timerId);
				    rNum-=bm;
				    if(oHandle['handle']){
				      $(oHandle['handle']+" a").attr("class",filter(oHandle['handle'])+"_out");
				      $(oHandle['handle']+" a:eq("+rNum+")").attr("class",filter(oHandle['handle'])+"_over");
				    }
				    (fn)(rNum);
				  }else{
				    for(var i=0;i< oBoxs.length;i++){
				      var rw=(rNum*sWidth-swing(num/step)*sWidth*bm)*ratios[i];
				      ID(oBoxs[i]).scrollLeft=rw;
				    }
				  }
				}
		  }
	  }

		toLeft=function(bm){
		  clearInterval(timerId);
		  timerId=setInterval(run(1,(typeof(bm)=="number"&&bm >0?bm:1)),speed);
		}
		toRight=function(bm){
		  clearInterval(timerId);
		  timerId=setInterval(run(-1,(typeof(bm)=="number"&&bm >0?bm:1)),speed);
		}

	  if(auto){
	    autotimerId=setInterval((direc=='left'?toLeft:toRight),(second+5)*1000);
	  }
	  if(oHandle['pre']){
	    $(oHandle['pre']).click(function(){
	      clearInterval(autotimerId);
	      toLeft();
	    });
	  }
	  if(oHandle['next']){
	    $(oHandle['next']).click(function(){
	      clearInterval(autotimerId);
	      toRight();
	    });
	  }
	  if(oHandle['handle']){
	    $(oHandle['handle']+" a").click(function(){
	      clearInterval(autotimerId);
        var cdx=$(oHandle['handle']+" a").index(this);
        if(cdx < rNum){
          toRight(rNum-cdx);
        }else if(cdx > rNum){
          toLeft(cdx-rNum);
        }
	    });
	  }
	}
}

//操作确认
function confirmurl(url){
	art.dialog.confirm("您确定要执行操作吗？此操作不可恢复！", function(){
    window.location=url;
  });
}

/*字符串处理*/
function strlen_verify(obj, checklen, maxlen) {
	var v = obj.value, charlen = 0, maxlen = !maxlen ? 200 : maxlen, curlen = maxlen, len = strlen(v);
	for(var i = 0; i < v.length; i++) {
		if(v.charCodeAt(i) < 0 || v.charCodeAt(i) > 255) {
			curlen -= 2;
		}
	}
	if(curlen >= len) {
		$('#'+checklen).html(curlen - len);
	} else {
		obj.value = mb_cutstr(v, maxlen, true);
	}
}
function mb_cutstr(str, maxlen, dot) {
	var len = 0;
	var ret = '';
	var dot = !dot ? '...' : '';
	maxlen = maxlen - dot.length;
	for(var i = 0; i < str.length; i++) {
		len += str.charCodeAt(i) < 0 || str.charCodeAt(i) > 255 ? 3 : 1;
		if(len > maxlen) {
			ret += dot;
			break;
		}
		ret += str.substr(i, 1);
	}
	return ret;
}
function strlen(str) {
	return ($.browser.msie && str.indexOf('\n') != -1) ? str.replace(/\r?\n/g, '_').length : str.length;
}

//图片浏览
function view_images(url){
	var isie6=(navigator.appName == "Microsoft Internet Explorer" && navigator.appVersion .split(";")[1].replace(/[ ]/g,"")=="MSIE6.0"),
			vid="image_priview"+(new Date()).getTime(),html,
			urls=$.type(url)=="string"?[url]:url,
			loadImg=SYS_PLUGIN_URL+'artDialog/skins/icons/loading.gif',
			blankBg='url('+STATIC_URL+'common/images/blank.gif) repeat',
			leftBg='url('+STATIC_URL+'common/images/view_left.'+(isie6?'gif':'png')+') center no-repeat',
			rightBg='url('+STATIC_URL+'common/images/view_right.'+(isie6?'gif':'png')+') center no-repeat';

	
	if(typeof(window['loadImageObj'])=="undefined"){
		window['loadImageObj']=new Image();
	}
	
	html='<div id="'+vid+'" style="overflow:hidden">'+
					'<img src="'+loadImg+'" />'+
					'<div style="position:relative;background:'+blankBg+'"></div>'+
					'<div style="position:relative;background:'+blankBg+'"></div>'+
				'<div>';
	
	top.art.dialog({
		id:'image_priview',
		title:'图片查看',
		fixed:true,
		lock:true,
		content:html,
		padding:"0px",
		left:"50%",
		top:"50%",
		close:function(){
			$(window['loadImageObj']).unbind("load");
		}
	});	
	
	$(window['loadImageObj']).bind("load",function(){
		var maxWidth=800,maxHeight=600,w=this.width,h=this.height;
		if(w > maxWidth){
			w=800;
			h=Math.round(w*(this.height/this.width));
		}
		if(h > 600){
			w=Math.round(w*(600/h));
			h=600;
		}		
		top.$("#"+vid).css({width:w,height:h});
		top.$("#"+vid+" img").attr({src:this.src,width:w,height:h});
		top.$("#"+vid+" div").css({width:(w/2),height:h});
		top.$("#"+vid+" div:eq(0)").css({left:0,top:-h});
		top.$("#"+vid+" div:eq(1)").css({left:(w/2),top:-h*2});

		top.art.dialog.list['image_priview'].size(w,h);
		top.art.dialog.list['image_priview'].position("50%","50%");
	});
	
	if(urls.length > 1){
		var cDx=0;
		top.$("#"+vid+" div").css({cursor:"pointer"});
		top.$("#"+vid+" div:eq(0)").hover(function(){
			$(this).css("background",leftBg);
		},function(){
			$(this).css("background",blankBg);
		}).click(function(){
			cDx = (cDx-1)>= 0 ? (cDx-1):(urls.length-1);
			top.$("#"+vid+" img").attr("src",loadImg);
			$(window['loadImageObj']).attr("src",urls[cDx]);
		});
		top.$("#"+vid+" div:eq(1)").hover(function(){
			$(this).css("background",rightBg);
		},function(){
			$(this).css("background",blankBg);
		}).click(function(){
			cDx = (cDx+1)< urls.length ? (cDx+1):0;
			top.$("#"+vid+" img").attr("src",loadImg);
			$(window['loadImageObj']).attr("src",urls[cDx]);
		});	
	}
	$(window['loadImageObj']).attr("src",urls[0]);
}

function sizeformat(size,bits){
	var unit=['B','KB','MB','GB','TB','PB'],
		bits=typeof(bits)=="undefined"? 2 : bits,
		i=Math.floor(Math.log(size) / Math.log(1024)),
		bits=Math.pow(10,bits);
	return Math.round((size / Math.pow(1024, i)) * bits) / bits + unit[i];
}