$(function(){
	if($(".image_uploader img").length){
		var h=$(".image_uploader img").height();
		var aw=$(".image_uploader input[type='hidden']").attr("width");
		var mxw=parseInt(typeof(aw)=="string"?aw.replace(/[^\d]+/gi,""):"600");
		$(".image_uploader").css({'position':"relative",'height':h,'width':'100%','overflow':'hidden','text-align':'center'});
		$(".image_uploader input[type='file']").css({'display':'block','position':'relative','width':'100%','height':h,'top':-h,'background':'#000','opacity':0,'padding':0});
		$(".image_uploader input[type='file']").localResizeIMG({
	        width: (typeof(mxw)=="number" ? mxw : 600),
	        quality: 0.5,
	        success: function (result, obj) {
	            var field = $(obj).attr("id").replace("_file", ""),
	            	sheight = $("#" + field + "_viewer").height(),
	            	up_url=$(obj).attr("data-url"),loader;
	            loader=weui.loading("识别中...");
	            $.post(up_url,{uploadfile:result.base64},function(res){
	            	var nation=2,sex=0;
            		console.log(res);
            		loader.hide();
	            	$("#" + field).val(res.path);
	            	$("#" + field + "_viewer").attr("src", res.path);
	            	if(typeof(res['infos'])=="object"){
	                    if(
	                        res['infos']['nationality'].indexOf("维吾尔")!=-1 ||  
	                        res['infos']['address'].indexOf("新疆")!=-1
	                    ){
	                        nation=1;
	                    }else if(res['infos']['nationality'].indexOf("汉")!=-1){
	                        nation=0;
	                    }
	                    sex=res['infos']['sex']=="男"?1:(res['infos']['sex']=="女"?2:0);
	                    $("#name").val(res['infos']['name']);
	                    $("#id_addr").val(res['infos']['address']);
	                    $("#id_no").val(res['infos']['num']);
	                    $("#sex").val(sex);
	                }
	            },"json");
	        }
	    });
		$(".image_uploader img").bind("load",function(){
			var h=$(this).height();
			$(this).parent().height(h);
			$("input[type='file']",$(this).parent()).css({'height':h,'top':-h});
		});
	}
	
	//选择位置
	$("#pos_select").click(function(){
		var url="http://apis.map.qq.com/tools/locpicker?search=1&type=1&key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77&referer=myapp";
		$("#mapPage").attr("src",url);
		$("#showInput").hide();
		$("#showMap").show();
		return false;
	});
	//绑定窗口事件，接收位置选择结果
	$(window).bind("message",function(event){
		var loc = event.data;
        if (loc && loc.module == 'locationPicker') {
			$("#showInput").show();
			$("#showMap").hide();
			$("#addr_name").val(loc.poiname);
			if($.trim($("#detail_addr").val())==""){
				$("#detail_addr").val(loc.poiaddress);
			}
			$("#pos_select").html(loc.poiname);
			$("#latitude").val(loc.latlng.lat);
			$("#longitude").val(loc.latlng.lng);
        }
	});
	//查看位置
	$("#pos_view").click(function(){
		wx.openLocation({
		    latitude: parseFloat($("#latitude").val()), // 纬度，浮点数，范围为90 ~ -90
		    longitude: parseFloat($("#longitude").val()), // 经度，浮点数，范围为180 ~ -180。
		    name: $("#addr_name").val(), // 位置名
		    address: $("#detail_addr").val(), // 地址详情说明
		    scale: 24, // 地图缩放级别,整形值,范围从1~28。默认为最大
		    infoUrl: '' // 在查看位置界面底部显示的超链接,可点击跳转
		});
		return false;
	});
	//选择时间
	$("#come_date_select,#left_date_select").click(function(){
		var cid=$(this).attr("id").replace("_select",""),that=this,
			value=$.trim($("#"+cid).val()),
			date=new Date(),
			y=date.getFullYear(),
			m=date.getMonth()+1,
			d=date.getDate(),
			dValue=value!=""?value.split("-"):[y,m,d];
		weui.datePicker({
			defaultValue:dValue,
            start: y-3,
            end: y+10,
            onChange: function (result) {
                //console.log(result);
            },
            onConfirm: function (result) {
               $("#"+cid).val(result.join("-"));
               $(that).html(result.join("-"));
            }
        });
	});
	
	//数据提交
	$("#submit_btn").click(function(){
		var data=$("#doform").serializeArray(),
			cid="",cvalue="",res="",tips="",loader;
		for(var k in data){
			id=data[k].name;
			cvalue=data[k].value;
			if($("#"+id).attr("data-input")!="optional"&&$.trim(cvalue)===""){
				tips=$("#"+id).attr("placeholder")||$("#"+id).attr("data-tips");
				weui.topTips(tips, 'success');
				$("#"+id).focus();
				return false;
			}
		}
		loader=weui.loading("提交中...");
		$.post($("#doform").attr("action"),$("#doform").serialize(),function(res){
			var is_reset=res.status=="1" && (typeof(res.noreset)=="undefined"||res.noreset=="0");
			loader.hide();
			weui.toast(res.info,2000);
			if(is_reset){
				$("#doform")[0].reset();
				$("#id_img").val("");
				$("#id_img_viewer").attr("src",$("#id_img_viewer").attr("data-default"));
			}
			if(typeof(res.url)!="undefined" && res.url!=""){
				setTimeout(function(){
					window.location=res.url;
				},2000);
			}
		},"json");
	});
});