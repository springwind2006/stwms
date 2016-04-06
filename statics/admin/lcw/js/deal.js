function pay(id)
{
	$.weeboxs.open(ROOT+'?c=Deal&a=pay&id='+id, {contentType:'ajax',showButton:false,title:'发放筹款',width:600,height:180});
}

function offline(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert("请选择需要下架的项目");
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm("确定要下架选中的项目吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delete&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}

function deal_item(id)
{
	location.href = ROOT + '?c=Deal&a=deal_item&id='+id;
}

function pay_log(id)
{
	location.href = ROOT + '?c=Deal&a=pay_log&id='+id;
}

function deal_log(id)
{
	location.href = ROOT + '?c=Deal&a=deal_log&id='+id;
}

function add_deal_item(deal_id)
{
	location.href = ROOT + '?c=Deal&a=add_deal_item&id='+deal_id;
}

function edit_deal_item(item_id)
{
	location.href = ROOT + '?c=Deal&a=edit_deal_item&id='+item_id;
}

function point(id)
{
	location.href = ROOT + '?c=Deal&a=point_start&id='+id;
}

//支持人，领筹人发送积分
function point_1_2(id)
{
	var _p = $('#p_u_'+id).val();

	//修改状态
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=point_s_1_2&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(uid){
				location.href = ROOT + '?c=Deal&a=spoint&user_id='+uid+'&point='+_p;
			}
	});
}

//发起人发送积分
function point_999(id)
{
	var _p = $('#p_u_'+id).val();

	//修改状态
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=point_s_999&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(uid){
				location.href = ROOT + '?c=Deal&a=spoint&user_id='+uid+'&point='+_p;
			}
	});
}

//增信积分发放
function point_3_4(id)
{
	var _p = $('#p_u_'+id).val();

	//修改状态
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=point_s_3_4&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(uid){
				location.href = ROOT + '?c=Deal&a=spoint&user_id='+uid+'&point='+_p;
			}
	});
}

//批量发放积分
function putall()
{

	idBox = $(".key:checked");
	if(idBox.length == 0)
	{
		alert("请选择要发放积分的用户");
		return;
	}
	idArray = new Array();
	$.each( idBox, function(i, n){
		var t1 = $(n).val();

		var tp =  $('#p_u_'+t1).val();

		//获取积分
		idArray.push(t1+'|'+tp);

		//获取积分

	});
	id = idArray.join(",");

	if(confirm("确定要批量发放积分吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=putall&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(rtn){
				//if(rtn == 1)
				location.href=location.href;
			}
	});
}

function del_deal_item(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert("请选择需要删除的项目");
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm("确定要删除选中的项目吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=del_deal_item&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}

function add_pay_log(deal_id)
{
	$.weeboxs.open(ROOT+'?c=Deal&a=add_pay_log&id='+deal_id, {contentType:'ajax',showButton:false,title:"发放筹款",width:600,height:220});
}

function del_pay_log(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert("请选择需要删除的项目");
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm("确定要删除选中的项目吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=del_pay_log&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}

function del_deal_log(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert("请选择需要删除的项目");
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm("确定要删除选中的项目吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"=Deal&"+VAR_ACTION+"=del_deal_log&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}

