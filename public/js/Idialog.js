/**
 * @author ivy <guoxuivy@gmail.com>
 * @copyright Copyright &copy; 2013-2017 Ivy Software LLC
 * @license https://github.com/guoxuivy/ivy/
 * @package framework
 * @link https://github.com/guoxuivy/ivy 
 * @since 1.0 
 *
 *对话框插件 Idialog 基于jquery 封装
 *
 *
 *
 *
 *
 需要样式
.idialog{ overflow:hidden; position:fixed; left:50%; top:200px;  display:none; z-index:999;}
.idialog_title{ height:30px; background:#07aaff; padding-left:20px; line-height:30px;font-family:"微软雅黑"; font-size:14px; color:#ffffff;}
.idialog_title span{ float:right; display:inline; margin-right:5px; height:30px; width:30px; text-align:center; cursor:pointer;}
.idialog_body{  overflow:hidden;font-family:"微软雅黑"; font-size:12px; color:#666666; background:#FFF; border-left:1px solid #dddddd; border-right:1px solid #dddddd; border-bottom:1px solid #dddddd;}
.idialog_content{padding:20px 30px 5px 5px;}
.idialog_active{ height:60px; background:#fafafa; width:100%;border-top:1px solid #dddddd; text-align:right;}
.idialog_active a{ display:inline-block; height:28px; width:80px; border:1px solid #cccccc; border-radius:5px; margin:0 5px;font-family:"微软雅黑"; font-size:12px; color:#666666; text-align:center; line-height:28px; margin-top:16px;}


<div class="modal fade" id="portlet-config" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">修改密码</h4>
            </div>
            <div class="modal-body">
		      
            </div>
            <div class="modal-footer">
                <button type="button" class="btn blue save-portlet">Save changes</button>
                <button type="button" class="btn default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- responsive -->
<div id="responsive" class="modal fade" tabindex="-1" data-width="760">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
		<h4 class="modal-title">Responsive</h4>
	</div>
	<div class="modal-body">
		<div class="row">
			<div class="col-md-3">
				
				<p>
					<input class="form-control" type="text">
				</p>
			</div>
			<div class="col-md-9">
				<h4>Some More Input</h4>
				<p>
					<input class="form-control" type="text">
				</p>
		
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" data-dismiss="modal" class="btn btn-default">Close</button>
		<button type="button" class="btn blue">Save changes</button>
	</div>
</div>
<!-- stackable -->



使用示例
var d = Idialog({
	top:100,
	width:500,
	content:$('#test'),
	init:function(body){
		//console.log(body);
	},
	ok:function(obj){
		//console.log(obj);
		return false;
	},
	cancel:true
});

 */
(function($,win,dom,undef){

	// var template  = '<div class="idialog" style="display: block;">';
	// 	template += '	<div class="idialog_title"><font></font><span></span></div>';
	// 	template += '	<div class="idialog_body">';
	// 	template += '		<div class="idialog_content"></div>';
	// 	template += '		<div class="idialog_active"><a class="idialog_cancel" href="javascript:">取消</a><a href="javascript:" class="idialog_ok">确定</a></div>';
	// 	template += '	</div>';
	// 	template += '</div>';


	var template  = '<div class="idialog modal fade " tabindex="-1" aria-hidden="true">';
		template += '	<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button><h4 class="modal-title">Title</h4></div>';
		template += '	<div class="idialog_body modal-body">';
		template += '		<div class="idialog_content"></div>';
		template += '		<div class="idialog_active modal-footer"><button type="button" data-dismiss="modal" class="idialog_cancel btn default">取消</button><button type="button" class="idialog_ok btn blue">确定</button></div>';
		template += '	</div>';
		template += '</div>';



	var Idialog=function(settings){
		this.settings=$.extend({},Idialog.defaults,settings);
		this._self=$(template);
		//执行
		this.run=function(){
			this.bind();
			this.show();
			//渲染后内容初始化
			this.init();
			return this;
		}
	};

	/**
	 * 弹出框 默认配置 可扩展
	 * @type {Object}
	 */
	Idialog.defaults={
		top:200,
		width:400,
		title:'通知',
		content:'系统错误',
		ok:true,
		cancel:true,
		init:function(){},
		close:function(){}
	};

	Idialog.prototype={
		show:function(){
			var obj=this;
			var _self=this._self;


			_self.find('.modal-title').html(this.settings.title);

			if(this.settings.width){
				_self.attr("data-width",this.settings.width);
			}

			if(this.settings.title===false){
				_self.find('.modal-header').remove();
			}

			if(this.settings.content instanceof $){
				_self.find('.idialog_content').html(this.settings.content.html());
			}else{
				_self.find('.idialog_content').html(this.settings.content);
			}

			if(this.settings.ok===false){
				_self.find('.idialog_ok').remove();
			}
			if(this.settings.cancel===false){
				_self.find('.idialog_cancel').remove();
			}

			_self.modal();
		},

		//对话框本身事件绑定
		bind:function(){
			var obj=this;
			var _self=this._self;
		
			//确定按钮回调
			_self.find('.idialog_ok').click(function(){
				var res = true;
				if(typeof(obj.settings.ok) == 'function'){
					var r = obj.settings.ok(obj);
					if(r===false) res=false;
				}
				if(res){
					obj.close();
				}
				
			});
		},
		//内容初始化回调
		init:function(){
			var obj=this;
			return obj.settings.init(obj._self.find('.idialog_content'));
		},
		//关闭弹窗回调
		close:function(){
			var obj=this;
			obj.settings.close(obj._self.find('.idialog_content'))
			obj._self.remove();
		},
	};

	/**
	 * 弹出框  气泡形式
	 * @type {Object}
	 */
	Idialog.tips=function(msg,time){
		if(time==undefined) time=2;
		var tips=$('<div class="idialog_tips">'+msg+'</div>');
		tips.appendTo("body");
		tips.css('margin-left','-'+tips.width()/2+'px');
		var close = arguments[2] ? arguments[2] : function(){};
		setTimeout(function() {
			tips.remove();
			close();
		},time*1000);
	};

	win['Idialog']=function(settings){
		var dialog=new Idialog(settings);
		return dialog.run();
	};
	win['Idialog']['tips']=Idialog.tips;


})(jQuery,window,document);