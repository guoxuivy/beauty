<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-04-06 17:03:44
 */
/**
 * 公司基本信息
 */
use Ivy\core\ActiveRecord;
class CompanyInfo extends ActiveRecord
{
	const FREE='标准版';
	const QUAN='全功能版';
	//关闭缓存
	protected $_cache = false;
	public function tableName() {
		return 'company_info';
	}
	/**
	 * 保存配置属性
	 * @param [type] $key   [description]
	 * @param [type] $value [description]
	 */
	public static function setConfig($key,$value)
	{
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$company_info=self::model()->findByPk($company);
		$config=unserialize($company_info->config);
		$config[$key]=$value;
		return self::model()->saveData(array('config'=>serialize($config)));
	}
	/**
	 * 获得配置属性
	 * @return [type] [description]
	 */
	public static function getConfig($key)
	{
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$company_info=self::model()->findByPk($company);
		$config=unserialize($company_info->config);
		if (!isset($config[$key])) {
			$config[$key]=self::getDefaultConfg($key);
		}
		return $config[$key];
	}
	/**
	 * 获得配置默认属性
	 * @param [type] $key [description]
	 */
	public static function getDefaultConfg($key=null)
	{
		$Array = array(
			'order_show'		=> 'true',//单据显示设置(true为显示,false为不显示)
			'score_config'		=>array(//积分配置
				'openAll'    =>'true',//积分总开关(true,false)
				'openBySale' =>'true',//购买获得积分
				'scoreFor'   =>'all',//积分范围('all'=>所有顾客,'1'=>仅限正式会员,'2'=>仅限非正式会员)
				'scoreBl'    =>1,//积分比例 消费金额1元=多少积分
				'openByGive' =>'true',//赠送获得积分
				),
			'payee'				=>array(//收款账户设置
				'money'=>array(
					'pre'  =>0,
					'open' =>'true',
					'name' =>'现金（现钞）',
					),
				'post'=>array(
					'pre'  =>1,//前缀
					'open' =>'false',//开关
					'name' =>'POS机',
					'has'  =>array(),//拥有的收账类型前缀'1'
					),
				'bank'=>array(
					'pre'  =>2,
					'open' =>'false',
					'name' =>'银行转账',
					'has'  =>array(),//拥有的收账类型前缀'2'
					),
				'other'=>array(
					'pre'  =>3,
					'open' =>'false',
					'name' =>'其它收款类型',
					'has'  =>array(),//拥有的收账类型前缀'3'
					),
				),
			'sale_access'		=>array(//销售权限设置
				'manager'   =>'true',//经理
				'counselor' =>'true',//顾问
				'operator'  =>'true',//美容师
				'reception' =>'true',//前台
				),
			'member_config'		=>30,//有效会员设置
			'wx_practice_msg'	=>'true',//微信实操单耗卡通知
		);
		if($key === null) {
			return $Array;
		}
		else {
			if(array_key_exists($key, $Array)) {
				return $Array[$key];
			}
			else {
				return false;
			}
		}
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		$this->attributes=$data;
		$this->last_time=time();
		return $this->save();
	}
	/**
	 *  更新状态(删除)
	 * @param  integer $status 
	 * @return 
	 */
	public function updateStatus($status=-1){
		$this->status=$status;
		return $this->update();
	}
	/**
	 * 状态的停用启用
	 * @return 
	 */
	public function updateUseStatus()
	{
		if(!in_array($this->status, array(1,0)))
			return false;
		$this->status=$this->status==0?1:0;
		return $this->updateStatus($this->status);
	}
	/**
	 * 积分范围
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getScoreFor($Pk=null){
		$Array = array(
			'all' => '所有顾客',
			'1'   => '仅限正式会员',
			'2'   => '仅限非正式会员',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}
			else {
				return false;
			}
		}
	}
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '<font color="#00DD00">使用中</font>',
			'9'		=> '<font color="#FFCC22">试用中</font>',
			'0'		=> '<font color="#666666">未审核</font>',
			'-1'	=> '<font color="red">停用</font>',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}

	/**
	 * 后台管理动作按钮
	 * @param  [type] $Pk [description]
	 * @return [type]     [description]
	 */
	public static function getAction($status=0){
		if($status==0)
			return "<a href='javascript:;' rel='1' class='check_code'>通过</a><a href='javascript:;' rel='0'  class='check_code' style='background-color: red;'>拒绝</a>";
		return "";
	}

	/**
	 * 后台管理动作按钮
	 * @param  [type] $Pk [description]
	 * @return [type]     [description]
	 */
	public static function getVersion(){
		$model = null;
		if(\Ivy::app()->user->comp_id)
			$model = \CompanyInfo::model()->findByPk(\Ivy::app()->user->comp_id);
		if(!$model)
			return "";
		if($model->status==9)
			$str = '';//<div class="head_shi">30天试用</div>
		if($model->has_invo=='N')
			$str.= '<div class="head_free">'.self::FREE.'</div>';
		if($model->has_invo=='Y')
			$str.= '<div class="head_quan">'.self::QUAN.'</div>';
		return $str;
	}

}	