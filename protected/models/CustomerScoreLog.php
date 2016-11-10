<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-07-31 17:56:34
 */
/**
 * 积分日志
 */
use Ivy\core\ActiveRecord;
class CustomerScoreLog extends ActiveRecord
{
	public function tableName() {
		return 'customer_score_log';
	}
	/**
	 * 积分日志总疗次
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getSumByUser($uid,$where=" AND pos_dir='out'")
	{
		$uid=(int)$uid;
		$_arr= self::model()
		->field(array("sum(`t`.`score`) as sum_score"))
		->where("t.cu_id={$uid} {$where}")
		->group("t.cu_id")
		->find();
		return $_arr;
	}
	/**
	 * 积分日志
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getListByUser($uid,$limit=6,$where="")
	{
		$uid=(int)$uid;
		$_arr= self::model()
		->field(array("*"))
		->where("t.cu_id={$uid} {$where}")
		->limit(0,$limit)
		->order('t.`create_time` DESC')
		->findAll();
		return $_arr;
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
}	