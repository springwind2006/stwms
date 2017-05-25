var host="ws://180.76.169.237:8888/",
	socket,localId="",isReco=false,
	isLed=false,isBuzzer=false;
$(function(){
    var iswx=typeof(wx)!="undefined",
    	startReco=function(){
    		$("#info").html("正在识别...");
    		localId!="" && iswx && wx.translateVoice({
			   localId: localId,
			    isShowProgressTips: 1,
			    success: function (res) {
    				$("#info").html(res.translateResult);
    				dispose('say',res.translateResult.replace(/。$/gi,""));
			    }
			});
    	};
    try {
        socket = new WebSocket(host);
        socket.onopen = function (msg) {
        	dispose('info','ip');
            debug('Connected');
        };
        socket.onmessage = function (msg) {
        	if(msg.data.indexOf(":")!=-1){
        		var op=msg.data.substr(0,msg.data.indexOf(":")),
        			data=msg.data.substr(msg.data.indexOf(":")+1);
        		if(op=="INFO_ip"){
        			$("#video").attr("src","http://"+data+":8889/?action=stream&t="+(new Date()).getTime());
        			$("#video").show();
        			$(".buttons").show();
        		}
        	}
            debug(msg.data);
        };
        socket.onclose = function (msg) {
        	delete socket;
        	socket=null;
            debug("Lose Connection!");
        };
    } catch (ex) {
    	delete socket;
        socket=null;
        debug(ex);
    }
    
    //录音按键
    $("#recoder").bind("click",function(){
    	if(!isReco){
	    	localId="";
	    	if(iswx){
	    		wx.startRecord();
		    	$("#info").html("开始录音...");
		    	$("#recoder").css("color","#f00");
		    	isReco=true;
	    	}else{
	    		$("#info").html("不支持录音");
	    	}
    	}else{
    		$("#info").html("停止录音");
    		$("#recoder").css("color","#fff");
	    	iswx && wx.stopRecord({
			    success: function (res) {
			        localId = res.localId;
			        startReco();
			    }
			});
    		isReco=false;
    	}
    });
    
    //led灯按键
    $("#led").bind("click",function(){
    	if(!isLed){
	    	$("#led").css("color","#f00");
	    	dispose("act",7);
	    	isLed=true;
    	}else{
    		$("#led").css("color","#fff");
    		dispose("act",8);
    		isLed=false;
    	}
    });
    
    //蜂鸣器按键
    $("#buzzer").bind("click",function(){
    	if(!isBuzzer){
	    	$("#buzzer").css("color","#f00");
	    	dispose("act",9);
	    	isBuzzer=true;
    	}else{
    		$("#buzzer").css("color","#fff");
    		dispose("act",10);
    		isBuzzer=false;
    	}
    });
    
    $("#send_msg").click(function(){
    	var msg=$.trim($("#text_msg").val());
    	if(msg!=""){
    		dispose('say',msg);
    	}
    });
    
    
    iswx && wx.onVoiceRecordEnd({
	    complete: function (res) {
    		isReco=false;
			$("#recoder").css("color","#fff");
			localId = res.localId; 
			startReco();
	    }
	});
    
});

//发送消息
function dispose(type,data){
	var cmd=type.toUpperCase()+"_"+data
	debug("发送："+cmd);
	try {
		if(socket==null){
			socket = new WebSocket(host);
		}
	    socket.send(cmd);
	} catch (ex) {
	    debug(ex);
	}
}

//调试信息
function debug(res){
	$(".debug").html(JSON.stringify(res)+"<br/>"+$(".debug").html());
}