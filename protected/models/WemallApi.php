<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-03-11 14:45:13
 */
/**
 * 微信接口
 */
use Ivy\core\ActiveRecord;
class WemallApi extends ActiveRecord
{
	//关闭缓存
	protected $_cache = false;
	public function tableName() {
		return 'wemall_api';
	}
	/**
	 * 发送到微信平台
	 */
	public static function send($do,$param=array())
	{
		$wemall_url=\Ivy::app()->C('wemall_url');
		if (empty($wemall_url))throw new CException("未设置微信平台URL");
		$model=new WemallApi;
		$model->action_url=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$model->url=$wemall_url.'&do='.$do;
		$model->do=$do;
		$model->param=json_encode($param);
		$model->action_time=time();
		$back=$model->curlrequest($model->url,$param);
		$model->back=$model->dataserialize($back);
		$model->back_time=time();
		$model->create_user=(int)\Ivy::app()->user->id;
		$model->save();
		return json_decode($back,true);
	}
	public function dataserialize($data)
	{
		if(is_null(json_decode($data)))
			return $data;
		return serialize(json_decode($data,true));
	}
	/**
	 * 发送文本消息
	 * @param  [type] $com_id  [description]
	 * @param  [type] $user_id [description]
	 * @param  [type] $text    [description]
	 * @return [type]          [description]
	 */
	public static function sendText($com_id,$user_id,$text)
	{
		if ($com_id && $user_id && $text) {
			return self::send('SendUser',array('data'=>self::base64_json(array('com_id'=>$com_id,'user_id'=>$user_id,'text'=>$text))));
		}
		return false;
	}
	/**
	 * 发送图文消息
	 * @param  [type] $com_id  [description]
	 * @param  [type] $user_id [description]
	 * @param  [type] $text    [description]
	 * @return [type]          [description]
	 */
	public static function sendNew($com_id,$user_id,$title,$text,$murl,$picurl,$do='')
	{
		if ($com_id && $user_id && $text) {
			$picurl=self::getHostInfo().SITE_URL.'/public/images/'.$picurl;
			return self::send('SendUser',array('data'=>self::base64_json(array(
				'com_id'=>$com_id,
				'user_id'=>$user_id,
				'do'=>$do,
				'articles'=>array(
						'title' => $title,
						'description' => $text,
						'murl' => $murl,
						'picurl' => $picurl,
					),
				))));
		}
		return false;
	}
	/**
	 * 获取host信息
	 * @return string
	 */
	public static function getHostInfo()
	{
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
	private static function base64_json($_data)
	{
		return base64_encode(json_encode($_data));
	}
	public function curlrequest($url,$data,$method='POST', $timeout = 60){
		// $name=$_POST['name'];
		// $pass=$_POST['pass'];
		// $http_authorization=base64_encode("1qasw2".$name.":".$pass."34sasa");
		$header = array();
		$header[] = "X-HTTP-Method-Override: $method";
		// $header[] = "HTTP_AUTHORIZATION: $http_authorization";
		// $header[] = "AUTHORIZATION: $http_authorization";
		$ch = curl_init(); //初始化CURL句柄 
		curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式

		if ($data) {
			if (is_array($data)) {
				$data = http_build_query($data);
			}
			curl_setopt($ch, CURLOPT_POST, 1);

			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的字符串
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);//设置HTTP头信息
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$document = curl_exec($ch);//执行预定义的CURL 
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($errno || empty($data)) {
			return $error;
		}
		return $document;
	}
	
}	