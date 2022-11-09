$(document).ready(function(){
	jQuery.fn.imageScroller = function(params){
		var p = params || {
			next:"buttonNext",
			prev:"buttonPrev",
			frame:"viewerFrame",
			width:105,
			child:"a",
			auto:true
		}; 
		var _btnNext = $("#"+ p.next);
		var _btnPrev = $("#"+ p.prev);
		var _imgFrame = $("#"+ p.frame);
		var _width = p.width;
		var _child = p.child;
		var _auto = p.auto;
		var _itv;
		_imgFrame.mouseover(function(){
			autoStop();
		});
		_imgFrame.mouseout(function(){
			autoPlay();
		});
		var turnLeft = function(){
			_btnPrev.unbind("click",turnLeft);
			if(_auto) autoStop();
			act_flag=turnLeft;
			_imgFrame.animate( {marginLeft:-_width}, 'fast', '', function(){
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.find(_child+":first").appendTo( _imgFrame );
				_imgFrame.css("marginLeft",0);
				_btnPrev.bind("click",turnLeft);
				if(_auto) autoPlay();
			});
		};	
		var turnRight = function(){
			_btnNext.unbind("click",turnRight);
			if(_auto) autoStop();
			act_flag=turnRight;
			_imgFrame.find(_child+":last").clone().show().prependTo( _imgFrame );
			_imgFrame.css("marginLeft",-_width);
			_imgFrame.animate( {marginLeft:0}, 'fast' ,'', function(){
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_imgFrame.find(_child+":last").remove();
				_btnNext.bind("click",turnRight);
				if(_auto) autoPlay(); 
			});
		};
		
		_btnNext.css("cursor","hand").click( turnRight );
		_btnPrev.css("cursor","hand").click( turnLeft );
		
		var act_flag=turnLeft;
		var autoPlay = function(){
		  _itv = window.setInterval(act_flag, 3000);
		};
		var autoStop = function(){
			window.clearInterval(_itv);
		};
		if(_auto)	autoPlay();
	};


	$(function(){	
		$("#viewer").imageScroller({
			next:"btn_r",
			prev:"btn_l",
			frame:"viewerFrame",
			width:105,
			child: "div",
			auto: true
		});	 
	});

})
						  