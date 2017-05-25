var connectDeviceId="";
wx.ready(function(){
	wx.invoke(
		'openWXDeviceLib',
		{},
		function(res){
			if(res.err_msg=="openWXDeviceLib:ok"){
				switch(res.bluetoothState){
					case "on":
						if(res.isSupportBLE=="yes"){
							getWXDeviceInfos();
						}else{
							debug("手机蓝牙不支持");
						}
					break;
					case "off":
						debug("设备已关闭");
					break;
					case "restting":
						debug("设备等待接入...");
					break;
					case "unauthorized":
						debug("设备未授权");
					break;
					default:
						debug("未知设备");
						break
				}
			}else{
				debug("设备打开失败");
			}
		}
	);
	wx.on('onWXDeviceStateChange', function(res) {
		if(res.state=="connected"){
			connectDeviceId=res.deviceId;
		}else{
			connectDeviceId="";
		}
		debug(res.state=="connecting"?"连接中...":(res.state=="connected"?"已连接设备":"已断开设备"));
  	});
	
	//监听接受到信息
	wx.on('onReceiveDataFromWXDevice', function(res) {
		var unicode= BASE64.decoder(res.base64Data);
        var str = '';  
        for(var i=0,len=unicode.length;i<len;++i){
        	str += String.fromCharCode(unicode[i]);  
        }
        debug("收到:"+str);
  	});
	
	wx.on('onScanWXDeviceResult',function(res){
		debug(res);
	});
	
	wx.on('onWXDeviceBluetoothStateChange',function(res){
	    debug(res);
	});
	wx.error(function(res){
	    debug(res);
	});
	
});

function getWXDeviceInfos(){
	wx.invoke(
		'getWXDeviceInfos',
		{'connType':'blue'},
		function(res){
			var dId,dState;
			if(res.err_msg=="getWXDeviceInfos:ok"){
				if(res.deviceInfos.length>0){
					for(var k in res.deviceInfos){
						dId=res.deviceInfos[k].deviceId;
						dState=res.deviceInfos[k].state;//eg: connected
						connectDeviceId=dState=="connected"?dId:"";
					}
					debug(connectDeviceId!=""?"设备已连接":"设备未连接");
				}else{
					debug("未绑定设备");
				}
			}else{
				debug("设备初始化错误");
			}
		}
	);
}

//发送蓝牙消息
function sendToBle(data){
	debug("发送："+data);
	if(connectDeviceId!=""){
		wx.invoke(
			'sendDataToWXDevice', 
			{'deviceId':connectDeviceId,'base64Data':BASE64.encoder("CMD_"+data)}, 
			function(res){
				debug(res.err_msg=="sendDataToWXDevice:ok"?"发送成功":"发送失败");
			}
		); 
	}else{
		debug("设备未连接！");
	}
}

//调试信息
function debug(res){
	$(".debug").html(JSON.stringify(res));
}