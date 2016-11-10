<?php
/**
 * 业绩目标设定
 */
use \Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class TargetMonth extends ActiveRecord
{
	public function tableName() {
		return 'target_month';
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




}	