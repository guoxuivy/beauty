var TableWidget = function () {

    var initTable = function (portlet_table) {
        var portlet = portlet_table;
        var table = portlet.find('table');

        var oTable = table.dataTable({
            "columnDefs": [{
                "orderable": false,
                "targets": [0]
            }],
            "order": [
                // [1, 'asc']
            ],
            "paging": false,
            "searching": false,
            "info": false,
            // set the initial value
        });

        var tableColumnToggler = portlet.find('.column_toggler');

        /* handle show/hide columns*/
        $('input[type="checkbox"]', tableColumnToggler).change(function () {
            /* Get the DataTables object again - this is not a recreation, just a get of the object */
            var iCol = parseInt($(this).attr("data-column"));
            var bVis = oTable.fnSettings().aoColumns[iCol].bVisible;
            oTable.fnSetColumnVis(iCol, (bVis ? false : true));
        });

        //批量选择
        table.find('.group-checkable').change(function () {
            var set = jQuery(this).attr("data-set");
            var checked = jQuery(this).is(":checked");
            jQuery(set).each(function () {
                if (checked) {
                    $(this).attr("checked", true);
                    $(this).parents('tr').addClass("active");
                } else {
                    $(this).attr("checked", false);
                    $(this).parents('tr').removeClass("active");
                }
            });
            jQuery.uniform.update(set);
        });

        table.on('change', 'tbody tr .checkboxes', function () {
            $(this).parents('tr').toggleClass("active");
        });

        // 下拉筛选
        portlet.on('click', '.table-search', function () {
            table.find('.table_search').toggleClass("none");
        });
    };

    //查询效果绑定
    var tableBuild = function(){
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
    };

    return {
        //main function to initiate the module
        init: function () {
            if (!jQuery().dataTable) {
                return;
            }

            $(".portlet-table").each(function(){
                initTable($(this));
            });
            tableBuild();

        }

    };

}();




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