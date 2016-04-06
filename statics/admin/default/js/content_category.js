$(function(){
  $(".treeview .folder").hover(function(){
  	$(this).addClass('hover');
  },function(){
	  $(this).removeClass('hover');
	}).click(function(){
	   taggleTree(this);
	});
  $("#treecontrol a").toggle(function(){
  	var msrc=$("#treecontrol img:eq(0)").attr('src');
  	$("#treecontrol img:eq(0)").attr('src',msrc.replace(/minus\.gif$/gi,'plus.gif'));
    $(".treeview .folder").each(function(){
      taggleTree(this,0);
    });
  },function(){
  	var msrc=$("#treecontrol img:eq(0)").attr('src');
  	$("#treecontrol img:eq(0)").attr('src',msrc.replace(/plus\.gif$/gi,'minus.gif'));
    $(".treeview .folder").each(function(){
      taggleTree(this,1);
    });
  });
});

function taggleTree(obj,tp){
   var isShow=typeof(tp)!="undefined"?tp:$(obj).prev(".hitarea").is(".expandable-hitarea");
   if(isShow){
      $(obj).prev(".hitarea").removeClass("expandable-hitarea");
      $(obj).parent().attr('class','collapsable');
      $(obj).next('ul').show();
   }else{
      $(obj).prev(".hitarea").addClass("expandable-hitarea");
      $(obj).parent().attr('class','expandable');  	      
      $(obj).next('ul').hide();
   }
}
