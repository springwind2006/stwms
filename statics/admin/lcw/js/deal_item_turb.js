$(document).ready(function(){
	bind_deal_turb();	
});

function bind_deal_turb()
{
	$("input[name^='lock_']").attr("value",function(){
		var nx=$(this).attr("xn"),ni=$(this).attr("tn"),oi=$("#user_"+nx),si=oi.val();
		check_name(si,$(this).attr("di"),$(this).attr("ti"),oi,$(this),$("[xx^='file_'][xx$='"+nx+"']"),$("input[name^='type_'][name$='"+nx+"']"));
		return ($("#user_"+$(this).attr("xn")).val().length>0)?"解锁":"锁定";
	});
	$("input[name^='lock_']").bind("click",function(){
		var nx=$(this).attr("xn"),ni=$(this).attr("tn"),oi=$("#user_"+nx),si=oi.val();
		if (1>si.length||oi.attr("disabled")==true){
			oi.attr("disabled",false);$(this).val("锁定");$("[xx^='file_'][xx$='"+nx+"']").css("display","none");return;
		}
		while (ni>0){
			if ($("#user_"+ni).val()==si&&ni!=nx){
				alert(si+"：已存在");oi.val("");return;
			}
			ni--;
		}
		check_name(si,$(this).attr("di"),$(this).attr("ti"),oi,$(this),$("[xx^='file_'][xx$='"+nx+"']"),$("input[name^='type_'][name$='"+nx+"']"));
	});
	
	$("[xx^='file_']").attr("style",function(){
		return ($("#user_"+$(this).attr("xn")).val().length>0)?"display:inline;":"display:none;";
	});
	
	$("input[name^='type_']").bind("focus",function(){
		var ol=$("#list_tp");
		$(this).val("");
		fill_list(ol,$(this).attr("sl"));
		$("#list_tp li").attr("xn",$(this).attr("xn"));
		$("#list_tp li").css({"margin":"2px 5px","cursor":"pointer"});
		ol.css({"width":$(this).outerWidth()-2+"px",
				"left":$(this).offset().left+"px",
				"top":$(this).offset().top+$(this).outerHeight()+"px"});
		ol.show();
		$("#list_tp li").bind("mousedown",function(){
			$("input[name='type_"+$(this).attr("xn")+"']").val($(this).text());
		});
	});
	$("input[name^='type_']").bind("blur",function(){
		var ol=$("#list_tp");
		ol.hide();
	});
	
	/* $("input[name^='user_']").attr("disabled",function(){
		return ($(this).val().length > 0);
	}); */
	
	$("form").bind("submit",function(){
		$("input[name^='user_']").attr("disabled",false);
	});
	$("input[name^='addfile_']").bind("click",function(){
		var nx=$(this).attr("xn"),ot=$("input[name='type_"+nx+"']");
		if (ot.val()==""||ot.val()=="类型：")return;
		ajaxFileUploads(nx,ot.val(),ot.attr("sl"));
	});
}

function check_name(un,di,ti,o,op,of,otp)
{
	if (1 > un.length) return;
	$.ajax({
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=check_name", 
			data: "ajax=1&un="+un+"&di="+di+"&ti="+ti,
			dataType: "json",
			success: function(obj){
				if (obj.status==0){
					alert(obj.info);
					o.val("");
					return;
				}
				o.attr("iturb",obj.iturb);o.attr("iuser",obj.iuser);o.attr("disabled",true);op.val("解锁");otp.attr("sl",obj.type);
				fill_list(of.find("[xx='li_tp']"),obj.card,"a","删除","del_tp($(this),"+o.attr("iturb")+")");of.css("display","inline");
			}
	});
}

function fill_list(o,sl,tp,ad,fn,sp)
{
	if (typeof(tp)=="undefined"||tp==null)tp="li";
	if (typeof(ad)=="undefined"||ad==null)ad="";
	if (typeof(sp)=="undefined"||sp==null)sp=";";
	o.find("[fill_list]").remove();
	if (typeof(sl)!="string"||1>sl.length||sl=="null")return;
	var al=sl.split(sp);
	for (i=0;al.length>i;i++){
		o.append("<"+tp+" fill_list='1'>"+al[i]+"</"+tp+">"+((ad.length>0)?"<a xv='"+al[i]+"' fill_list='1' href='javascript:;' onclick='"+fn+"'>-"+ad+"</a> ":""));
	}
}

function del_tp(o,ti)
{
	$.ajax({
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=delete_type",
			data: "ajax=1&name="+o.attr("xv")+"&ti="+ti,
			dataType: "json",
			type:"POST",
			success: function(obj){
				if (obj.status==0){
					alert(obj.info);
					return;
				}
				o.parent().find(":contains('"+o.attr("xv")+"')").remove();o.remove();
			}
	});
}