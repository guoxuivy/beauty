// 使用实例 guox整理  广告js
// 
/*
<script id="adLayer" type="text/javascript" src="<?php echo $this->basePath('public/js/King_Chance_Layer.js');?>" data="<?php echo $themePath;?>"></script>
*/
var basePath = document.getElementById('adLayer').getAttribute('data');


document.writeln('<div class="King_Chance_Layer">');
document.writeln('<div class="King_Chance_LayerCont" style="display:none;">');
document.writeln('<div class="King_Chance_Layer_Close"><span>X</span>关闭</div>');
document.writeln('<img src="'+basePath+'/images/buttom.jpg" width="400" height="300" />');
document.writeln('</div>');
document.writeln('</div>');

$(document).ready(function(){
	var $King_Chance_LayerCont = $(".King_Chance_LayerCont");
	var $King_Chance_Layer_Close = $(".King_Chance_Layer_Close");
	var $King_Chance_Layer_Btn = $(".King_Chance_Layer_Btn > ul > li");
	var $King_Chance_Layer_Content = $(".King_Chance_Layer_Content > ul > li");
	var King_Chance_Layer_Btn_Hover = "hover";
	var King_Chance_Layer_Show_Num = 0;
	var King_Chance_Layer_Btn_Len = $King_Chance_Layer_Btn.length;
	$King_Chance_LayerCont.slideDown();
	
	$King_Chance_Layer_Close.click(function(){
		$King_Chance_LayerCont.slideUp();
	});
});

var lastScrollY = 0;
function heartBeat(){
	var diffY;
	if (document.documentElement && document.documentElement.scrollTop)
		diffY = document.documentElement.scrollTop;
	else if (document.body)
		diffY = document.body.scrollTop
	else{/*Netscape stuff*/}
	//alert(diffY);
	percent=.1*(diffY-lastScrollY);
	if(percent>0)
		percent=Math.ceil(percent);
	else 
		percent=Math.floor(percent);
	document.getElementById("leftDiv").style.top = parseInt(document.getElementById("leftDiv").style.top)+percent+"px";
	document.getElementById("rightDiv").style.top = parseInt(document.getElementById("rightDiv").style.top)+percent+"px";
	lastScrollY=lastScrollY+percent;
	//alert(lastScrollY);
}
//下面这段删除后，对联将不跟随屏幕而移动。
window.setInterval("heartBeat()",10);
//-->
//关闭按钮
function close_left1(){
    left1.style.visibility='hidden';
	document.getElementById("leftDiv").style.display='none';
}

function close_right1(){
    right1.style.visibility='hidden';
	document.getElementById("rightDiv").style.display='none';
}

//显示样式
document.writeln("<style type=\"text\/css\">");
document.writeln("#leftDiv,#rightDiv{width:100px;height:100px;background-color:#fff;position:absolute;}");
document.writeln(".itemFloat{width:100px;height:auto;line-height:5px}");
document.writeln("<\/style>");
//以下为主要内容
document.writeln("<div id=\"leftDiv\" style=\"top:100px;left:10px\">");
//------左侧各块开始
//---L1
document.writeln("<div id=\"left1\" class=\"itemFloat\">");
document.writeln("<embed src="+basePath+"/images/left_img.jpg width=100 height=300></embed>");
document.writeln("<br><a style=\"display:block; text-decoration:none; background-color:#999;height:22px;line-height:22px;font-size:12px; color:#fff;\" href=\"javascript:close_left1();\" title=\"关闭上面的广告\">关闭<\/a><br><br><br><br>");
document.writeln("<\/div>");

//------左侧各块结束
document.writeln("<\/div>");
document.writeln("<div id=\"rightDiv\" style=\"top:100px;right:10px\">");
//------右侧各块结束
//---R1
document.writeln("<div id=\"right1\" class=\"itemFloat\">");
document.writeln("<embed src="+basePath+"/images/left_img.jpg width=100 height=300></embed>");
document.writeln("<br><a style=\"display:block; text-decoration:none; background-color:#999;height:22px;line-height:22px;font-size:12px; color:#fff;\" href=\"javascript:close_right1();\" title=\"关闭上面的广告\">关闭<\/a><br><br><br><br>");
document.writeln("<\/div>");

//------右侧各块结束
document.writeln("<\/div>");


