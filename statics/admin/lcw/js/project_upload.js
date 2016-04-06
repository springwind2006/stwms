function upload_image(obj,file_id,img_id,field_id,width,height){
	$("input[name='" + file_id + "']").bind("change",function(){
		$(obj).hide();
		$(obj).parent().find(".fileuploading").removeClass("hide");
		$(obj).parent().find(".fileuploading").removeClass("show");
		$(obj).parent().find(".fileuploading").addClass("show");
		$.ajaxFileUpload
			(
				{
					url:APP_ROOT + '/api/upload.php?file_key='+file_id+"&width="+(width?width:0)+"&height="+(height?height:0),
					secureuri:false,
					fileElementId:file_id,
					dataType:'json',
					success:function(data,status){
						$(obj).show();
						$(obj).parent().find(".fileuploading").removeClass("hide");
						$(obj).parent().find(".fileuploading").removeClass("show");
						$(obj).parent().find(".fileuploading").addClass("hide");
						if(data.status == 1){
							$("#"+img_id).attr("src",data.thumb_url + "?r=" + Math.random());
							$("#"+field_id).val(data.url);
						}else{
							$.showErr(data.msg);
						}
					},
					error:function(data,status,e){
						$.showErr(data.responseText);
						$(obj).show();
						$(obj).parent().find(".fileuploading").removeClass("hide");
						$(obj).parent().find(".fileuploading").removeClass("show");
						$(obj).parent().find(".fileuploading").addClass("hide");
					}
				}
			);
		$("input[name='" + file_id + "']").unbind("change");
	});
}

function upload_file(obj,file_id,field_id,type){
	$("input[name='" + file_id + "']").bind("change",function(){
		$(obj).hide();
		$(obj).parent().find(".fileuploading").removeClass("hide");
		$(obj).parent().find(".fileuploading").removeClass("show");
		$(obj).parent().find(".fileuploading").addClass("show");
		$.ajaxFileUpload
			(
				{
					url:APP_ROOT + '/api/upload.php?file_type=file&allow_ext='+type+'&file_key='+file_id,
					secureuri:false,
					fileElementId:file_id,
					dataType:'json',
					success:function(data,status){
						$(obj).show();
						$(obj).parent().find(".fileuploading").removeClass("hide");
						$(obj).parent().find(".fileuploading").removeClass("show");
						$(obj).parent().find(".fileuploading").addClass("hide");
						if(data.status == 1){
							$("#"+field_id).val(data.url);
							$("#"+field_id+"_status").show();
							$("#"+field_id+"_status a").attr("href",data.url);
							$("#"+field_id+"_status img").attr("src",data.url);
						}else{
							$.showErr(data.msg);
						}
					},
					error:function(data,status,e){
						$.showErr(data.responseText);
						$(obj).show();
						$(obj).parent().find(".fileuploading").removeClass("hide");
						$(obj).parent().find(".fileuploading").removeClass("show");
						$(obj).parent().find(".fileuploading").addClass("hide");
					}
				}
			);
		$("input[name='" + file_id + "']").unbind("change");
	});
}

function upload_avatar(obj){
	var avatar_file_id=$("input",obj).attr("id"),
		avatar_id=$(obj).prev("img").attr("id"),
		field_id=$(obj).next("input").attr("id");
	upload_image(obj,avatar_file_id,avatar_id,field_id,200,200);
}
