<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-04-30 17:46:59
 */
/**
 * 员工职位
 */
use Ivy\core\ActiveRecord;
class EmployPositionInfo extends ActiveRecord
{
	public function tableName() {
		return 'employ_position';
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
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '正常',
			'0'		=> '停用',
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
	/**
	 * 获取数组 职位不区分公司
	 * @return array 格式array(id=>name)
	 */
	public static function getArray()
	{
		//$company_info=\ivy::app()->user->getState('company_info');
		//$comp_id=$company_info['id'];
		$Arr=array();
		$model=self::model()->findAll('id in (5,6,7,8)');
		if ($model) {
			foreach ($model as $val) {
				$Arr[$val['id']]=$val['position_name'];
			}
		}
		return $Arr;
	}

	/**
	 * 获取职位列表
	 */
	public static function getPositionList(){
		return \EmployPositionInfo::model()->findAll('id in (5,6,7,8)');
	}
}	