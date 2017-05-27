$(function(){
	var info={'username':"用户名",'password':"密码",'checkcode':"验证码"};
  $("#username,#password,#checkcode").keyup(function(){
  	$("#status").html("提示：正在输入"+info[this.id]+"...");
  	if(this.id=="checkcode"){
      $(this).val($(this).val().replace(/[^\d\w]+/gi,""));
  	}
  });
  $("#username,#password,#checkcode").focus(function(){
  	$(this).css("background-color","#eea");
  });
  $("#username,#password,#checkcode").blur(function(){
  	$(this).css("background-color","#fff");
  });
  $("#dosubBt").click(function(){
  	var subURL=location.href.substr(location.href.lastIndexOf("/")+1);
    for(var k in info){
      if($.trim($("#"+k).val())==''){
        $("#"+k).focus();
        $("#status").html("提示："+info[k]+"不能为空!");
        return false;
      }
    }
    $.ajax({
		  url: subURL,
		  cache: false,
		  data:{"username":$("#username").val(),
		  	    "password":$("#password").val(),
		  	    "checkcode":$("#checkcode").val(),
		  	    "dosub":"1"},
		  dataType:"json",
		  success: function(r){
		  	if(r['code']=='0'){
          $("#status").html("提示："+r['msg']+" 正在为您跳转...");
          window.location=r['url'];
		  	}else{
          $("#status").html("提示："+r['msg']);
		  	}
		  }
		});
  });
  $("#resetBt").click(function(){$("#doform")[0].reset();});  
});