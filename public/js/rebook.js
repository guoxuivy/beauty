/***预约本效果js****/
var Rebook = function () {
    return {
        //main function to initiate the module
        init: function () {

            var titlex = 0;
			var outwidth = 0;
			var yyliewid = 0; 
			var i = 0;
			var yylie = 0; 


            function initTable(){ 
				titlex = $(".yy_two_son_title").offset().top;
				outwidth = $(".yy_two_son").width();
				yylie = $(".yy_two_son_title ul li").length;
				if(yylie * 101 + 30<outwidth){
					yyliewid = outwidth;
				}else{
					yyliewid = yylie * 101 + 30 ;
				}

				$(".yy_two_son_con").css({"width":yyliewid}); //

				$(".yy_two_son_title").css({"width":yyliewid});
				if(yylie * 101 + 30<outwidth){
					$(".yy_two_son_list ul li").css({"width":(yyliewid-30)/yylie}); //
					$(".yy_two_son_title ul li").css({"width":(yyliewid-30)/yylie});
				}
					
			}

            function meeting(){  
				$(".pre").click(function(){
					if(outwidth < yyliewid ){
						var morenu = Math.round((yyliewid - outwidth)/101);
						i <morenu ? i++ : morenu ;
						$(".yy_two_son_con").animate({left: -i*101},200);
					}
				});	
				i;
				$(".next").click(function(){	
					if(outwidth < yyliewid ){
						i >= 1 ? i-- : 0;
						$(".yy_two_son_con").animate({left: -i*101},200);	
					} 
				});	
			}
			//表头固定
			function titlefixed(){
				$(window).scroll(function(){
					if($(window).scrollTop() >= titlex){
						$(".zwtitlefa").css({"position":"fixed","top":"0"})
						$(".yy_two_son_con_l_title").css({"position":"fixed","top":"0",})
						$(".yy_two_son01").css({"position":"fixed","top":"0"})
					}else{
						$(".zwtitlefa").css({"position":"static"});
						$(".yy_two_son_con_l_title").css({"position":"static"});
						$(".yy_two_son01").css({"position":"absolute"});
					}
				});
			}

			//鼠标经过 行列 变色
			function invital(){
				$(".yy_two_son_list li").hover(function(){
					var iNum = $(this).parents(".yy_two_son_list").find("li").index($(this)[0])+1; 
					//console.log(iNum);
					$(".yy_two_son_list li:nth-child("+iNum+")").addClass("yy_two_son_list_100");
					if($(this).html()==''){
						$(this).html('新建预约');
					}
				},function(){
					var iNum = $(this).parents(".yy_two_son_list").find("li").index($(this)[0])+1; 
					$(".yy_two_son_list li:nth-child("+iNum+")").removeClass("yy_two_son_list_100");
					if($(this).html()=='新建预约'){
						$(this).html('');
					}
				});	
			  
			  	
				$(".yy_two_son_list").hover(function(){
					$(this).css({"border-bottom":"1px solid #9bdcc1","background":"#f7ffef"});
					$(this).prev(".yy_two_son_list").css({"border-bottom":"1px solid #9bdcc1"});
				},function(){
					$(this).css({"border-bottom":"1px solid #dddddd","background":"none"});
					$(this).prev(".yy_two_son_list").css({"border-bottom":"1px solid #dddddd"});
				});
			}

			$(window).resize(function(){
				initTable();
		    });

            
			initTable();
            meeting();
			titlefixed();//表头固定
			invital();//鼠标经过 行列 变色

        },
        
    };

}();



// $(function(){
// 	var titlex = $(".yy_two_son_title").offset().top;
// 	var outwidth = $(".yy_two_son").width();
// 	var i = 0;
// 	var yylie = $(".yy_two_son_title ul li").length;
// 	if(yylie * 101 + 30<outwidth){
// 		var yyliewid = outwidth;
// 	}else{
// 		var yyliewid = yylie * 101 + 30 ;
// 	}

// 	$(".yy_two_son_con").css({"width":yyliewid}); //

// 	$(".yy_two_son_title").css({"width":yyliewid});
// 	if(yylie * 101 + 30<outwidth){
// 		$(".yy_two_son_list ul li").css({"width":(yyliewid-30)/yylie}); //
// 		$(".yy_two_son_title ul li").css({"width":(yyliewid-30)/yylie});
// 	}
//     $(window).resize(function(){
// 		i = 0;
// 		outwidth = $(".yy_two_son").width();
// 		$(".yy_two_son_con").animate({left: 0});
//     });
	

// 	meeting();
// 	invital();//鼠标经过 行列 变色
// 	titlefixed();//表头固定
   
   
// })

// function meeting(){  
// 	$(".pre").click(function(){
// 		if(outwidth < yyliewid ){
// 			var morenu = Math.round((yyliewid - outwidth)/101);
// 			i <morenu ? i++ : morenu ;
// 			$(".yy_two_son_con").animate({left: -i*101},200);
// 		}
// 	});	
// 	i;
// 	$(".next").click(function(){	
// 		if(outwidth < yyliewid ){
// 			i >= 1 ? i-- : 0;
// 			$(".yy_two_son_con").animate({left: -i*101},200);	
// 		} 
// 	});	
// }
// //表头固定
// function titlefixed(){
// 	$(window).scroll(function(){
// 		if($(window).scrollTop() >= titlex){
// 			$(".zwtitlefa").css({"position":"fixed","top":"0"})
// 			$(".yy_two_son_con_l_title").css({"position":"fixed","top":"0",})
// 			$(".yy_two_son01").css({"position":"fixed","top":"0"})
// 		}else{
// 			$(".zwtitlefa").css({"position":"static"});
// 			$(".yy_two_son_con_l_title").css({"position":"static"});
// 			$(".yy_two_son01").css({"position":"absolute"});
// 		}
// 	});
// }

// //鼠标经过 行列 变色
// function invital(){
// 	$(".yy_two_son_list li").hover(function(){
// 		var iNum = $(this).parents(".yy_two_son_list").find("li").index($(this)[0])+1; 
// 		//console.log($(this)[0]);
// 		$(".yy_two_son_list li:nth-child("+iNum+")").addClass("yy_two_son_list_100");
// 		if($(this).html()==''){
// 		$(this).html('新建预约');
// 		}
// 	},function(){
// 		var iNum = $(this).parents(".yy_two_son_list").find("li").index($(this)[0])+1; 
// 		$(".yy_two_son_list li:nth-child("+iNum+")").removeClass("yy_two_son_list_100");
// 		if($(this).html()=='新建预约'){
// 		$(this).html('');
// 		}
// 	});	
  
//   	// 
// 	$(".yy_two_son_list").hover(function(){
// 		$(this).css({"border-bottom":"1px solid #9bdcc1","background":"#f7ffef"});
// 		$(this).prev(".yy_two_son_list").css({"border-bottom":"1px solid #9bdcc1"});
// 	},function(){
// 		$(this).css({"border-bottom":"1px solid #dddddd","background":"none"});
// 		$(this).prev(".yy_two_son_list").css({"border-bottom":"1px solid #dddddd"});
// 	});
// }