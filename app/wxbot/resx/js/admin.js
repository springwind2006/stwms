$(function(){
	$("#wx_login_bt").click(function(){
		$(this).val("正在初始化系统，请稍候！");
		$(this).attr("disabled",true);
		$.get(plugin_url('wxbot','do','type=start'),function(res){
			if(res=="1"){
				waitlogin();
			}else{
				alert("初始化失败！"+res);
			}
		});
	});
	$("#setting input").click(function(){
		var value=$(this).val(),
			name=$(this).attr("name");
		$.get(plugin_url('wxbot','do','type=setting&name='+name+'&value='+value),function(res){
			alert("设置成功！");
		});
	});
});

function waitlogin(){
	$.get(plugin_url('wxbot','do','type=checkqrurl'),function(res){
		if(res!="0" && res.indexOf("://")!=-1){
			wait_scan_qr(res);
		}else{
			waitlogin();
		}
	});	
}

function wait_scan_qr(qrurl){
	top.art.dialog({
		title:'请用微信扫描二维码登录',
	    content: '<img src="http://qr.liantu.com/api.php?text='+encodeURIComponent(qrurl)+'"/>',
	    id: 'weixin_login'
	});
	check_login();
}

function check_login(){
	$.get(plugin_url('wxbot','do','type=checklogin'),function(res){
		if(res=="1"){
			top.art.dialog.list['weixin_login'].close();
			$(this).val("已成功登录！");
			location.reload();
		}else{
			setTimeout(check_login,500);
		}
	});
}
