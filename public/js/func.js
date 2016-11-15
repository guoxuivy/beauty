//抖动插件
$.fn.shake = function(intShakes /*Amount of shakes*/, intDistance /*Shake distance*/, intDuration /*Time duration*/) {
    this.each(function() {
        var jqNode = $(this);
        jqNode.css({position:'relative'});
        for (var x=1; x<=intShakes; x++) {
            jqNode.animate({ left: (intDistance * -1) },(((intDuration / intShakes) / 4)))
            .animate({ left: intDistance },((intDuration/intShakes)/2))
            .animate({ left: 0 },(((intDuration/intShakes)/4)));
        }
    });
    return this;
};

/**
 * 统一的问号弹出提示
 */
$.fn.help = function(){
    this.each(function(){
        var exp = $(this).attr('explain');
        $(this).html('<div class="help_ms"><div class="help_icon"></div><span class="help_ti">'+exp+'</span></div>');
    });
    this.hover(
        function(){
            $(".help_ms").hide();
            $(this).find(".help_ms").show();
        },function(){
            $(this).find(".help_ms").hide();
    });
    return this;

};
$(function(){
    $('.help').help();
});


//cookie操作扩展
$.extend({
    /**
     1. 设置cookie的值，把name变量的值设为value  
    example $.cookie(’name’, ‘value’);
     2.新建一个cookie 包括有效期 路径 域名等
    example $.cookie(’name’, ‘value’, {expires: 7, path: ‘/’, domain: ‘jquery.com’, secure: true});
    3.新建cookie
    example $.cookie(’name’, ‘value’);
    4.删除一个cookie
    example $.cookie(’name’, null);
    5.取一个cookie(name)值给myvar
    var account= $.cookie('name');
    **/
    cookie: function(name, value, options) {
        if (typeof value != 'undefined') { // name and value given, set cookie
            options = options || {};
            if (value === null) {
                value = '';
                options.expires = -1;
            }
            var expires = '';
            if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
                var date;
                if (typeof options.expires == 'number') {
                    date = new Date();
                    date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
                } else {
                    date = options.expires;
                }
                expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
            }
            var path = options.path ? '; path=' + options.path : '';
            var domain = options.domain ? '; domain=' + options.domain : '';
            var secure = options.secure ? '; secure' : '';
            document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        } else { // only name given, get cookie
            var cookieValue = null;
            if (document.cookie && document.cookie != '') {
                var cookies = document.cookie.split(';');
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = jQuery.trim(cookies[i]);
                    // Does this cookie string begin with the name we want?
                    if (cookie.substring(0, name.length + 1) == (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    }
 
}); 



/*公共函数库*/
//URL替换
function changeURL(url, ref, value) {  
	if(url=='')
		url=window.location.href;
    var str = "";
    if (url.indexOf('?') != -1)  
        str = url.substr(url.indexOf('?') + 1);  
    else  
        return url + "?" + ref + "=" + value;  
    
    var returnurl = "";  
    var setparam = "";  
    var arr;  
    var modify = "0";  
    if (str.indexOf('&') != -1) {  
        arr = str.split('&');  

        for (i in arr) {  
            if (arr[i].split('=')[0] == ref) {  
                setparam = value;  
                modify = "1";  
            }  
            else {  
                setparam = arr[i].split('=')[1];  
            }  
            returnurl = returnurl + arr[i].split('=')[0] + "=" + setparam + "&";  
        }  
        returnurl = returnurl.substr(0, returnurl.length - 1);  
        if (modify == "0")  
            if (returnurl == str)  
                returnurl = returnurl + "&" + ref + "=" + value;  
    }  
    else {    
        if (str.indexOf('=') != -1) {  
            arr = str.split('=');  
  
            if (arr[0] == ref) {  
                setparam = value;  
                modify = "1";  
            }  
            else {  
                setparam = arr[1];  
            }  
            returnurl = arr[0] + "=" + setparam;  
            if (modify == "0")  
                if (returnurl == str)  
                    returnurl = returnurl + "&" + ref + "=" + value;  
        }  
        else  
            returnurl = ref + "=" + value;  
    }  
    return url.substr(0, url.indexOf('?')) + "?" + returnurl;  
}

//删除参数值
function delQueStr(url, ref) {
    var str = "";
    if (url.indexOf('?') != -1) {
        str = url.substr(url.indexOf('?') + 1);
    }
    else {
        return url;
    }
    var arr = "";
    var returnurl = "";
    var setparam = "";
    if (str.indexOf('&') != -1) {
        arr = str.split('&');
        for (i in arr) {
            if (arr[i].split('=')[0] != ref) {
                returnurl = returnurl + arr[i].split('=')[0] + "=" + arr[i].split('=')[1] + "&";
            }
        }
        return url.substr(0, url.indexOf('?')) + "?" + returnurl.substr(0, returnurl.length - 1);
    }
    else {
        arr = str.split('=');
        if (arr[0] == ref) {
            return url.substr(0, url.indexOf('?'));
        }
        else {
            return url;
        }
    }
}
//获取当前url中的参数
function getUrlParam(name) {
    var search = decodeURI(window.location.search);
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r =search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]); return null; //返回参数值
}

function to_url(url){
    window.location.href=url;
}

//table 查询 封装
$(function(){
    if($.cookie('show_search')=='true'){
        $('.table_search').show();
        $('.filter_search').parent().addClass('button_visit');
    };
    if($.cookie('show_search')=='false'){
        $('.table_search').hide();
        $('.filter_search').parent().removeClass('button_visit');
    };
     //search显示相关
    $('.filter_search').click(function(){
        var o_v=$(".table_search").is(':visible');
        $(".table_search").toggle();
        $.cookie('show_search',!o_v);
        if (o_v) {
            $('.filter_search').parent().removeClass('button_visit');
        }else
        {
            $('.filter_search').parent().addClass('button_visit');
        }
    });
    //表头title
    $('.table_title>th').each(function(index, el) {
        if(typeof($(this).attr('title'))=="undefined")
            $(this).attr('title', $.trim($(this).text()));
    });
    //表身title
    $('.table_list>td').each(function(index, el) {
        if(typeof($(this).attr('title'))=="undefined")
            $(this).attr('title', $(this).text());
    });

    //分页相关 
    //分页-跳转到
    $(document).on('click','.js_go', function(event) {
        event.preventDefault();
        var page=parseInt($(this).siblings('.js_topage').val());
        var tag=$(this).attr('tag');
        if(page>0)
            window.location.href=changeURL('',tag,page);
    });
    //分页-每页多少
    $(document).on('change','.js_row', function(event) {
        event.preventDefault();
        var row=$(this).val();
        window.location.href=changeURL('','row',row);
    });
    //分页相关 end！！！！！！！！！！！！！！！！！！！！！！！！！

   
    //表格查询事件绑定
    $('.table_search').find('select').change(function(){
        var search_uri = searchToUrl();
        if(window.location.href!=search_uri)
            window.location.href=search_uri;
    });
    $('.table_search').find('input').blur(function(){
        var search_uri = searchToUrl();
        if(window.location.href!=search_uri)
            window.location.href=search_uri;
    });
    
    //查询默认信息初始化
    $('.table_search').find('select').each(function(){
        var val=getUriVal($(this).attr('data-col'));
        $(this).val(val);
    });

    $('.table_search').find('input').each(function(){
        var val=getUriVal($(this).attr('data-col'));
        if(val!=null){
            val=val.replace(/(^%+)|(%+$)/g,"");//去掉前后%
            $(this).val(val);
        }
    });
    
    //获取url中的列值
    function getUriVal(dataCol){
        var col='t_search\\['+dataCol+'\\]';
        return getUrlParam(col);
    }
    //将查询列构造url
    function searchToUrl(url){
        url = arguments[0] ? arguments[0] : window.location.href;
        url=changeURL(url,'page',1);//重置分页
        var cols=getSearchCol();
        $.each(cols,function(n,col){
            if(col.v==''){
                url=delQueStr(url,'t_search['+col.k+']');
            }else{
                url=changeURL(url,'t_search['+col.k+']', encodeURI(col.v));
            }
        });
        return url;
    }

    //收集查询列信息
    function getSearchCol(){
        var cols=[];
        var inputs=$('.table_search :input');
        var isnull=function(col){
            col=$(col);
            if(typeof(col.attr('data-col'))=="undefined"){
                return true;
            }
            if(col.val()==null){
                return true;
            }
            return false;
        }
        $.each( inputs, function(n,col){
            if(!isnull(col)){
                var k=$(col).attr('data-col');
                var v=$.trim($(col).val());
                if(typeof($(col).attr("nolike"))=="undefined" && $(col).attr("type")=='text' && v!='')
                    v='%'+v+'%';
                cols.push({k:k,v:v});
            }
        });
        return cols;
    }

    //查询相关 end！！！！！！！！！！！！！！！！！！！！！！！！！
    //选择框
    $(".table_table").find(".table_check input[type='checkbox']").click(function(){
        var table_list=$(this).closest('table').find(".table_list");
        if($(this).is(':checked')){
            table_list.find('td:first').find("input[type='checkbox']").prop("checked",true);
        }else{
            table_list.find('td:first').find("input[type='checkbox']").removeAttr("checked");
        }
    });


    //客户姓名那块加上链接
    $('span[class="khgl_03"]').click(function(event) {
        /* Act on the event */
        var _href=$(this).parents('.table_list').find('.view').eq(0).attr('href');
        if (_href)
            window.location.href=$(this).parents('.table_list').find('.view').eq(0).attr('href');
    });

});







/**
 * 浮点数计算插件 兼容jquery html()和val()
 * @param  {[type]} intShakes [description]
 * @return {[type]}           [description]
 */
//浮点加
function accAdd(arg1,arg2){
    var r1,r2,m;
    try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}
    try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}
    m=Math.pow(10,Math.max(r1,r2))
    return (arg1*m+arg2*m)/m
}
//给Number类型增加一个add方法，调用起来更加方便。 
Number.prototype.add = function (arg){ 
    return accAdd(arg,this); 
}
String.prototype.add = function (arg){ 
    if(!isNaN(this)){
       return accAdd(arg,this); 
    }
}
//浮点减
function accSub(arg1,arg2){
    var r1,r2,m,n;
    try{r1=arg1.toString().split(".")[1].length}catch(e){r1=0}
    try{r2=arg2.toString().split(".")[1].length}catch(e){r2=0}
    m=Math.pow(10,Math.max(r1,r2));
    //last modify by deeka
    //动态控制精度长度
    n=(r1>=r2)?r1:r2;
    n=(n>20)?20:n;
    return ((arg1*m-arg2*m)/m).toFixed(n);
}
Number.prototype.sub = function (arg){ 
    return accSub(this,arg); 
}
String.prototype.sub = function (arg){ 
    if(!isNaN(this)){
       return accSub(this,arg); 
    }
}
//浮点乘
function accMul(arg1,arg2){ 
    var m=0,s1=arg1.toString(),s2=arg2.toString(); 
    try{m+=s1.split(".")[1].length}catch(e){} 
    try{m+=s2.split(".")[1].length}catch(e){} 
    return Number(s1.replace(".",""))*Number(s2.replace(".",""))/Math.pow(10,m) 
}
Number.prototype.mul = function (arg){ 
    return accMul(arg,this); 
}
String.prototype.mul = function (arg){ 
    if(!isNaN(this)){
       return accMul(arg,this); 
    }
}
//浮点除
function accDiv(arg1,arg2){ 
    var t1=0,t2=0,r1,r2; 
    try{t1=arg1.toString().split(".")[1].length}catch(e){} 
    try{t2=arg2.toString().split(".")[1].length}catch(e){} 
    with(Math){ 
        r1=Number(arg1.toString().replace(".","")) 
        r2=Number(arg2.toString().replace(".","")) 
        return (r1/r2)*pow(10,t2-t1); 
    } 
}
Number.prototype.div = function (arg){ 
    return accDiv(this,arg); 
}
String.prototype.div = function (arg){ 
    if(!isNaN(this)){
       return accDiv(this,arg); 
    }
}