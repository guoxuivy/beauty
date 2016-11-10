<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-07-28 18:40:36
 */
/**
 * 公司基本信息
 */
use Ivy\core\ActiveRecord;
class CompanyCapital extends ActiveRecord
{
	public function tableName() {
		return 'company_capital';
	}
	/**
	 * 获取资金账户
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public static function getList($where='',$res=false)
	{
		$where.=' AND status=1';
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$_arr= self::model()->findAll("comp_id={$company} {$where}");
		if ($res) {
			return $_arr;
		}
		$list=array();
		if($_arr)
			foreach ($_arr as $key => $value) {
				$list[$value['id']]=$value['name'];
			}
		return $list;
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
	 * 获取账户类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'1'		=> '现金',
			'2'		=> '赠送',
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
	 * 获取能否
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getYN($Pk=null){
		$Array = array(
			'1'		=> '能',
			'0'		=> '否',
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
			'1'		=> '正常',
			//'0'		=> '停用',
			'-1'	=> '删除',
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
	 * 逻辑删除
	 * @return 
	 */
	public function delete(){
		return $this->updateStatus();
	}
}	