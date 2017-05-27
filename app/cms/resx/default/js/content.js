$(function(){
	if($("#hits_count")[0]){	
		var catidstr=$("#hits_count").html(),catids=catidstr ? catidstr.split("|"):[];	
		$.get("/index.php?c=api&a=hits&catid="+catids[0]+"&id="+catids[1],function(r){
			$("#hits_count").html(r["views"]);
		},"json");
	}
});

$(function(){
	  if($.browser.msie && $.browser.version=='6.0'){
 	  	loadVAR();
	  }

	  try{$(".hover").css('opacity', 0);}catch(e){}
		try{$(".menu0 .hover").css('opacity', 1);}catch(e){}
		try{
			$(".hover").hover(
				function(){
					$(this).stop().animate({opacity: '1'},200);
				},
				function(){
					$(this).stop().animate({opacity: '0'},500);
					$(".menu0 .hover").stop().animate({opacity: '1'},500);
				}
			);
	 }catch(e){}
	 try{$("body").append("<iframe src=\"about:blank\" name=\"subframe\" id=\"subframe\" frameBorder=\"0\" scrolling=\"no\" style=\"height:0px;width:0px;display:none\"></iframe>");}catch(e){};
});

function loadVAR(){
	  if(typeof(jQuery)!="undefined"){
			p.loadFile('jquery.pngFix.js',function(){
				try{$(".logo").pngFix('self');}catch(e){};
				try{$(".menu").pngFix('self');}catch(e){};
				try{$(".slideShow span").pngFix('self');}catch(e){};
				try{$(".caseCommend span").pngFix('self');}catch(e){};
				try{$(".mask_tl").pngFix('self');}catch(e){};
			});
	  }else{
	    setTimeout("loadVAR()",100);
	  }
}


function glideObj(){
	function $id(id){return document.getElementById(id);};
	this.layerGlide=function(auto,oEventCont,oSlider,sSingleSize,second,fSpeed,point){
		var oSubLi = $id(oEventCont).getElementsByTagName('li');
		var interval,timeout,oslideRange;
		var time=1;
		var speed = fSpeed
		var sum = oSubLi.length;
		var a=0;
		var delay=second * 1000;
		var setValLeft=function(s){
			return function(){
				oslideRange = Math.abs(parseInt($id(oSlider).style[point]));
				$id(oSlider).style[point] =-Math.floor(oslideRange+(parseInt(s*sSingleSize) - oslideRange)*speed) +'px';
				if(oslideRange==[(sSingleSize * s)]){
					clearInterval(interval);
					a=s;
				}
			}
		};
		var setValRight=function(s){
			return function(){
				oslideRange = Math.abs(parseInt($id(oSlider).style[point]));
				$id(oSlider).style[point] =-Math.ceil(oslideRange+(parseInt(s*sSingleSize) - oslideRange)*speed) +'px';
				if(oslideRange==[(sSingleSize * s)]){
					clearInterval(interval);
					a=s;
				}
			}
		}

		function autoGlide(){
			for(var c=0;c<sum;c++){oSubLi[c].className='';};
			clearTimeout(interval);
			if(a==(parseInt(sum)-1)){
				//for(var c=0;c<sum;c++){oSubLi[c].className='';};
				a=0;
				oSubLi[a].className="active";
				interval = setInterval(setValLeft(a),time);
				timeout = setTimeout(autoGlide,delay);
			}else{
				a++;
				oSubLi[a].className="active";
				interval = setInterval(setValRight(a),time);
				timeout = setTimeout(autoGlide,delay);
			}
		}

		if(auto){timeout = setTimeout(autoGlide,delay);};
		for(var i=0;i<sum;i++){
			oSubLi[i].onmouseover = (function(i){
				return function(){
					for(var c=0;c<sum;c++){oSubLi[c].className='';};
					clearTimeout(timeout);
					clearInterval(interval);
					oSubLi[i].className="active";
					if(Math.abs(parseInt($id(oSlider).style[point]))>[(sSingleSize * i)]){
						interval = setInterval(setValLeft(i),time);
						this.onmouseout=function(){if(auto){timeout = setTimeout(autoGlide,delay);};};
					}else if(Math.abs(parseInt($id(oSlider).style[point]))<[(sSingleSize * i)]){
							interval = setInterval(setValRight(i),time);
						this.onmouseout=function(){if(auto){timeout = setTimeout(autoGlide,delay);};};
					}
				}
			})(i)
		}
	}
}

function SendMSNMessage(mname){
	var divObj='MSN'+'_MSN'+p.startTime,oid='MSN'+p.startTime;
  if(!p.ID(divObj)){
     p.cElem({'tag':'div','attr':{'id':divObj,'style':'height:0px;width:0px;visibility:hidden;display:none;'}}).
     innerHTML='<object id="'+oid+'" classid="clsid:B69003B3-C55E-4B48-836C-BC5946FC3B28" codetype="application/x-oleobject" width="0" height="0"></object>';
  }
	try{p.ID(oid).InstantMessage(mname);}catch(e){alert("请先安装MSN！");}
}
function AddMSNContact(mname){
	var divObj='MSN'+'_MSN'+p.startTime,oid='MSN'+p.startTime;
  if(!p.ID(divObj)){
     p.cElem({'tag':'div','attr':{'id':divObj,'style':'height:0px;width:0px;visibility:hidden;display:none;'}}).
     innerHTML='<object id="'+oid+'" classid="clsid:B69003B3-C55E-4B48-836C-BC5946FC3B28" codetype="application/x-oleobject" width="0" height="0"></object>';
  }
  try{p.ID(oid).AddContact(0, mname);}catch(e){alert("请先安装MSN！"); }
}