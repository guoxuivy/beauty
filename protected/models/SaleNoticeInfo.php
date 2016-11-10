<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-07-28 17:06:52
 */
/**
 * 门店
 */
use Ivy\core\ActiveRecord;
class SaleNoticeInfo extends ActiveRecord
{
	public function tableName() {
		return 'sale_notice';
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
		if (is_string($this->start_time)) {
			$this->start_time=strtotime($this->start_time);
		}
		if (is_string($this->end_time)) {
			$this->end_time=strtotime($this->end_time);
		}
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
			'-1'	=> '已删除',
		);
		if($Pk === null) {
			return $Array;
		} else {
			$model=self::model()->findByPk($Pk);
			if($model->status=="1"){
				if($model->start_time>time()) return "未开始";
				if($model->start_time<time() && time()<$model->end_time ) return "进行中";
				if($model->end_time<time()) return "已过期";
			}else{
				return $Array[$model->status];
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
	 * 获取可用活动列表
	 * @return 
	 */
	public function getListOpt(){
		$map["comp_id"]=array("eq",\Ivy::app()->user->comp_id);
		$map["status"]=array("eq",1);
		$map["start_time"]=array("lt",time());
		$map["end_time"]=array("gt",time());
		$list = $this->findAll($map);
		$r=array();
		foreach ($list as $v) {
			$r[$v['id']]=$v['title'];
		}
		return $r;
	}
}	