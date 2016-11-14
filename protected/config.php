<?php
/**
 * @author ivy;
 */
return array (
		//pdo数据库配置
		'db_pdo' => array (
			'dsn' => 'mysql:dbname=etwasd;host=localhost;port=3306',
			'user' => 'etwasd',
			'password' => '8y3j5m4l',
			//'dsn' => 'mysql:dbname=platform;host=127.0.0.1;port=3306',
			//'password' => '',
			
			//从库读取数据 用户名密码保存请保持一致
			// 'slave' = >array(
			// 	'mysql:dbname=platform;host=192.168.0.1;port=3306',
			// 	'mysql:dbname=platform;host=192.168.0.2;port=3306',
			// 	'mysql:dbname=platform;host=192.168.0.3;port=3306',
			// ),
			
			'profile' => true, //开启sql执行性能显示（debug模式下生效）
			'ARcache' => false, //开启 ActiveRecord (sql查询)缓存 需要配合开启cache使用
			'ARcacheTime' => 3600, //ARcache 缓存过期时间 默认1小时
		),
		//支持memcache集群
		'memcache'=>array(
			//"192.168.0.202:11211",
		),
		
		//默认路由
		'route' => array(
			'controller' =>'index',
			'action'	=> 'index',
		),
		//友好错误显示页面
		'errorHandler'=>array(
			'errorAction'=>'error/index',
		),
		//开启表单令牌
		'token'=>true,

		/******************************以上为系统框架配置**************************************/


		//供应商数据库配置
		'supplier_pdo' => array (
			'dsn' => 'mysql:dbname=platform_supplier;host=192.168.0.202;port=13306',
			'password' => 'mysqladmin56',
			'user' => 'root',
		),



		'supplier_url'=>'http://localhost/beauty_supplier/',//供应商登录地址
		'beauty_url'=>'http://localhost/beauty_admin/',//美容院登录地址

		'wemall_url'=>'http://xyq6nyurcv.proxy.qqbrowser.cc/wemall/web/meihuiApi.php?c=site&a=entry&m=meihui_tool',//无do
		/******************************以上为微信配置**************************************/
		'smsConfig'=>array(
			'accountSid'=>'aaf98f895219cd5501521a953dde0225',//主帐号
			'accountToken'=>'305b169a45184014ac4189b3f8dd415e',//主账号TOKEN
			'appId'=>'8a48b551522ff931015230192c6b00bc',//应用Id
			'serverIP'=>'app.cloopen.com',//请求地址，格式如下，不需要写https://
			'serverPort'=>'8883',//端口
			'verifyTempId'=>'61887',//验证短信(忘记密码)模版ID 【meihui平台】尊敬的{1}用户,您本次的验证码是{2},{3}分钟内有效.如非本人操作,请忽略本条信息.
			'TempId1'=>'61886',//用户成功缴费通知 【meihui平台】尊敬的{1},您的系统服务费已缴纳成功,谢谢!如有疑问请拨打客服电话:027-87050575
			'TempId2'=>'61869',//注册审核失败通知 【meihui平台】尊敬的{1},您的验证信息未通过.如有疑问请拨打客服电话:027-87050575
			'TempId3'=>'61856',//注册审核成功通知 【meihui平台】尊敬的{1},您的注册申请已通过审核,登陆地址为:http//:www.etwasd.com
			'TempId4'=>'63338',//试用到期通知 【meihui平台】尊敬的{1},您的系统试用期还剩{2}天.需继续使用请联系027-87050575
			'TempId5'=>'63339',//服务费将到期通知 【meihui平台】尊敬的{1},您的系统服务费即将到期,请尽快联系客服缴纳027-87050575
			'TempId6'=>'63340',//服务费已到期系统停用通知 【meihui平台】尊敬的{1},您的系统将于{2}天后到期,届时系统将停止服务.
			'TempId7'=>'63341',//忘记密码手机号回执验证 【meihui平台】尊敬的{1},您的密码重置验证码为{2},{3}分钟之内有效.
			),
		
		
);
