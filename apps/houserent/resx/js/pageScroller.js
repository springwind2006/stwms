$.fn.endlessScroll = function(options){
	var defaults = {
	    outer:"",
	    inner:"",
		bottomPixels: 50,
		resetCounter: function(){ return false; },
		callback: function(){ return true; },
		ceaseFire: function(){ return false; }
	};
	var options = $.extend(defaults, options);
	if(options.outer!="" && options.inner!=""){
		var fireSequence = 1,
			outer=$(options.outer),
			inner=$(options.inner);
		outer.scroll(function(){
			if(
				options.ceaseFire.apply(this) === false && 
				(outer.scrollTop() + options.bottomPixels >= inner.height() - outer.height())
			){
				if(options.resetCounter.apply(this) === true){
					fireSequence = 1;
				}
				fireSequence++;
				options.callback.call(this, fireSequence);
			}
		});
	}
};

$.pageScroller=function(outer,inner,container,before,after){
	var stop=false,
		target_url=$(container).attr("data-url");
	$(document).endlessScroll({
		outer:outer,
		inner:inner,
		bottomPixels: 50,
		ceaseFire:function(){
			return stop;
		},
		callback: function (p) {
		  stop=true;
		  typeof(before)=="function" && before(p);
	      $.ajax({
	          type: "post",
		      dataType: "json",
		      url: target_url,
		      data: {
		          'page': p,
		          'ajax': 1
		      },
		      success: function (data) {
		    	  stop=$.trim(data.html)=="";
		    	  !stop && $(container).append(data.html);
		    	  typeof(after)=="function" && after(data.html);
		      },
		      error:function(r){
		    	  stop=false;
		    	  typeof(after)=="function" && after(r);
		      }
	      });
		}
	}); 
}
  
