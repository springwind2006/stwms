(function(A,w){
	/*
	 此工具库兼容IE6.0以上浏览器及火狐浏览器
	 所有以"$"开头,如"$myid"交给本对象视为ID值对象处理；
	 匿名函数内部变量,t为核心变量,为不污染外部变量，需用var声明
	*/
	var t=function(a){return new t.fn.init(a)},initElem,init,
	myTool="p", /*设置外部调用的命名空间名称,外部设置参数:p*/
	myJq="jq",/*设置JQuery对象的命名空间*/
	jsName="jTools.js", /*当前文件的文件名*/
	tDir="jTools/", /*扩展工具目录,##为本库加载外部库的默认目录,若为外链地址，则从外表加载，否则路径相对于主工具路径##*/
	jQName="jquery.js",/*需要加载的jQuery文件名，需放置在{tDir}/下或从网络加载*/

	DCM=A.document,
	DEM=DCM.documentElement,
	DBD=DCM.body,
	cHref=A.location,
	pHost=cHref.protocol+"//"+cHref.host,   /*协议://域名:端口号*/
	fPath=cHref.pathname,   /*当前文件(非脚本文件)路径*/
	fName=fPath.match(/[^\/]*$/gi)[0],   /*当前文件名(非脚本文件)*/
	extURL="",/*调用API目录，为空则使用{tDir}/api/*/
	dataPath="",
	isJ=false,   /*是否已经继承了jQuery对象*/
	loadJQ=true, /*是否加载jQuery对象,外部设置参数:jq*/
	loadPlugin=false,/*是否加载jQuery插件,外部设置参数:plugin*/
	loadLib=false, /*是否加载外加库，当为true时会加载所有的库文件，位于{tDir}/lib/ ,外部设置参数:lib*/
	libArr=['bugs.js'],   /*放置在"{tDir}/lib/"或网上，需要同步加载的脚本文件*/
	showError=1, /*0:完全屏蔽错误;1:显示错误信息;2:错误交给浏览器处理, 外部设置参数:error*/
	ERRORS={
	  'e1':"没有权限操作外连iframe！",
	  'e2':"无法外链请求！",
	  'e3':"参数输入错误"
	},
	/*[[常用正则表达式集合*/
	http=new RegExp("^https?://","gi"),
	hTag=new RegExp("<(\\w+)\\s?","gi"),
	xmlTag=new RegExp("<.+?>","gi"),
	wTag=new RegExp("(^[\\s\\r\\n\\0\\t\\x0B]*)|([\\s\\r\\n\\0\\t\\x0B]*$)","g"),
	regTag=new RegExp("([\\$\\(\\)\\*\\+\\.\\[\\?\\\\\\^\\{\\|])","gi"),


	/*内部方法，外部可以通过本命名空间访问*/
	gDomain=function(){
	 /*
	  参数形式:gDomain([cURL][,gtp])
	  获取:"协议://域名:端口号"组成的字符串，gtp为test判断是否同域
	 */
	 var oArr=["protocol","host","port","full","test"],
	     cURL=trim(arguments[0]).match(http)?trim(arguments[0]):pHost,
	     gtp=findVl(arguments[0],oArr)?arguments[0]:(findVl(arguments[1],oArr)?arguments[1]:"full"),
	     phREG=new RegExp("^http://[^/]+(:\d+)?","i"),
	     cDomain=cURL.match(phREG)[0];
	     switch(gtp){
	       case "protocol":
	         return (cDomain.match(/^[^:\/]+/i)[0]);
	       break;
	       case "host":
	         return (cDomain.match(/\/[^\/:]+/i)[0].replace(/\//,""));
	       break;
	       case "port":
	         return (cDomain.match(/:[^\/:]+/i)[0].replace(/:/,""));
	       break;
	       case "full":
	         return cDomain;
	       break;
	       case "test":
	         return (cDomain==pHost);
	       break;
	     }
	},
	timeNum=function(){
	  return (new Date()).getTime();
	},
	error=function(tp){
	 switch(tp){
	   case 0:
	     t.showError=showError=tp;
	     A.onerror=function(){return true;};
	   break;
	   case 1:
	     t.showError=showError=tp;
	     A.onerror=function(){
	     	 alert("信  息: "+arguments[0]+"\r\n文  件: "+arguments[1]+"\r\n错误行: "+arguments[2]);
	     	 return true;
	     };
	   break;
	   case 2:
	     t.showError=showError=tp;
	     A.onerror=function(){return false;};
	   break;
	   default:
	     if(t.showError!=0){
	       if(ERRORS[tp]){
	         return alert(ERRORS[tp]);
	       }else{
	         return alert("系统未知错误");
	       }
	     }
	   break;
	 }
	},
	trim=function(str){
	  if(typeof(str)=="undefined"||(!str&&str!==0)){return '';};
	  if(typeof(str)=="number" || typeof(str)=="string"){
	    return str.toString().replace(wTag, "").replace(/　/gi,"");
	  }else{
	    return '';
	  }
	},
	inTrim=function(str){
	  str=trim(str);
	  return str.replace(/[\s\r\n\0\t\x0B]*/gi,"");
	},
	tagTrim=function(str){
		str=trim(str);
		return str.replace(/>[\s\r\n\0\t\x0B]*</gi,"><");
	},
	REG=function(str){ /*转换正则表达式中的特殊字符*/
	  return str.replace(regTag,"\\$1");
	},
	toTxt=function(str){ /*返回字符串的文本格式，过滤html字符,并将实体字符转化为普通文本*/
		if(typeof(str)!="string"){return "";}
		str=str.replace(/<script[^>]*?>.*?<\/script>/gim,"");
		str=str.replace(/<[\/\!]*?[^<>]*?>/gim,"");
		var reg=new RegExp();
		var oReg=["&(quot|#34);","&(amp|#38);","&(nbsp|#160);","&(iexcl|#161);","&(cent|#162);","&(pound|#163);","&(copy|#169);","&#(\\d+);"];
		var rArr=["\"", "&"," ",String.fromCharCode(161),String.fromCharCode(162),String.fromCharCode(163), String.fromCharCode(169),""];
		for(var i=0;i< oReg.length;i++){
		  reg.compile(oReg[i],"gi");
		  str=str.replace(
		       reg,
		       function($0,$i){
		  	     return $i.match(/\d+/gi)?String.fromCharCode($i):rArr[i];
		       }
		    );
		}
	  return deCode(str,"xu");
	},
	findVl=function(vl,obj,tp){ /*适用于字符串，对象，数组*/
		var reky=-1,tpArr=[];
	  if(typeof(obj)=="string"){
	  	 tpArr=obj.match(new RegExp("("+REG(vl)+")","g"));
	  	 if(!tpArr){return reky;}
	  }else{
	 	  for(var k=0;k< obj.length;k++){
	 	      tpArr.push(obj[k]);
	 	  }
	  }
	  for(var ck=0;ck< tpArr.length;ck++){
		  if(tpArr[ck]===vl){reky=ck;}/*注：此处必须是恒等于*/
	  }
	  switch(tp){
	    case "pre":
	      reky=(reky-1 >= 0)? (reky-1):(tpArr.length-1);
	    break;
	    case "next":
	      reky=(reky+1 >= tpArr.length)? 0:(reky+1);
	    break;
	    case "key": /*返回找到的键值,没有找到，返回-1*/
	       return (typeof(obj)=="string" ? (obj.indexOf(tpArr[reky])) : reky);
	    break;
	    default: /*判断是否存在*/
	      return (reky==-1 ? false : true);
	    break;
	  }
	  return (typeof(obj)=="string"?(obj.indexOf(tpArr[reky])):tpArr[reky]);
	},
	setUVL=function(vl,obj,dft){/*vl为数组，每个值依次在obj对象中查找，找到则返回[vl[i],i],没有返回默认值dft*/
  	var first=function(cObj){for(var ci=0;ci< cObj.length;ci++){return cObj[ci]}},
  	    cRst,dft=dft?dft:first(obj);
    if(!isArray(vl)){
    	cRst=findVl(vl,obj);
      if(cRst!=-1&&cRst===true){
        return vl;
      }
    }else{
      for(var i=0;i< vl.length;i++){
      	cRst=findVl(vl[i],obj);
        if(cRst!=-1&&cRst===true){
         return vl[i];
        }
      }
    }
    return dft;
	},
	val=function(tObj,vl){
		/*
		  功能：此函数可以设置或获取对象的值
		  参数：val(tObj[,vl]),tObj可以为ID值或DOM对象，或DOM对象和ID值对象虚拟数组
		  说明：当有vl参数，设置对象序列的值，返回有效设置的个数；
		        当无vl参数，当tObj为数组时，返回的是获取值的数组，tObj为ID值或DOM对象，返回单个值

		*/
	   var tObj=t.$(tObj),ObArr=[],obj,tgName,inTagArr=["input","textarea","select"],reArr=[],tNum=0;
	   if(!tObj){return false;};
	   if(!isArray(tObj)){ObArr.push(tObj)}else{for(var i=0;i< tObj.length;i++){ObArr.push(tObj[i]);}};
	   for(var ti=0;ti< ObArr.length;ti++){
	   	 obj=t.$(ObArr[ti]);
	   	 if(!obj){continue};
		   tgName=obj.tagName.toLowerCase();
		   if(findVl(tgName,inTagArr)){
		     if(typeof(vl)!="undefined"){obj.value=vl;}else{reArr.push(obj.value);}
		     tNum++;
		   }else{
		   	 if(tgName=="iframe"){
		   	 	  try{
			   	 	  if(typeof(vl)!="undefined"){
			   	 	 	  obj.contentWindow.document.body.innerHTML=vl;
			   	 	  }else{
			   	      reArr.push(obj.contentWindow.document.body.innerHTML);
			   	    }
			   	    tNum++;
		   	   }catch(e){/*return error('e1');*/}
		   	 }else{
		   	 	 if(typeof(vl)!="undefined"){obj.innerHTML=vl;}else{reArr.push(obj.innerHTML);}
		       tNum++;
		     }
		   }
		 }
		 return (typeof(vl)!="undefined"?tNum:(isArray(tObj)?reArr:reArr[0]));
	},
	attr=function(obj,na,vl){
		/*
		  功能:设置对象的的属性,vl不提供则获取属性
		  参数->
		  obj:可以为ID数组、ID值、对象数组、对象;
		  na:属性名称,可以为字符串,也可以为对象的键值;
		  vl:属性值；
		  返回值:没有设置vl时,返回所有找到的属性值，设置了vl时，返回处理的所有对象
		*/
  	 if(typeof(obj)=="undefined"||typeof(na)=="undefined"){return false;}
  	 if(typeof(na)=="object"){
  	 	 /*按键值处理对象的属性值*/
  	   for(var k in na){
  	     attr(obj,k,na[k]);
  	   }
  	 }else{
  	   var rArr=[],re=[],cvl=(typeof(vl)=="object"?val(vl):vl),
  	   fx={
  	   	    "for":"htmlFor","class":"className","readonly":"readOnly","maxlength":"maxLength",
	          "colspan":"colSpan","tabindex":"tabIndex","cellspacing":"cellSpacing",
	          "rowspan":"rowSpan","usemap":"useMap","frameborder":"frameBorder"
	        },mNa=typeof(na)!="undefined"?na.toLowerCase():na;

	     if(isArray(obj)){
  	     for(var ei=0;ei< obj.length;ei++){rArr.push(t.$(obj[ei]));}
  	   }else{
  	     rArr.push(t.$(obj));
  	   }
  	   na=fx[mNa]?fx[mNa]:mNa;

       for(var ri=0;ri< rArr.length;ri++){
       	 switch(na){
       	   case "style":
       	     if(typeof(vl)=="undefined"){
		           re.push(rArr[ri].style.cssText);
		         }else{
		         	 rArr[ri].style.cssText=cvl;
		         }
       	   break;
       	   case "value":
       	     if(typeof(vl)=="undefined"){
		           re.push(val(rArr[ri]));
		         }else{
		         	 val(rArr[ri],cvl);
		         }
       	   break;
       	   default:
		       	 if(typeof(vl)=="undefined"){
		           re.push(rArr[ri][na]);
		         }else{
		         	 rArr[ri][na]=cvl;
		         }
       	   break;
       	 }
       }
  	   return typeof(vl)=="undefined"?re:rArr;
	   }
	},
	txt=function(tObj,vl){/*返回对象包含的文本*/
	   var cvl=val(tObj,vl);
	   if(isArray(cvl)){
	     for(var i=0;i< cvl.length;i++){
	       cvl[i]=toTxt(cvl[i]);
	     }
	     return cvl;
	   }
	   return toTxt(cvl);
	},
	toNum=function(obj,tp){
	  if(typeof(obj)=="boolean"){return(obj?1:0);};
	  if(typeof(obj)=="undefined"){return 0;};
	  var str=(typeof(obj)=="object" ? val(obj) : obj).toString().replace(/[^\d\.]/gi,""),tp=(tp ? tp:"float");
		return (tp=="float" ? parseFloat(str) : parseInt(str));
	},
	isNull=function(ep){
		if (typeof(ep)!="undefined" && !ep && ep!=0){
		   return true;
		}else{
		   return false
		}
	},
	isArray=function(arr){
		/*
		此判断依据是拥有序列的数字索引即视为数组对象
		*/
		if(typeof(arr)!="object"){return false};
  	var idx=0;
	  for(var k in arr){
	     if(k!=idx){return false;}
	     idx++;
	  }
	  return true;
	},
	enCode=function(cnt,ctp){
		switch(ctp){
		  case "xu": /*十六进制编码*/
		    cnt=escape(cnt).replace(/%u/gi,"\\u");
	    break;
	    default: /*URL编码*/
	      cnt=encodeURIComponent(cnt);
	    break;
		}
		return cnt;
	},
	deCode=function(cnt,ctp){
		switch(ctp){
		  case "xu": /*十六进制解码*/
			  var reg=/\\u(\w{4})/g;
			  cnt=cnt.replace(reg,
					      function($0,$1,$2){
					        return unescape("%u"+$1);
					      }
					    );
		  break;
		  default: /*URL解码*/
	       cnt=decodeURIComponent(cnt);
		  break;
		}
		return cnt;
	},
	cElem=function(para,tgt){/*参数形式: para:{'tag':'input','attr':{'type':'text','value':''}},tgt:目标*/
	 var tObj=tgt||DCM.body,cObj,ky,Vl,Tag=para['tag'].toLowerCase(),
	     eName=para['attr']['name'] || para['attr']['id'] || "tElem"+t.startTime;
	 try{ /*防止IE下iframe的name属性无效*/
	 	 cObj=(Tag=="iframe"?DCM.createElement("<"+Tag+" name=\""+eName+"\">"):DCM.createElement(Tag));
	 }catch(e){
	 	 cObj=DCM.createElement(Tag);
	 }
	 if(Tag=="iframe"){
 	 	  if(typeof(para['attr']['value'])!="undefined"){
	 	 	  Vl=para['attr']['value'];
				delete para['attr']['value'];
			}
			attr(cObj,para['attr']);
			if(para['attr']['src']&&typeof(Vl)!="undefined"&&gDomain(para['attr']['src'],"test")){
			 	 if(t.ifie){
			 	 	t.bind(cObj,"onreadystatechange",function(){
			     	  if(findVl(cObj.readyState,["complete","loaded"])){
				        val(cObj,Vl);
				   	 	 	t.bind(cObj,"onreadystatechange",arguments.callee);
			     	  }
					});
			  }else{
			 	 	t.bind(cObj,"onload",function(){
				        val(cObj,Vl);
				   	 	 	t.bind(cObj,"onload",arguments.callee);
					});
			  }
			}
    }else{
   	  attr(cObj,para['attr']);
    }
    tObj.appendChild(cObj);
	  return cObj;
	},
	aPara=function(url,para){ /*为原RUL添加参数,返回添加后的URL*/
	  if(!trim(para)){return url;};
	  var url=url+(findVl("?",url)!=-1?'&':'?');
	  return url+t.toURL(para);
	},
	swfVersion=function(){
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
	},
  getByClass=function(cls,pNode){/*根据类名获取对象*/
		var elems=(t.$(pNode)||DBD).getElementsByTagName("*"),reArr=[],j,clsName=cls.replace(/[\.]/gi,"").toLowerCase();
		for(var i=0;j=elems[i];i++){
			if(findVl(clsName,trim(j.className).toLowerCase())!=-1){
			  reArr.push(j);
			}
		}
		return reArr;
  },
  getByTag=function(tag,pNode){
    return (t.$(pNode)||DCM).getElementsByTagName(tag);
  },
  pathInfo=function(s){ /*获取路径信息*/
    var rObj={};
    rObj["dir"]=s.replace(/[^\/\\]+$/gi,"");
    rObj["base"]=s.match(/[^\/\\]+$/gi) ? s.match(/[^\/]+$/gi)[0].replace(/\.\w+$/gi,"") : "";
    rObj["ext"]=s.match(/\.\w+$/gi) ? s.match(/\.\w+$/gi)[0].toLowerCase().replace(/\./gi,"") : "";
    return rObj;
  },
  getData=function(fl,mTp){/*获取外部数据变量,fl:数据的文件名,mTp:为回调函数或引用的对象*/
  	if(!gDomain(fl,"test")){ /*异域加载*/
	  	t.loadHTML(fl,function(r){
	  		 var cMimes={};
	  		 try{eval("cMimes="+r.replace(/\r\n/gi,""));}catch(e){};
	       if(typeof(mTp)=="function"){
           (mTp)(cMimes);
	       }else if(typeof(mTp)=="object"){
	       	 for(ky in cMimes){mTp[ky]=cMimes[ky];}
	       }
	  	});
    }else{ /*同域从数据存放位置加载*/
    	var cMimes={};
      try{eval("cMimes="+t.loadHTML(t.dataPath+fl,false).replace(/\r\n/gi,""));}catch(e){};
			if(typeof(mTp)=="function"){
			  (mTp)(cMimes);
			}else if(typeof(mTp)=="object"){
			  for(ky in cMimes){mTp[ky]=cMimes[ky];}
			}
			return cMimes;
    }
    return {};
  }
  /*数组对象处理系列函数*/
  objKeys=function(obj){/*获取对象键组成的数组*/
  	var rArr=[];
  	if(typeof(obj)!="object"){return rArr;}
  	for(var ky in obj){rArr.push(ky);}
  	return rArr;
  },
  objValues=function(obj){/*获取对象值组成的数组*/
  	var rArr=[];
  	if(typeof(obj)!="object"){return rArr;}
  	for(var ky in obj){rArr.push(obj[ky]);}
  	return rArr;
  },
  objFlip=function(obj){/*交换对象的键值*/
  	var rObj={};
  	if(typeof(obj)!="object"){return rObj;}
  	for(var ky in obj){
  		if(trim(obj[ky]).match(/^\w+$/gi)){
  		  rObj[obj[ky]]=ky;
  		}
  	}
  	return rObj;
  },
  objMerge=function(){ /*合并对象，后面的对象覆盖有相同的键名则覆盖前一个对象的键值*/
  	/*参数:obj1,obj2,obj3...*/
  	var rObj={};
  	if(!arguments.length){return rObj;}
  	for(var i=0;i< arguments.length;i++){
      if(typeof(arguments[i])!="object"){continue;}
      for(var ky in arguments[i]){
        rObj[ky]=arguments[i][ky];
      }
  	}
  	return rObj;
  },
  objEach=function(obj,callBack){ /*对数组对象中的每个元素执行回调函数*/
  	/*参数:obj,callBack*/
  	if(typeof(obj)!="object"){return false;}
  	var fn=(typeof(callBack)!="function"?function(){}:callBack);
    for(var ky in obj){(fn)(obj[ky],ky);}
    return true;
  },

	/*内部私有方法，外部不能访问*/
	gPath=function(){ /*获取当前工具文件夹路径*/
	  var reg=new RegExp(REG(arguments[1] ? arguments[1] : jsName)+"(?:\\?.*?)?$","i"),
	  sArr=DCM.getElementsByTagName("script"),reArr;
		for(var sci in sArr){
		  if(sArr[sci].src && (reArr=sArr[sci].src.match(reg))){

		  	//fPath
		  	var cSrc=sArr[sci].src;
		  	if(!cSrc.match(http)){
		  		if(cSrc.indexOf("/")!==0){
		  	    cSrc=pHost+fPath.replace(/[^\/]+$/gi,"")+cSrc;
		  	  }else{
		  	    cSrc=pHost+cSrc;
		  	  }
		  	}
		  	if(arguments[0]=="full"){return cSrc;}
		  	if(arguments[0]=="part"){return reArr[0];}
		    return cSrc.replace(reg,"");
		  }
		}
	},
	Tags=function(t){ /*获取当前页面的所有标签*/
		var taArr=[],cTag,cMt=((DCM.compatMode.toLowerCase()=="css1compat")?DCM.documentElement:DCM.body).innerHTML.match(t ? (new RegExp("<("+REG(t)+")\\s","gi")):hTag);
		for(var i=0;i< cMt.length;i++){
	     cTag=trim(cMt[i].toLowerCase().replace(/^</gi,""));
		   taArr.push(cTag);
		}
		return taArr;
	},
  getEvent=function(){ /*获取事件对象*/
		if(DCM.all){return A.event;}
		var func=getEvent.caller;
		while(func != null){
			var arg0=func.arguments[0];
			if(arg0){
				if((arg0.constructor == Event || arg0.constructor == MouseEvent)||(typeof(arg0)== "object" && arg0.preventDefault && arg0.stopPropagation))
				{
				  return arg0;
				}
			}
			func=func.caller;
		}
		return null;
  },
  type=function(obj){/*获取对象类型*/
		var toString = Object.prototype.toString,class2type={};
    p.objEach("Boolean Number String Function Array Date RegExp Object".split(" "),function(name,i){
			class2type[ "[object " + name + "]" ]=name.toLowerCase();
		});
		return obj==null?String(obj):class2type[toString.call(obj)]||"object";
	};


   /*本空间属性和方法*/
	t.version="1.8";
	t.author="Tianlan";
	t.ifie=navigator.appName=="Microsoft Internet Explorer" ? parseInt(navigator.appVersion.substr(22,3)):false;
	t.loadJQ=loadJQ; /*是否加载jQuery库*/
	t[myJq]={}; /*jQuery对象访问域*/
	t.showError=showError;
	t.showState=0;/*控制处理过程显示状态,0:不显示状态,1:只显示遮罩,2:只显示处理提示,3:显示遮罩和提示*/
	t.jtPath=tDir.match(http)?tDir:(gPath()+tDir);/*当前工具包的路径*/
	t.dataPath=dataPath?dataPath:(t.jtPath+"data/");
	t.extURL=trim(extURL)?extURL:"api/";
	t.startTime=timeNum();
	t.swfVersion=swfVersion();
	t.SWFApi='SWF'+t.startTime;
	t.SERVER={
	  'pBreak':"<b role=\"page\"></b>"
	};
	t.GLOBAL={
	    'pSize':200,
	    'pHost':pHost,
	    'fPath':fPath,
	    'fName':fName
	};
	t.hasLoadJS=[];
	t.GET={};
  t.$=function(a,cDCM){
   	    if(typeof(a)=="object"){return a;};
        var cObj=(cDCM?cDCM:DCM).getElementById(a);
        return isNull(cObj) ? false : cObj;
  };
	t.noConflict=function(tp){ /*调用此函数将p命名空间转移*/
        switch(tp){
          case "$":if(isJ){return jQuery.noConflict();};break;
          case "jQuery":if(isJ){return jQuery.noConflict();};break;
          default:
            A[tp]=this;
            t.bind(window,"load",function(){
              A[myTool]=null;
              myTool=tp;
            });
          break;
        }
	};
  t.fn=t.prototype={
        'init':function(){
    	      		  switch(typeof(arguments[0])){
   	      		    case "string":
   	      		      if(arguments[0].match(/^\$/gi)){
   	      		        this["arguments"]=arguments;
			   	      		  this["topOnly"]=false;/*只对ID值元素进行查询，不包含子元素,查询字段如："$myID"*/
			   	      		  var ELMS=this.query(arguments[0]);
			   	      		  this["length"]=ELMS.length;
			   	      		  for(var ei=0;ei< this["length"];ei++){
			   	      		     this[ei]=ELMS[ei];
			   	      		  }
			                return this;
   	      		      }
   	      		    break;
   	      		    case "function":
   	      		       return t.bind(A,"load",arguments[0]);
   	      		    break;
   	      		  }
   	      		  return A.jQuery(arguments[0],arguments[1]);
        },
        'query':function(){
				  	/*
					  	 备注：《此函数为系统选择器核心函数，功能有待进一步增强》
					  	 参数:this.query(qStr,[fn][,tp])
					  	 功能:依次执行选择器选中对象的回调函数，第一级选择器必须为ID值,同时根据tp参数的不同可以返回匹配的对象；

					  	 用法:如 qStr:"$pid1 .cls1,$pid2 .cls2";
					  	      是对id值为pid1的元素中具有cls1类和id值为pid2的元素中具有cls2类的元素对象执行回调函数;
					  	      如 qStr:"$pid1 input,$pid2 span";
					  	      是对id值为pid1的元素中具有input标签和id值为pid2的元素中具有span标签的元素对象执行回调函数;
					  	*/
					  	var opStr=trim(arguments[0]),pArr=opStr.split(/\s*,\s*/gi),cArr=[],pID,pMt,dx=0,mObj=[],
					  	    fn=typeof(arguments[1])=="function"?arguments[1]:false,tp=setUVL(arguments,["all","first","last"]);
              if(opStr.match(/^\$[\w\$]+$/)){this["topOnly"]=true;}
					  	for(var i=0;i< pArr.length;i++){
					  		 pMt=pArr[i].replace(/^[\$#]/i,"");/*此处有空增强其它的选择类*/
					  		 if(findVl("+",pMt)!=-1){
					  		 }else if(findVl("~",pMt)!=-1){
					  		 }else if(findVl(">",pMt)!=-1){
					  		 }else{
					  		   cArr[0]=pMt.split(/\s+/gi); /*第1层级*/
					  		   pID=cArr[0][0];
					  		   if(cArr[0].length==1){
					  		   	 mObj.push(t.$(pID));
					  		     if(fn){fn.apply(t.$(pID),[i])};
					  		     dx++;
					  		   }else{
					  		   	 cArr[1]=findVl(".",cArr[0][1])!=-1 ? getByClass(cArr[0][1],pID) : getByTag(cArr[0][1],pID);/*第2层级*/
				             for(var i1=0;i1< cArr[1].length;i1++){
				             	 mObj.push(t.$(cArr[1][i1]));
				               if(fn){fn.apply(t.$(cArr[1][i1]),[i1])};
				               dx++;
				             }
					  		   }
					  		 }
					  	}
							switch(tp){
					  	  case "all":return mObj;break;
					  	  case "first":return mObj[0];break;
					  	  case "last":return mObj[mObj.length-1];break;
						  }
        }
	};
	t.fn.init.prototype=t.fn;/*实现简单继承*/

	t.extend=t.fn.extend=function(){
		 for(var ky in arguments[0]){
		     this[ky]=arguments[0][ky];
		 }
	};

	t.fn.extend({
		'get':
		function(cObj){
  	   for(var ei=0;ei< this.length;ei++){
  	   	 if(this[ei]==cObj){
  	   	   return ei;
  	   	 }
  	   }
		},
		'hover':
		function(fn1,fn2)
		{
			 if(typeof(fn1)!='function' || typeof(fn2)!='function'){return false;}
  	   for(var ei=0;ei< this.length;ei++){
  	   	 t.bind(this[ei],"onmouseover",fn1);
  	   	 t.bind(this[ei],"onmouseout",fn2);
  	   }
  	   return this;
		},
		'bind':
		function(act,fn){
  	   for(var ei=0;ei< this.length;ei++){
  	   	 t.bind(this[ei],act,fn);
  	   }
  	   return this;
		},
		'unbind':
		function(act,fn){
  	   for(var ei=0;ei< this.length;ei++){
  	   	 t.unbind(this[ei],act,fn);
  	   }
  	   return this;
		},
	  'val':
	  function(vl){
  	   var rArr=[],re,vl=(typeof(vl)=="object"?val(vl):vl);
  	   for(var ei=0;ei< this.length;ei++){rArr.push(this[ei]);}
  	   re=val(rArr,vl);
  	   return (typeof(vl)=="undefined" && this.topOnly ? re[0] : re);
	  },
	  'txt':
	  function(vl){
  	   var rArr=[],re,vl=(typeof(vl)=="object"?txt(vl):vl);
  	   for(var ei=0;ei< this.length;ei++){rArr.push(this[ei]);}
  	   re=txt(rArr,vl);
  	   return (typeof(vl)=="undefined" && this.topOnly ? re[0] : re);
	  },
	  'attr':
	  function(na,vl){
	  	 var rArr=[];
	  	 for(var ei=0;ei< this.length;ei++){rArr.push(this[ei]);}
	  	 var re=attr(rArr,na,vl);
  	   return (typeof(vl)=="undefined" && this.topOnly ? re[0] : re);
	  },
	  'replace':
	  function(vl){
	  	if(typeof(vl)!="object"){
	  	  vl=document.createTextNode(vl);
	  	}
  	  for(var ei=0;ei< this.length;ei++){
  	  	this[ei].parentNode.replaceNode(vl,this[ei]);
  	  }
  	  return this;
	  },
	  'toNum':
	  function(tp){
	  	   var rArr=[],cNum;
	  	   for(var ei=0;ei< this.length;ei++){
	  	   	  cNum=toNum(this[ei],tp);
	  	      rArr.push(cNum);
	  	      val(this[ei],cNum);
	  	   }
	  	   return (this.topOnly ? rArr[0] : rArr);
	  },
	  'loadHTML':
	  function(){
		   	 /*
		   	  功能:为对象加载HTML代码，可以实现跨域加载；
		   	  参数形式1: t.loadHTML(url[,para][,fn][,mtd]);
		      para参数:URL序列字符串,或ID值，或ID数组，或对象;
		      注意:如果请求页面的编码不是utf-8，一定要加入参数{"encode":"编码名称"},如para:{"encode":"gb2312"}
		      fn参数:回调函数或可执行的字符串;
		   	 */
	  	var url=arguments[0],_this=this,uPa=arguments[0].match(/\?.*/gi)?trim(arguments[0].match(/\?.*/gi)[0]).replace(/^\?/i,""):"",
          para=t.toURL(findVl(typeof(arguments[1]),["string","object"]) ? arguments[1] : ""),
          fn=typeof(arguments[1])=="function" ? arguments[1] : (typeof(arguments[2])=="function"?arguments[2]:function(){}),
          mtd=(arguments[3]?(findVl(arguments[3].toLowerCase(),["get","post"])?arguments[3]:"post"):"post").toUpperCase();
	  	t.loadHTML(url,para,function(r){
	  		for(var ri=0;ri< _this.length;ri++){val(_this[ri],r);}
        (fn)(r);
	  	},mtd);
	  	return this;
	  },
	  'css':
	  function(){
	  	var cssObj={};
      if(typeof(arguments[0])=="object"){
        cssObj=arguments[0];
      }else if(typeof(arguments[0])=="string"){
      	switch(typeof(arguments[1])){
      	  case "undefined":return this[0].style[arguments[0]];break;
      	  case "string":cssObj[arguments[0]]=arguments[1];break;
      	  case "function":break;
      	  default:return false;break;
      	}
      }else{
        return false
      }

	    this.each(function(){
	    	var NNM;
        for(var cNM in cssObj){
		    	NNM="";
			    for(var ai=0,cArr=cNM.toLowerCase().split("-");ai< cArr.length;ai++){
			       NNM+=(ai?(cArr[ai].substr(0,1).toUpperCase()+cArr[ai].substr(1)):cArr[ai]);
			    }
          this.style[NNM]=cssObj[cNM];
        }
	    });
	    return this;
	  },
	  'each':
	  function(){
      var fn=typeof(arguments[0])=="function" ? arguments[0] : false,
          tp=setUVL(arguments,["none","all","first","last"]),mObj=[];
      for(var oi=0;oi< this.length;oi++){
      	if(tp!="none"){mObj.push(this[oi]);}
        if(fn){fn.apply(this[oi],[oi])};
      }
			if(this.length){
		  	switch(tp){
			  	  case "all":return mObj;break;
			  	  case "first":return mObj[0];break;
			  	  case "last":return mObj[mObj.length-1];break;
			  }
		  }
		  return this;
	  }
	});

	/*将函数内部使用的方法外部化，15个*/
	t.extend({
		  'query':t.fn.query,/*将核心查询函数外部化*/
		  'gDomain':gDomain,
		  'timeNum':timeNum,
		  'error':error,
		  'trim':trim,
		  'inTrim':inTrim,
		  'tagTrim':tagTrim,
		  'REG':REG,
      'findVl':findVl,
		  'val':val,
		  'toNum':toNum,
		  'isNull':isNull,
		  'isArray':isArray,
		  'enCode':enCode,
		  'deCode':deCode,
		  'cElem':cElem,
		  'aPara':aPara,
		  'getByClass':getByClass,
		  'getByTag':getByTag,
		  'setUVL':setUVL,
		  'objKeys':objKeys,
		  'objValues':objValues,
		  'objFlip':objFlip,
		  'objMerge':objMerge,
		  'objEach':objEach,
		  'pathInfo':pathInfo,
		  'getData':getData,
		  'toTxt':toTxt,
		  'txt':txt,
		  'attr':attr,
		  'type':type
	});


  /*本工具主要执行方法，26个*/
  t.extend({
      'bind':function(obj,act,fn)
      { /*添加事件绑定*/
				var obj=t.$(obj);
				if(!obj){return false};
				var act=t.ifie ?("on"+act.replace(/^on/gi,"")):act.replace(/^on/gi,""),actRef="_"+act,cFn,re;

				if(!obj[actRef]){/*首次绑定此种类型的事件，构建事件序列和绑定事件*/
					obj[actRef]=[];
					cFn=function(){
						try{
							var evt=getEvent();
							evt["target"]=evt.srcElement||evt.target;
							evt["keyCode"]=evt.which||evt.charCode||evt.keyCode;
							evt["pageX"]=evt.x||evt.pageX;
							evt["pageY"]=evt.y||evt.pageY;
							for(var i in obj[actRef]){
								re=obj[actRef][i].apply(obj,[evt]);
							}
							if(re===false){if(DCM.all){evt.cancelBubble=true;}else{evt.stopPropagation();}}
						}catch(e){}
						/*通过函数返回的值来检查是否阻止冒泡*/
					};
					if(obj.attachEvent){
						obj.attachEvent(act,cFn);
					}else{
						obj.addEventListener(act,cFn);
					}
				}
				for(var i in obj[actRef]){
					if(obj[actRef][i]==fn){return;}
				}
				obj[actRef].push(fn);
				return this;
      },
      'unbind':function(obj,act,fn)
      { /*移除事件绑定,注意：绑定的匿名函数是无法移除的*/
			 	var obj=t.$(obj);
				if(!obj){return false};
				var act=t.ifie ?("on"+act.replace(/^on/gi,"")):act.replace(/^on/gi,"");
				if(obj["_"+act]){
					for(var i in obj["_"+act]){
						if(obj["_"+act][i]==fn){
							obj["_"+act].splice(i,1);
							break;
						}
					}
				}
				return this;
			},
			/*<<:延迟加载系列函数*/
			'run':function()
		  {
		  	/*
		  	 说明:动态执行插件库并执行,加载的{tDir}/lib/目录下的库
		  	 参数形式1:run function(fname[,pArr][,callback])
		  	          fname:函数名字符串；
		  	          pArr:传递给执行函数的参数数组,
		  	          callback:为回调函数系统为回调函数传递执行插件函数的返回值为参数
		  	 使用示例:p.run('md5',['admin'],function(r){alert(r)});
		  	*/
		  	if(!arguments.length){return false};
		  	var fname=arguments[0],
		  	    pArr=typeof(arguments[1])=='object' ? arguments[1] : [],
		  	    fn=(typeof(arguments[1])=='function' ? arguments[1] :   (
		  	        typeof(arguments[2])=='function' ? arguments[2] : '')
		  	       ),pStr="",reVL;
		  	for(var pi=0;pi< pArr.length;pi++){pStr+=",pArr["+pi+"]";};
		  	pStr=pStr.substr(1);
		  	if(A[myTool][fname]){
		  		reVL=eval("A[myTool]."+fname+"("+pStr+")");
		  	  if(fn){(fn)(reVL)};
		  	}else{
          t.loadJS(fname+".js",'lib',function(){
          	var reVL=eval("A[myTool]."+fname+"("+pStr+")");
                if(fn){(fn)(reVL)};
          });
		  	}
		  	return this;
			},
			'loadCSS':function(){
				 /*动态加载CSS文件
				   参数：s为字符串或数组，s标识加载的css文件路径
				 */
			   var s=arguments[0],head,cLink;
			   var checkPath=function(url){/*检查是否已经加载过*/
  					    var CDOM=DCM.getElementsByTagName("link");
				        for(var i=0;i< CDOM.length;i++){
			            if(CDOM[i].getAttribute("href")==url){
			              return true;
			            }
				        }
				        return false;
				     };
				 if(typeof(s)=="string"){
            if(checkPath(s)){return false;}
            head=DCM.getElementsByTagName('head').item(0);
		        cLink=DCM.createElement('link');
		        cLink.href=s.replace(/&$/,'');
            cLink.type="text/css";
            cLink.rel="stylesheet";
            head.appendChild(cLink);
				 }else if(isArray(s)){
           for(var i=0;i< s.length;i++){
              t.loadCSS(s[i]);
           }
				 }
				 return this;
			},
			'loadJS':function()
			{
		   	 /*
		   	  参数形式1: t.loadJS(url[,cd][,fn]);
		      参数形式2: t.loadJS(obj[,cd][,fn]);
		      参数obj形式:A--> ['url1','url2','url3'] 或 B--> [['url1',fun1],['url2',fun2],['url3',fun3]];
		      cd参数:加载的相对与工具目录({tDir}/)路径;
		      动态参数：forceExc决定是否在js文件已加载的情况下强制执行回调函数;
		   	 */
		   	 var s,sp,k,head,script,fn,cfn,lp,forceExc=false;
		   	 var checkPath=function(url,tp){/*检查是否已经加载过*/
				        var ctp=tp||'js',CDOM={};
				        if(ctp=='js'){
				          CDOM=DCM.getElementsByTagName("script");
				          for(var i=0;i< CDOM.length;i++){
				            if(CDOM[i].getAttribute("src")==url){
				              return true;
				            }
				          }
				        }
				        return false;
				     };
		   	 sp=t.jtPath+(typeof(arguments[1])=="string"&&trim(arguments[1]) ? (arguments[1]+'/'):'');
		   	 lp=trim(arguments[1])=="lib" ? 'p='+myTool:'';
		   	 fn=(typeof(arguments[1])=="function" ? arguments[1] : (typeof(arguments[2])=="function" ? arguments[2]:false));



		   	 for(var ai=1;ai< arguments.length;ai++){
		   	    if(typeof(arguments[ai])=='boolean'){
		   	      forceExc=arguments[ai];
		   	    }
		   	 }

		   	 if(typeof(arguments[0])=="string"){
		   	   s=arguments[0].match(http)? arguments[0]:(sp+aPara(arguments[0],lp));
		   	   if(checkPath(s)){if(fn&&forceExc){(fn)();};return false;}
		   	   head=DCM.getElementsByTagName('head').item(0);
		       script=DCM.createElement('script');
		       script.src=s.replace(/&$/,'');
		       t.process("show");
		       if(fn){
		       	 script.onload=script.onreadystatechange=function(){
		       	    if(!this.readyState || findVl(this.readyState,['loaded','complete'])){
		       	    	if(!findVl(this.src,t.hasLoadJS)){
		       	    		t.hasLoadJS.push(this.src);
			       	    	(fn)();
			       	    	t.process("hide");
		       	      }
		       	    }
		         }
		       };
		   	   head.appendChild(script);
		   	 }else if(typeof(arguments[0])=="object"){
			   	 for(k=0;k< arguments[0].length;k++){
			   	 	   if(typeof(arguments[0][k])=="string"){ /*参数形式:A*/
			   	 	     s=arguments[0][k].match(http)? arguments[0][k]:(sp+aPara(arguments[0][k],lp));
			   	 	     cfn=fn;
			   	 	   }else{  /*参数形式:B*/
		             s=arguments[0][k][0].match(http)? arguments[0][k][0]:(sp+aPara(arguments[0][k][0],lp));
		             cfn=(typeof(arguments[0][k][1])=="function" ? arguments[0][k][1] : false);
			   	 	   }
			   	 	   if(checkPath(s)){if(cfn&&forceExc){(cfn)();};continue;};
				   	   head=DCM.getElementsByTagName('head').item(0);
				       script=DCM.createElement('script');
				       script.src=s.replace(/&$/,'');
				       t.process("show");
				       if(cfn){
					       	script.onload=script.onreadystatechange=function(){
						       	 if(!this.readyState || findVl(this.readyState,['loaded','complete'])){
					       	 	    if(!findVl(this.src,t.hasLoadJS)){
						       	 	    t.hasLoadJS.push(this.src);
								       	  (cfn)(this.readyState);
						       	      t.process("hide");
					       	      }
						         }
					        }
				       };
				   	   head.appendChild(script);
			  	 }
			   }
			   return this;
		  },
		  'loadJSON':function()
		  {
		   	 /*
		   	  参数形式1: t.loadJSON(url[,data][,fn],[cache]);
		      data参数:URL序列字符串,或ID值，或ID数组，或对象;
		      fn参数:回调函数;
		      cache参数:指定是否为缓存的文件;
		   	 */
		     var jRUL=arguments[0]+(findVl("?",arguments[0])!=-1?"&":"?"),
		         fn=typeof(arguments[1])=="function" ? arguments[1] :
		            (typeof(arguments[2])=="function"?arguments[2]:function(){}),
		         cache=findVl(arguments[1],["cache","fresh","nocache"]) ? arguments[1] : (
		               findVl(arguments[2],["cache","fresh","nocache"]) ? arguments[2] : (
		               findVl(arguments[3],["cache","fresh","nocache"]) ? arguments[3] : "fresh"
		               )),
		         pObj=findVl(typeof(arguments[1]),["object","string"]) ? arguments[1] : {},
						 funName='temp'+(cache!="cache" ? timeNum():t.cookie(myTool+"_CACHE")),
						 apiJs=jRUL+t.toURL(pObj)+"jsoncallback="+funName;
						 t.process("show");
						 A[funName]=function(json){fn(json);t.process("hide");}
						 t.loadJS(apiJs);
				 return this;
		  },
			'loadXML':function()
			{
		   	 /*
		   	  参数形式1: t.loadXML(fStr[,callback]);
		      fStr参数:XML字符串或XML文件路径;
		      callback:为回调函数,系统为回调函数传递生成的XML对象为参数;
		      注意：发送的是GET请求,默认是异步加载数据
		   	 */
				var parser,txmlDoc,fStr=arguments[0],callback=arguments[1],sync=setUVL(arguments,[true,false]);
				if(fStr.match(xmlTag)){
					/*从XML字符串加载数据*/
						if(A.DOMParser){
						  parser=new DOMParser();
						  txmlDoc=parser.parseFromString(fStr,"text/xml");
						}else{
						  txmlDoc=new ActiveXObject("Microsoft.XMLDOM");
						  txmlDoc.async=false;
						  txmlDoc.loadXML(fStr);
						}
						if(callback){(callback)(txmlDoc.documentElement)};
						return txmlDoc.documentElement;
				}else{
				  /*从XML文件加载数据*/
				    if(!gDomain(fStr,"test")||sync){/*异域异步加载*/
				      if(callback){
				        t.loadHTML(fStr,function(r){
                  (callback)(t.loadXML(tagTrim(r)));
   				      });
				      }
				    }else{ /*同域同步加载*/
					    txmlDoc=t.loadXML(tagTrim(t.loadHTML(fStr,false)));
					    if(callback){(callback)(txmlDoc)};
						  return txmlDoc;
				    }
				}
				return this;
		  },
		  'loadHTML':function()
		  {
		   	 /*
		   	  参数形式1: t.loadHTML(url[,para][,fn][,mtd]);
		      para参数:URL序列字符串,或ID值，或ID数组，或对象;
		      fn参数:回调函数或可执行的字符串
		      sync参数:是否进行异步加载;
		   	 */
			  var url=arguments[0],uPa=arguments[0].match(/\?.*/gi)?trim(arguments[0].match(/\?.*/gi)[0]).replace(/^\?/i,""):"",
            para=t.toURL(findVl(typeof(arguments[1]),["string","object"]) ? arguments[1] : ""),
            fn=typeof(arguments[1])=="function" ? arguments[1] : (typeof(arguments[2])=="function"?arguments[2]:function(){}),
            mtd=(arguments[3]?(findVl(arguments[3].toLowerCase(),["get","post"])?arguments[3]:"post"):"post").toUpperCase(),
            sync=setUVL(arguments,[true,false]);

        if(!gDomain(url,"test")){/*加载站外文件*/
          t.loadJSON(aPara(t.extURL+"get_content.php","url="+t.subData(url)),para,function(r){
            (fn)(deCode(r));
          },"cache");
        }else{
          return t.ajax(url,para,fn,mtd,sync);
        }
        return this;
		  },
			'ajax':function()
			{
		   	 /*
		   	  参数形式1: t.ajax(url[,para][,fn][,mtd]);
		      data参数:URL序列字符串;
		      fn参数:回调函数或可执行的字符串;
		   	 */
			  var ajaxObj,url=arguments[0].replace(/[#\?].*/gi,""),uPa=arguments[0].match(/\?.*/gi)?trim(arguments[0].match(/\?.*/gi)[0]).replace(/^\?/i,""):"",
            para=t.toURL(findVl(typeof(arguments[1]),["string","object"]) ? arguments[1] : "")+uPa,
            fn=typeof(arguments[1])=="function" ? arguments[1] : (typeof(arguments[2])=="function"?arguments[2]:function(){}),
			      mtd=(arguments[3]?(findVl(arguments[3].toLowerCase(),["get","post","xml"])?arguments[3]:"post"):"post").toUpperCase(),
			      sync=setUVL(arguments,[true,false]),
			      resType=(mtd=="XML"?"xml":"html");
        url=trim(url)?url:cHref.href.replace(/[#\?].*/gi,"");/*为空则提交至当前页面*/
        if(!gDomain(url,"test")){return error('e2');};
        para+=(resType=="xml"?"&resType=xml":"");
        mtd=findVl(mtd,["GET","POST"])?mtd:"POST";
			  if(window.XMLHttpRequest){
				   ajaxObj=new XMLHttpRequest();
				}else{
					 try{ajaxObj=new ActiveXObject("Msxml2.XMLHTTP");}catch(e){try{ajaxObj=new ActiveXObject("Microsoft.XMLHTTP");}catch(e){return false};};
				};

			  if(mtd=="GET"){
				    ajaxObj.open(mtd,aPara(url,para),sync);
				    ajaxObj.setRequestHeader("If-Modified-Since","0");
            ajaxObj.setRequestHeader("Cache-Control","no-cache");
				    ajaxObj.send(null);
				}else{
				    ajaxObj.open(mtd,url,sync);
				    ajaxObj.setRequestHeader("Content-Type","application/x-www-form-urlencoded;charset=utf-8"); /*采用utf-8字符集发送数据*/
				    ajaxObj.send(para);
			  };
			  t.process("show");
			  ajaxObj.onreadystatechange=function(){
				   if(ajaxObj.readyState==4&&ajaxObj.status==200){
				   	  if(resType=="xml"&&ajaxObj.responseXML.documentElement){
                (fn)(ajaxObj.responseXML.documentElement);
				   	  }else{
				   	  	(fn)(ajaxObj.responseText);
				   	  }
				   	  t.process("hide");
				   };
			  };
			  return (sync?ajaxObj:ajaxObj.responseText);
			},
			'POST':function()
			{
		   	 /*
		   	  参数形式1: t.post(url[,pObj][,fn]);
		      pObj参数:传递的字符串、数组、常规对象，
		               当为字符串时是form标签或有子节点的标签的ID值，
		               为数组时，是ID序列，
		               为对象时，对象的每个键名和键值即是提交的参数名和值;
		      fn参数:回调函数或可执行的字符串;
		   	 */
				 var url=arguments[0] || "",
				     pObj=(findVl(typeof(arguments[1]),['object','string'])?arguments[1]:{}),
				     fn=(typeof(arguments[1])=="function"?arguments[1]:(typeof(arguments[2])=="function"?arguments[2]:false)),
				     mtd=(typeof(arguments[2])=="string"?arguments[2]:
				          (arguments[3]?(findVl(arguments[3].toLowerCase(),["post","get"])?arguments[3]:"post"):"post")
				         ),
				     tIframe="doFrame"+t.startTime,iObj=t.$(tIframe),startTime=t.timeNum(),fObj,fDiv;
				 var bindFn=function(){ /*提交IFRAME加载事件绑定函数*/
						       if(t.ifie){
							       t.bind(iObj,"onreadystatechange",function(){
							       	  if(findVl(iObj.readyState,["complete","loaded"])){
		                      if(fn){
			                      if(gDomain(url,"test")){
			                        (fn)(val(iObj));/*同一个域则返回处理后内容*/
			                      }else{
			                        (fn)(t.timeNum()-startTime);/*不同域则返回处理耗时*/
			                      }
		                      }
		                      t.process("hide");
		                      t.unbind(iObj,"onreadystatechange",arguments.callee);
		                      if(fDiv){DCM.body.removeChild(fDiv);}
		                    }
							       });
						       }else{
							       t.bind(iObj,"onload",function(){
							       	    if(fn){
					                  if(gDomain(url,"test")){
			                        (fn)(val(iObj));/*同一个域则返回处理后内容*/
			                      }else{
			                        (fn)(t.timeNum()-startTime);/*不同域则返回处理耗时*/
			                      }
		                      }
		                      t.process("hide");
		                      t.unbind(iObj,"onload",arguments.callee);
		                      if(fDiv){DCM.body.removeChild(fDiv);}
							       });
						       }
				 };

				 if(!url){return false};
         if(!iObj || iObj.tagName.toLowerCase()!="iframe"){
           iObj=cElem({"tag":"iframe","attr":{'id':("doFrame"+t.startTime),'name':("doFrame"+t.startTime),'frameBorder':'0','src':'about:blank','style':'display:none;visibility:hidden;height:0px;width:0px;'}});
         }
				 if(typeof(pObj)=="object"){ /*当pObj参数为参考对象或数组，则根据常规对象键值或数组ID值建立提交表单*/
				 	  fDiv=cElem({"tag":"div","attr":{'style':'visibility:hidden;display:none;height:0px;width:0px;'}});
				 	  fObj=cElem({"tag":"form","attr":{'style':'display:inline;'}},fDiv);
            if(isArray(pObj)){
               for(var fi=0;fi< pObj.length;fi++){
                 fObj.appendChild(cElem({"tag":"textarea","attr":{'id':pObj[fi],'name':pObj[fi],'value':val(pObj[fi])}}));
               }
            }else{
               for(var fi in pObj){
                 fObj.appendChild(cElem({"tag":"textarea","attr":{'id':fi,'name':fi,'value':pObj[fi]}}));
               }
            }
				 }else{ /*当pObj参数为字符串，则提交当前ID值pObj内部的控件值*/
				 	  fObj=t.$(pObj);
				    if(!fObj){return false;}
				    if(fObj.tagName.toLowerCase()!="form"&&fObj.parentNode.tagName.toLowerCase()!="form"){
				    	 var inNode=fObj.cloneNode(true),tpSpan;
				    	 tpSpan=cElem({"tag":"span","attr":{'style':'display:inline;'}},fObj);
				    	 fObj.parentNode.replaceChild(tpSpan,fObj);
               fObj=cElem({"tag":"form","attr":{'style':'display:inline;'}},tpSpan);
               fObj.appendChild(inNode);
				    }else if(fObj.parentNode.tagName.toLowerCase()=="form"){
               fObj=fObj.parentNode;
				    }
				 }

	 			 fObj.setAttribute("target",tIframe);
	    	 fObj.setAttribute("action",url);
	    	 fObj.setAttribute("method",mtd);
	    	 t.process("show");
         bindFn(fDiv);
         fObj.submit();
			},
			'GET':function(){
				t.POST(arguments[0],arguments[1],arguments[2],"get");
			},
			/*延迟加载系列函数:>>*/
		  'toURL':function(tObj)
		  {
		   	 /*
		   	  返回的url字符串结尾为&
		   	  参数形式1: t.toURL(tObj[,arg1][,arg2]);
		      tObj参数:tObj为字符串时，已经是URL序列则原样返回，否则是对象的ID值(arg1为self是取本对像(否则取子对象),arg2为keep表示保留数据库安全字符);
		               tObj为常规对象时,键名和键值即是URL参数名和参数值;
		               tObj为数组时,数组值为标签ID，ID值作为URL参数名称，ID值的对象值作为对应的URL参数的值;
		   	 */

		   	 var fd=function(cid){
		   	 	      var cObj=typeof(cid)=="object" ? cid : t.$(cid);
		            return trim(cObj.name ? cObj.name:cObj.id);
		   	     },urlStr="";
		   	 if(typeof(tObj)=="string"){
		   	 	  if(inTrim(tObj).match(/(\w+=.*?)+/gi)){return trim(tObj).replace(/&$/gi,'')+"&";}; /*排除原先已是url序列的字符串*/
		   	 	  var Obj=t.$(tObj),ctlObjs,ctlObj,rdArr,rdVl,cName="";
		   	    if(!Obj){return ""};
		   	    if(arguments[1]=="self"){
		           return fd(tObj)+"="+t.subData(val(tObj),(arguments[2]?arguments[2]:"keep"))+"&";
		   	    }else{
		   	    	 /*获取当前对象子元素的URL字符串*/
		           var ctls=["input","select","textarea","iframe"];
		           for(var ci=0;ci< ctls.length;ci++){
		              ctlObjs=Obj.getElementsByTagName(ctls[ci]);
		              for(var ti=0;ti< ctlObjs.length;ti++){
		                 ctlObj=ctlObjs[ti];
		                 if(ctls[ci]=="input"&&ctlObj.type=="radio"){
		                 	 if(ctlObj.name){
		                 	 	 if(ctlObj.name!=cName){
			                 	 	 cName=ctlObj.name;
			                 	   rdArr=Obj.getElementsByName(ctlObj.name);
			                 	   rdVl=rdArr[0].value;
			                 	   for(var ri=0;ri< rdArr.length;ri++){
			                 	     if(rdArr[ri].checked){
			                          rdVl=rdArr[ri].value;
			                 	     }
			                 	   }
			                 	   urlStr+=fd(ctlObj)+"="+t.subData(val(ctlObj),(arguments[2]?arguments[2]:"keep"))+"&";
		                 	   };
		                   }else{
		                     urlStr+=fd(ctlObj)+"="+t.subData(val(ctlObj),(arguments[2]?arguments[2]:"keep"))+"&";
		                   }
		                 }else if(ctls[ci]=="iframe"){
                       if(trim(ctlObj.getAttribute("role")).match(/^writable/gi)){
                         urlStr+=fd(ctlObj)+"="+t.subData(val(ctlObj),(arguments[2]?arguments[2]:"keep"))+"&";
                       }
		                 }else{
		                   urlStr+=fd(ctlObj)+"="+t.subData(val(ctlObj),(arguments[2]?arguments[2]:"keep"))+"&";
		                 }
		              }
		           }
		   	    }
		   	 }else if(typeof(tObj)=="object"){
		   	 	  if(isArray(tObj)){
			        for(var ky=0;ky< tObj.length;ky++){
			          urlStr+=tObj[ky]+"="+t.subData(val(tObj[ky]),(arguments[1]?arguments[1]:"keep"))+"&";
			        }
		   	 	  }else{
			        for(var oKy in tObj){
			          urlStr+=oKy+"="+t.subData(tObj[oKy],(arguments[1]?arguments[1]:"keep"))+"&";
			        }
		        }
		   	 }
		     return urlStr;
      },
			'getSelection':function()
			{
				  return (A.getSelection ? A.getSelection():(
							       DCM.getSelection ? DCM.getSelection():(
							         DCM.selection ? DCM.selection.createRange().text:""
							       )
				          ));
			},
		  'stripChar':function(str)
		  {
		   	  if(typeof(str)!="number" && typeof(str)!="string"){return str;};
		   		str=trim(str.toString());
					var repArr=[
										   [/'/gi,/,/gi,/"/gi,/;/gi],
										   ["","，","“","；"]
										 ];
				  for(var i=0;i< repArr[0].length;i++){
				    str=str.replace(repArr[0][i],(repArr[1][i]? repArr[1][i]:""));
				  }
				  return str;
		  },
		  'subData':function(str,stp)
		  {  /*stp:为keep保留数据库安全字符*/
		   	 if(typeof(str)!="number" && typeof(str)!="string"){return str;};
		     if(stp!="keep"){str=t.stripChar(str);}
	       return enCode(str);
		  },
		  'sumMbChar':function(str)/*按双字节统计非汉字，两个字母或数字算一个*/
		  {
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
		  },
			'fillNum':function(nStr,len) /*给数字加入前导0,如5->005;*/
		  {
					 var len=(len==undefined ? 3 : len),nStr=nStr.toString(),sNum=nStr.length;
				   if(sNum >= len){return nStr;}
				   for(var fi=0;fi< len-sNum;fi++){
				     nStr="0"+nStr;
				   }
				   return nStr;
			},
			'toObj':function(dom) /*将DOM对象的属性转化为相应键和值的对象*/
			{
					 var aObj={};
					 if(!dom || !dom.attributes){return aObj;};
				   for(var mi=0;mi< dom.attributes.length;mi++){
				     aObj[dom.attributes[mi].nodeName]=dom.attributes[mi].nodeValue;
				   }
				   return aObj;
			},
			/*cookie操作系列函数*/
			'cookie':function(ckNa,ckVl,ops)
			{
				if(!arguments.length){return false};
        if(arguments.length==1){
        	return t.getCookie(ckNa);
        }else if(ckVl!=null&&ckVl!="null"&&ckVl!=""){
          return t.setCookie(ckNa,ckVl,ops);
        }else{
          return t.delCookie(ckNa);
        }
			},
			'setCookie':function(ckNa,ckVl)
			{
				 try{
					var ops=ops||{};
					if(ckVl===null){
	            ckVl='';
	            ops.expires=-1;
	        }
	        var expires='';
	        if(ops.expires&&(typeof ops.expires=='number'||ops.expires.toUTCString)){
	          var date;
	          if(typeof ops.expires == 'number'){
	              date=new Date();
	              date.setTime(date.getTime() +(ops.expires * 24 * 60 * 60 * 1000));
	          }else{
	              date=ops.expires;
	          }
	          expires='; expires=' + date.toUTCString();
	        }
	        var path=ops.path ? '; path=' + ops.path : '',
	            domain=ops.domain?'; domain='+ops.domain:'',
	            secure=ops.secure?'; secure':'';
	        document.cookie=[ckNa, '=', encodeURIComponent(ckVl), expires, path, domain, secure].join('');
				  return ckVl;
				}catch(e){}
			},
			'getCookie':function(ckNa)
			{
				try{
				　var arr=DCM.cookie?DCM.cookie.match(new RegExp("(^| )"+REG(ckNa)+"=([^;]*)(;|$)")):[];
				　if(arr!=null&&arr!="null"&&arr!=""){return decodeURIComponent(arr[2]);};
				  return false;
				}catch(e){}
			},
			'delCookie':function(ckNa)
			{
				 try{
				　var exp=new Date(),cval=t.getCookie(ckNa);
				　exp.setTime(exp.getTime()-10000);
					DCM.cookie=ckNa+"=";
					DCM.cookie=ckNa+"=;path=/;expires="+ exp.toGMTString();
			  }catch(e){}
			},
			'toCenter':function(obj,CWin)
			{
				var L,T,W,H,cW,cH,obj=t.$(obj),iStat=(obj.style&&obj.style.display.toLowerCase()=="none"?"none":"block"),
				Dom=CWin?(CWin.document.documentElement ? CWin.document.documentElement:CWin.document.body):(DEM?DEM:DBD);
				if(!obj){return false};
				try{
					  obj.style.display="block";
				  	L=toNum(Dom.scrollLeft);
				  	T=toNum(Dom.scrollTop);
				  	W=toNum(Dom.clientWidth);
				  	H=toNum(Dom.clientHeight);
				    cW=toNum(obj.offsetWidth);
				    cH=toNum(obj.offsetHeight);
				    obj.style.left=((W-cW)/2+L)+'px';
				    obj.style.top=((H-cH)/2+T)+'px';
				    obj.style.display=iStat;
			 	    if(arguments[1]=='none'){obj.style.display="none";}
			 	}catch(e){}
			},
			'process':function()
			{
				var mID="Mask"+t.startTime,tID="Ps"+t.startTime,cWin=parent?parent:A,
				    cDCM=parent.document?parent.document:DCM,
				    cDBD=cDCM.body,cDEM=cDCM.documentElement,
				    isShow=setUVL(arguments,['show','hide']),
				    opacity=20,
				    sType=typeof(t.showState)=="number" ? setUVL(t.showState,[3,0,1,2,4]):3,
				    inTl=typeof(t.showState)=="string" ? t.showState : "数据处理中......";
				    if(!cDBD){return false;}
				    if(typeof(t.showState)=="object"){
              if(typeof(t.showState["opacity"])!="undefined"){opacity=t.showState["opacity"];}
              if(typeof(t.showState["type"])!="undefined"){sType=t.showState["type"];}
              if(typeof(t.showState["title"])!="undefined"){inTl=t.showState["title"];}
				    }
				var mObj=t.$(mID,cDCM),tObj=t.$(tID,cDCM),
				    center=function(args){
               var objs=args?(isArray(args)?args:[args]):[mObj,tObj];
               for(var oi=0;oi< objs.length;oi++){
               	 if(t.$(objs[oi])){
               	  t.toCenter(t.$(objs[oi]),cWin);
               	 }
               }
				    },
				    state=function(args,tp){
               var objs=args?(isArray(args)?args:[args]):[mObj,tObj],tp=setUVL(tp,['show','hide']);
               for(var oi=0;oi< objs.length;oi++){
               	 if(t.$(objs[oi]) && t.$(objs[oi]).style){
                   t.$(objs[oi]).style.display=(tp=="show"?"":"none");
                 }
               }
               if(tp=="show"){center(args)};
				    };

				if(!mObj || !tObj){/*创建遮罩层并绑定相关事件*/
					 var mCSS="background:#ccc;filter:alpha(opacity="+opacity+");opacity:"+(opacity/100)+";-moz-opacity:"+(opacity/100)+";width:100%;height:100%;",
					     tCSS="background:#fff url(\""+t.jtPath+"images/progress/loading.gif\") 7px center no-repeat;padding:5px 5px 5px 32px;border:1px solid #ddd;font-size:12px;text-align:left;line-height:20px;color:red;width:100px;height:20px;overflow:hidden;white-space:nowrap;",
					     mObj=cElem({"tag":"div","attr":{"id":mID,"style":mCSS+"position:absolute;top:0px;left:0px;z-index:999;display:none;"}},cDBD),
					     tObj=cElem({"tag":"div","attr":{"id":tID,"value":inTl,"style":tCSS+"z-index:1100;position:absolute;top:50%;left:50%;display:none;"}},cDBD);
					 cElem({"tag":"iframe","attr":{"frameborder":"0","style":"background:#000000;filter:alpha(opacity=0);opacity:0;-moz-opacity:0;width:100%;height:100%;"}},mObj);
				   t.bind(tObj,"onclick",function(){t.process("hide")});
					 t.bind(cWin,"onresize",function(){center([mObj,tObj])});
					 t.bind(cWin,"onscroll",function(){center([mObj,tObj])});
				};
				t.val(tObj,inTl);
				if(t.ifie){
				  mObj.filters.alpha.opacity=opacity;
				}else{
				  mObj.style.opacity=(opacity/100);
				}

				switch(sType){ /*为t.showState的值*/
					case 0:state([mObj,tObj],"hide");break;/*0:全部不显示*/
				  case 1:state([mObj],isShow);break;/*1:只显示遮罩*/
				  case 2:state([tObj],isShow);break;/*2:只显示处理提示*/
				  case 3:state([mObj,tObj],isShow);break;/*3:显示遮罩和提示*/
				}
			},
		  'getPos':function(obj)
		  {
	    var sL=toNum(DEM?DEM.scrollLeft:DBD.scrollLeft),
	        sT=toNum(DEM?DEM.scrollTop:DBD.scrollTop),
	        x,y,obj=t.$(obj),xy=new Object();
					oRect=obj.getBoundingClientRect();
					x=toNum(oRect.left)+sL+"px";
					y=toNum(oRect.top)+sT+"px";
					xy['x']=x;
					xy['y']=y;
					return xy;
		  },
		  '$_GET':function(fd,cTg)
		  {
			  	var fullUrl=(typeof(cTg)=="object" ? cTg.location.href : (typeof(cTg)=="string" ? cTg : cHref.href)),
			  	    vtp=(typeof(cTg)=="string" ? cTg : arguments[2]),
			  	    subUrl,urlArr=[],tparr=[];

			  	if(fullUrl.indexOf('?')!=-1){
			  	  subUrl=fullUrl.substr(fullUrl.indexOf('?')+1,fullUrl.length);
			  	  urlArr=subUrl.split('&');
			  	  for(var ky in urlArr){
			  	  	try{
			  	  		if(typeof(urlArr[ky])!="string"){continue};
				  	    tparr=urlArr[ky].split('=');
				  	    if(fd!=undefined){
					  	    if(tparr[0]==fd){
					  	    	if(vtp=="num"){
					  	        return tparr[1].match(/^\d+$/) ? toNum(tparr[1]):tparr[1];
					  	      }else{
					  	        return tparr[1];
					  	      }
					  	    }
				  	    }else if(trim(tparr[0])!=""){
			            t.GET[tparr[0]]=tparr[1];
				  	    }
			  	    }catch(e){}
			  	  }
			  	}
			  	return false;
		  },
		  'encodeURL':function(fullUrl)
		  {
				  var rootUrl,subUrl,urlArr,tparr;
			  	if(fullUrl.indexOf('?')!=-1){
			  		rootUrl=fullUrl.substr(0,fullUrl.indexOf('?')+1);
			  	  subUrl=fullUrl.substr(fullUrl.indexOf('?')+1,fullUrl.length);
			  	  urlArr=subUrl.split('&');
			  	  for(var ky=0;ky< urlArr.length;ky++){
			  	  	try{
			  	  		if(trim(urlArr[ky])!=""){
					  	    tparr=urlArr[ky].split('=');
				          rootUrl+=tparr[0]+"="+enCode(tparr[1])+"&";
			          }
			  	    }catch(e){}
			  	  }
			  	  fullUrl=rootUrl.substr(0,rootUrl.length-1);
			  	}
			  	return fullUrl;
			},
			'serialize':function(obj,deep)
			{
					var vStr='{',dp=toNum(deep)?toNum(deep):0;
					if(findVl(typeof(obj),["string","number","boolean","function"])){return obj.toString();}
					if(typeof(obj)!="object"){return "";}
					for(var ky in obj){
					 if(typeof(obj[ky])=='object' && !dp){
					   try{vStr+=t.serialize(obj[ky],deep)+",";}catch(e){}
					 }else{
					 	 try{vStr+="\""+ky+"\":\""+obj[ky].toString()+"\",";}catch(e){}
					 }
					}
					vStr=vStr.replace(/,$/gi,"");
					vStr+='}';
					return vStr;
			},
			'makeMedia':function(url,bPara,sPara,isNew)
			{
			/*
			  作用:生成flash对象或返回flash的HTML代码
			  参数:makeMedia(url,bPara,sPara)
			       url:flash文件的地址;
			       bPara:为对象,传递给flash的参数数组;
			       sPara:为对象,传递给flash内部的执行代码的参数对象;
			       isNew:是否强制更新flash对象,防止浏览器缓存flash对象
			*/
		    if(!trim(url)){return false};
		    var htmlStr,bPara=bPara?bPara:{},sPara=sPara?sPara:{},mType,
		        url=(isNew?aPara(url,"t="+t.timeNum()):url),
		        sType=(!parseInt(bPara['width'])||!parseInt(bPara['height']))?'hide':'show',
		        ext=pathInfo(url)['ext'];
	      if(ext=="swf"){
	      	 mType=' classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" ';
	         bPara=objMerge({"id":"_TempFlash"+t.timeNum(),"movie":url,"src":url,"width":"1px","height":"1px","flashvars":t.toURL(sPara),"allowscriptaccess":"always","wmode":"transparent","MENU":"false"},bPara);
	      }else if(findVl(ext,["wma","wav","mp3","midi","mid","asf","wmv","mpeg","mpg","avi","aiff","au"])){
	         mType=' classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" ';
	         bPara=objMerge({"id":"_TempMediaPlayer"+t.timeNum(),"url":url,"src":url,"width":"1px","height":"1px"},bPara);
	      }
		    if(!t.$(bPara['id'])){
		    	 /*生成代码*/
		       htmlStr='<object id="'+bPara['id']+'" name="'+bPara['id']+'"'+mType+' style="width:'+parseInt(bPara['width'])+'px;height:'+parseInt(bPara['height'])+'px;">';
	         for(var na in bPara){
	         	 if(findVl(na.toLowerCase(),["id","width","height","src"])){continue;}
	           htmlStr+='<param name="'+na+'" value="'+bPara[na]+'" />';
	         }
	         htmlStr+='<embed id="'+bPara['id']+'" name="'+bPara['id']+'"'+mType+' style="width:'+parseInt(bPara['width'])+'px;height:'+parseInt(bPara['height'])+'px;" ';
	         for(var na in bPara){
	         	 if(findVl(na.toLowerCase(),["id","width","height","movie"])){continue;}
	           htmlStr+=na+'="'+bPara[na]+'" ';
	         }
		       htmlStr+='></embed>';
		       htmlStr+='</object>';
	         /*附件或返回代码*/
	         if(sType=='hide'){
	           val(cElem({'tag':'div','attr':{'style':'position:absolute;left:-10px;height:1px;width:1px;overflow:hidden;'}},DBD),htmlStr);
	           if(!A[bPara['id']]){A[bPara['id']]=t.$(bPara['id']);} /*修改浏览器下动态加载的object访问问题*/
	         }else{
	           return htmlStr;
	         }
			  }
      },
      'mousePos':function()
      {
      	var evt=getEvent(),
      	    Dom=(DEM?DEM:DBD),
            cx=evt.pageX ? evt.pageX:(evt.clientX?(evt.clientX+(Dom.scrollLeft?Dom.scrollLeft:document.body.scrollLeft)):0),
            cy=evt.pageY ? evt.pageY:(evt.clientY?(evt.clientY+(Dom.scrollTop?Dom.scrollTop:document.body.scrollTop)):0);
        return {'x':cx,'y':cy};
      },
      'inArea':function(tid,epos)
      {
      	var obj=t.$(tid),xy=t.getPos(obj),mXY=t.mousePos(),
      	    w=obj.offsetWidth,h=obj.offsetHeight;
            xy['x']=t.toNum(xy['x']);
            xy['y']=t.toNum(xy['y']);
        return (mXY['x']>=xy['x'] && mXY['x']<=xy['x']+w) && (mXY['y']>=xy['y'] && mXY['y']<=xy['y']+h);
      }
  });

  initElem=function(){/*本方法在页面加载完成之后执行*/
  	/*初始化iframe具有属性role='writable'的标签样式*/
  	var injs='document.onkeypress=function(){return keyPress(event);};function keyPress(ev){if(ev.keyCode==13){[return]var range=document.selection.createRange();range.text="\\n";range.moveStart("character", 1); range.collapse(true);range.select();return false;}return true;}',
  	    inCSS,SM,SMCSS,Elems,ky,cAttr,inHTML='<html><head><script type="text/javascript">'+injs+'</script><style type="text/css">body{margin:0px;padding:2px;[SMCSS];cursor:text;'+(t.ifie?'height:100%;width:100%;':'height:96%;width:96%;')+'[style];}p{margin:0px;}</style></head><body></body></html>';
    Elems=DCM.getElementsByTagName("iframe");
    for(ky=0;ky< Elems.length;ky++){
    	cAttr=trim(Elems[ky].getAttribute("role"));
    	if(!cAttr.match(/^writable/gi)){continue;}
    	inCSS=cAttr.replace(/(?:^writable:m)|(?:^writable:s)|(?:^writable)/gi,"");
    	inCSS=inCSS.replace(/(?:^<)|(?:>$)/gi,"");
    	SM=cAttr.match(/^writable:s/gi)?"return false;":"";
    	SMCSS=cAttr.match(/^writable:s/gi)?"white-space:nowrap;overflow:hidden;":"overflow-y:scroll;overflow-x:auto;";
    	inHTML=inHTML.replace(/\[style\]/gi,inCSS);
    	inHTML=inHTML.replace(/\[return\]/gi,SM);
      inHTML=inHTML.replace(/\[SMCSS\]/gi,SMCSS);
			Elems[ky].contentWindow.document.open();
			Elems[ky].contentWindow.document.write(inHTML);
			Elems[ky].contentWindow.document.close();
			if(t.ifie){
				Elems[ky].contentWindow.document.body.contentEditable=true;
			}else{
				Elems[ky].contentWindow.document.designMode="on";
			}
    }
  };
  init=function(){ /*初始化变量及导入相应的库和插件*/
	  	var cFname=gPath("part"),cVl,
	  	    showError=toNum((cVl=t.$_GET('error',cFname))?cVl:t.showError,"int"),
	  	    lib=toNum((cVl=t.$_GET('lib',cFname))?cVl:loadLib,"int"),
	  	    jq=toNum((cVl=t.$_GET('jq',cFname))?cVl:t.loadJQ,"int"),
	  	    plugin=toNum((cVl=t.$_GET('plugin',cFname))?cVl:loadPlugin,"int");

      /*获取p参数解决外界变量冲突*/
	  	myTool=(cVl=t.$_GET('p',cFname))?(cVl.match(/^[a-zA-Z_\$][\w_\$]*/gi)?cVl:myTool):myTool;
	  	A[myTool]=t;/*为本工具附加处理对象*/

      if(!t.cookie(myTool+"_CACHE")){t.cookie(myTool+"_CACHE",t.startTime)}; /*设置系统缓存标识*/

	  	t.error(showError);
	    if(lib){
		     var libURL="config_js.php?type=lib";
			   t.loadJSON(libURL,function(jData){for(var i=0;i< jData.length;i++){t.loadJS(jData[i],"lib");}},"cache");
	    }
		  if(jq){
		  	var pluginURL="config_js.php?type=plugin";
		  	if(!A.jQuery){
		  		t.loadJS(jQName,function(){
		          	  if(A.jQuery){t[myJq]=A.jQuery;isJ=true;};
					  		  if(isJ&&plugin){
					  		    t.loadJSON(pluginURL,function(jData){for(var i=0;i< jData.length;i++){t.loadJS(jData[i],"plugins");}},"cache");
					  		  }
		  		});
		  	}else{
		  		if(A.jQuery){t[myJq]=A.jQuery;isJ=true;};
		  	  if(isJ&&plugin){
		  	    t.loadJSON(pluginURL,function(jData){for(var i=0;i< jData.length;i++){t.loadJS(jData[i],"plugins");}},"cache");
		  	  }
		  	}
		  }
		  t.loadJS(libArr,'lib');
		  t.$_GET();
		  t.bind(A,"onload",function(){
		  	initElem();
		  });
  };
  init();
})(window);