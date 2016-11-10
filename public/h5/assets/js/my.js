(function($) {
	'use strict';
	//各种页面转跳连接绑定
	$(function() {
		//刷新按钮
		$('.am-icon-refresh').click(function(){
			location.reload()
		});
		//退出
		$('.am-logout').click(function(){
			window.location.href='index.php?r=h5/login/logout';
		});

		

		//底部导航
		$('nav li').each(function(i){
			switch(i){
			case 0:
				var url = 'index.php?r=h5/index/index';
				break;
			case 1:
				var url = 'index.php?r=h5/sys/index';
				break;
			case 2:
				var url = 'index.php?r=h5/me/index';
				break;
			}
			$(this).click(function(){
				window.location.href=url;
			});
			
		});
	});
})(jQuery);
function to_url(url){
	window.location.href = url;
}
