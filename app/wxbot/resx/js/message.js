function cls(){
	with(event.srcElement)
	if(value==defaultValue) value=""
}
function res(){
	with(event.srcElement)
	if(value=="") value=defaultValue
}


function trim(str) {
	return str.replace(/(^[\s\u3000]*)|([\s\u3000]*$)/g,"");
}
function checkForm(){
	var ID=function(r){return document.getElementById(r)};
	if(trim(ID("content").value)=="" || trim(ID("content").value)=="您的需求"){
		alert('请填写完整信息！');
		ID("content").focus();
		return false;
	}
	if(ID("cfgnum").value.length!=5){
		alert('验证码不正确！');
		ID("cfgnum").focus();
		return false;
	}
	if(ID("email").value.length!=0){
		if (ID("email").value.charAt(0)=="." ||
			 ID("email").value.charAt(0)=="@"||
			 ID("email").value.indexOf('@', 0) == -1 ||
			 ID("email").value.indexOf('.', 0) == -1 ||
			 ID("email").value.lastIndexOf("@")==ID("email").value.length-1 ||
			 ID("email").value.lastIndexOf(".")==ID("email").value.length-1)
		  {
			  alert("Email地址格式不正确！");
			  ID("email").focus();
			  return false;
		  }
	}else{
	   alert("Email不能为空！");
	   ID("email").focus();
	   return false;
	}
   return true;
}


function feedBack(){
  if(checkForm()){
  	$.post(
  	"index.php?plugin_c=message&plugin_a=add",
  	$("#message_submit").serialize(),
  	function(r){
  		var res=$.trim(r);
  		switch(res){
  			case '1':
  				alert("谢谢您的咨询！我们会尽快处理！");
  				$("#content").val("");
  			break;
  			case '2':
  				alert("验证码填写错误！麻烦您重新输入...");
  				$("#cfgnum").focus();
  			break;
  			default:
  				alert("网络错误！麻烦您等会儿再试...");
  			break;
  		}
  	});
  }
  return false;
}