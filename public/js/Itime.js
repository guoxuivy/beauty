/**
 * @author ivy <guoxuivy@gmail.com>
 * @copyright Copyright &copy; 2013-2017 Ivy Software LLC
 * @license https://github.com/guoxuivy/ivy/
 * @package framework
 * @link https://github.com/guoxuivy/ivy 
 * @since 1.0 
 *
 *报表时间插件  基于jquery 封装
 *


使用示例
var d = Itime({
		content:$('#test'),
		begin:'20140601',
		end:'20150723',
		type:'m',  //默认模式 年 月 日 对应 y m d
	});



/**
 * 报表日期选择插件
 * @param  {[type]} $     [description]
 * @param  {[type]} win   [description]
 * @param  {[type]} dom   [description]
 * @param  {[type]} undef [description]
 * @return {[type]}       [description]
 */
(function($,win,dom,undef){

	var template  = '<div class="itime"><a rel="d" href="javascript:">日报</a>';
		template += '<a rel="m" href="javascript:">月报</a>';
		template += '<a rel="y" href="javascript:">年报</a><br/>';
		template += '<select class="b_y"></select>';
		template += '<select class="b_m"></select>';
		template += '<select class="b_d"></select>';
		template += '<div class="itime_end"><span>至</span>';
		template += '<select class="e_y"></select>';
		template += '<select class="e_m"></select>';
		template += '<select class="e_d"></select></div></div>';


	var Itime=function(settings){
		this.settings=$.extend({},Itime.defaults,settings);
		this._self=$(template);
		//执行
		this.run=function(){
			this.show();
			this.bind();
			//渲染后内容初始化
			this.init();
			return this;
		}
	};

	/**
	 * 弹出框 默认配置 可扩展
	 * @type {Object} 默认日选择
	 * @endoff {Object} 默认开启end
	 */
	Itime.defaults={
		content:'系统错误',
		type:'d',
		endoff:false,
		init:function(){}
	};

	Itime.prototype={
		show:function(){
			var obj=this;
			var _self=this._self;
			if(this.settings.endoff)
				_self.find(".itime_end").remove();
			
			if(this.settings.content instanceof $){
				this.settings.content.html(_self)
			}else{
				alert("content must be jquery obj")
			}
			obj.buidOpt("b");
			obj.buidOpt("e");
			
		},

		//年月日切换绑定
		bind:function(){
			var obj=this;
			var _self=this._self;
			//年 月 日 
			_self.find('a').click(function(){
				_self.find(".changa").removeClass("changa");
				$(this).addClass("changa");
				var t=$(this).attr("rel");
				switch(t){
					case "y":
						_self.find(".b_m ,.b_d,.e_m ,.e_d").hide();
						_self.find(".b_y,.e_y").show();
						break;
					case "m":
						_self.find(".b_d,.e_d").hide();
						_self.find(".b_y ,.b_m,.e_y ,.e_m").show();
						break;
					case "d":
						_self.find(".b_y ,.b_m ,.b_d,.e_y ,.e_m ,.e_d").show();
						break;
				}
			});

			//默认日
			_self.find("a[rel='"+this.settings.type+"']").trigger("click");
			
		},
		//内容初始化回调
		init:function(){
			var obj=this;
			return obj.settings.init(obj._self);
		},
		//内容初始化回调
		buidOpt:function(p){
			var obj=this;
			var _self=this._self;
			var myDate = new Date();
			var y = myDate.getFullYear();    //获取完整的年份(4位)
			var s_y=y;						//选中年份
			var m = myDate.getMonth()+1;   
				m = m<10?"0"+m:m; 
			var d = myDate.getDate(); 
				d = d<10?"0"+d:d;

			if(p=="b")
				var conf=this.settings.begin;
			else
				var conf=this.settings.end;

			//有预制量
			if(conf!=undefined){
				s_y = conf.substr(0, 4);
				m = conf.substr(4, 2);
				d = conf.substr(6, 2);
			}

			var opt_y =	"";
			for (var i = 0; i <= 4; i++) {
				var show_y=y-i;
				if(s_y==show_y)
					opt_y += "<option value='"+show_y+"' selected='selected'>"+show_y+"年</option>";
				else
					opt_y += "<option value='"+show_y+"'>"+show_y+"年</option>";
			};
			var opt_m =	"";
			for (var i = 1; i <= 12; i++) {
				var s_m = i<10?"0"+i:i;
				if(s_m==m)
					opt_m += "<option value='"+s_m+"' selected='selected'>"+s_m+"月</option>";
				else
					opt_m += "<option value='"+s_m+"'>"+s_m+"月</option>";
			};
			var opt_d =	"";
			for (var i = 1; i <= 31; i++) {
				var s_d = i<10?"0"+i:i;
				if(s_d==d)
					opt_d += "<option value='"+s_d+"' selected='selected'>"+s_d+"日</option>";
				else
					opt_d += "<option value='"+s_d+"'>"+s_d+"日</option>";
			};

			_self.find("."+p+"_y").html(opt_y);
			_self.find("."+p+"_m").html(opt_m);
			_self.find("."+p+"_d").html(opt_d);
		}
		
	};


	win['Itime']=function(settings){
		var itime=new Itime(settings);
		return itime.run();
	};

})(jQuery,window,document);