// JavaScript Document
var body_resize=function(){
	// var documentz = $(window).width();
	// var documentzz = 1100;
	// if(documentz > 1100){
	// 	$(".containter").css({"width":"100%"});
	// }else{
	// 	$(".containter").css({"width":documentzz});
	// } 

	/*不同浏览器不同的高度*/
	var windowheght = $(window).height();
	var windowheghttt = windowheght -50;
	var conriht = $(".content_right").height();
	if(windowheght > conriht ){
		$(".content_left").css({"min-height":windowheghttt});
	}else {
		$(".content_left").css({"min-height":conriht});
	}
};

//系统tablegrid组件js  旧版本tablegrid 兼容
var table_flash = function(table_container){
	//同时刷新框架高度
	body_resize();


	/******兼容老旧的table样式 start*********/
	if(undefined==table_container){
		var table_container = $(".table_container");
	}
	for(var i =0; i < table_container.length;i++){ //表个数

		var table_title = table_container.eq(i).find(".table_title");
		var table_con_son = table_container.eq(i).find(".table_con_son");

		var sumbg =0;
	
		table_title.find("li").last().css({"border-right":"none"});
	
		var table_title_li = table_title.find("li");

		var table_list = table_con_son.find(".table_list"); //数据行
		//alert(table_title.eq(i).find("li").eq(0).width())
		for(var j = 0; j < table_title_li.length;j++){
			sumbg +=parseInt(table_title.find("li").eq(j).width());
		}

		var suntall = sumbg + 21*table_title.find("li").length + 1;
		table_title.parents(".table_con_son").css({"min-width":suntall});

		//数据列宽度同步
		for(var j = 0; j < table_title_li.length;j++){
			var h_width=table_title.find("li").eq(j).width();
			$.each( table_list, function(k, row){
				$(row).find("li").eq(j).css({"width":h_width +1});
			});

			table_con_son.find(".table_search li").eq(j).css({"width":h_width +1});
		}

		//选择框
		table_con_son.find(".table_check input[type='checkbox']").click(function(){
			var table_list=$(this).closest('.table_con_son').find(".table_list");
			if($(this).is(':checked')){
				table_list.find('li:first').find("input[type='checkbox']").prop("checked",true);
			}else{
				table_list.find('li:first').find("input[type='checkbox']").removeAttr("checked");
			}
		});
	}

	/******兼容老旧的table样式 end*********/


	/** GridView 操作列浮动效果 ****start****/
	$('.table_table').each(function(i){
		var table=$(this).find('.table_hiddle table');
		//console.log(table)
		if(table.attr('last-float')=='false'){
			return true;//跳过
		} 
		var out_width=$(this).find('.table_hiddle').width();
		var in_width=table.width();
		if(out_width==0){
			return true;// 表格隐藏跳过 显示时需要强制table_flash
		}
		table.find("tr").each(function(){
			$(this).find("td:last,th:last").css({ position: "relative", left: out_width-in_width });
		});
		$(this).find('.table_hiddle').off().scroll(function(){
			var left_w=$(this).scrollLeft();
			table.find("tr").each(function(){
				var td_last=$(this).find("td:last,th:last");
				td_last.css({left: out_width-in_width+left_w });
			});

		});
	});
	/** GridView 操作列浮动效果 ******end***/

};


//兼容chrome
$(window).load(function(){
	table_flash();
	/*当浏览器小于一定宽度时。固定页面宽度*/
	$(window).resize(function(){
		/*不同浏览器不同的高度*/
		table_flash();
	});
});


$(function(){
	/*登录*/
	$(".head_right_01").hover(function(){
		$(".plat_user_main").toggle();
	});
    /*二级菜单*/
	$(".content_left_01").click(function(){
		//$(".ercds").slideUp(700);
		$(".ercds").stop().slideUp(200);
		$(".tubiaojian").removeClass("tubiaojian01");
		$(".tubiaojian").parent().removeClass("navbc");
		$(this).find(".tubiaojian").addClass("tubiaojian01");

		if ( ! $(this).find(".tubiaojian").parent().hasClass("menu_a_visit") ){
			$(this).find(".tubiaojian").parent().addClass("navbc");
		}
		if($(this).find(".ercds li").length>0){
			$(this).find(".ercds").stop().slideDown(200);
		}
		
	});
	/*消息提醒*/
	$(".zwxx").hover(function(){
		$(this).find(".plat_user_mainxx").toggle();
	});
	/**/
	/*添加房间*/
	$(".tjfjz").click(function(){
		$(".tjfj").show();
	});
	/*关闭*/
	$(".fjgb").click(function(){
		$(".tjfj").hide();
	});
	

		
})

