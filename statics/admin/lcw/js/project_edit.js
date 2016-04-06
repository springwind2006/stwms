//切换地区
function load_city(obj,target){
	var id = $(obj).find("option:selected").attr("rel");
	var html = "<option value=''>请选择城市</option>";
	if(id!=0){
		var regionConfs=regionConf['r'+id]['c'];
		for(var key in regionConfs)	{
			html+="<option value='"+regionConfs[key]['n']+"' rel='"+regionConfs[key]['i']+"'>"+regionConfs[key]['n']+"</option>";
		}
	}
	$(target).html(html);
}

function check_user(){
	var user_id = $("input[name='user_id']").val();
	if(!isNaN(user_id)&&parseInt(user_id)>0){
		$.ajax({
		url: ROOT+"?"+VAR_MODULE+"=User&"+VAR_ACTION+"=check_user&id="+user_id,
		data: "ajax=1",
		dataType: "json",
		success: function(obj){
				if(!obj.status){
					alert("会员ID不存在");
					$("input[name='user_id']").val('');
				}
			}
		});
	}else{
		$("input[name='user_id']").val('');
	}
}
