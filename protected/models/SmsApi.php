<?php
/**
 * @Author: K
 * @Date:   2016-1-12 14:15:00
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-03-11 14:45:41
 */
/**
 * 短信接口
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class SmsApi extends ActiveRecord
{
	//关闭缓存
	//protected $_cache = false;
	public function tableName() {
		return 'sms_api';
	}
	/**
	 * 发送到短信平台
	 * to（手机号码，多个以,隔开） dates（发送内容数组格式array（‘123’，‘10’）对应{1} {2}） tempId(模版ID对应短信中心后台模版)
	 */
	public static function send($to,$datas,$tempId=null)
	{
		$smsConfig=\Ivy::app()->C('smsConfig');
		if (empty($smsConfig))throw new CException("未设置短信设置");
		if (empty($tempId))
			$tempId=$smsConfig['verifyTempId'];
		$model=new SmsApi;
		$model->attributes=$smsConfig;
		$model->action_url=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$model->to=$to;
		$model->datas=serialize($datas);
		$model->tempId=$tempId;
		\Ivy::importExt("smsSDK/CCPRestSDK");
		$rest = new \REST($smsConfig['serverIP'],$smsConfig['serverPort']);
	    $rest->setAccount($smsConfig['accountSid'],$smsConfig['accountToken']);
	    $rest->setAppId($smsConfig['appId']);
	    //$result = 'test';
	    $result = $rest->sendTemplateSMS($to,$datas,$tempId);//发送
	    if($result == NULL)
	    	return false;
		$model->result=serialize($result);
		$model->create_user=(int)\Ivy::app()->user->id;
		return $model->save();
	}

	
}	