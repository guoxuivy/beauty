<?php
/**
 * @Author: K
 * @Date:   2015-08-24 16:43:50
 * @Last Modified by:   Administrator
 * @Last Modified time: 2015-08-24 16:44:01
 */
use Ivy\core\ActiveRecord;
class PracticeEffectUser extends ActiveRecord
{

	public function tableName() {
		return 'practice_effect_user';
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