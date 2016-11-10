<?php
/**
 * @Author: K
 * @Date:   2015-03-31 12:01:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-09-25 13:56:46
 */
use Ivy\core\ActiveRecord;
class PracticeOrderDetail extends ActiveRecord
{
	/**
	 * 获取最后一次实操时间
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function getLastTime($id)
	{
		if ($id) {
			$model=self::model()
			->field("left(max(po.last_time),16)  last_time")
			->join('practice_order po on t.por_id=po.id')
			->find("t.detail_id={$id} AND po.status=1");
			return $model?$model->last_time:'';
		}
	}
	public function tableName() {
		return 'practice_order_detail';
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
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
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