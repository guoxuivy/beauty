<?php
namespace noinvo;
/**
 * 领用单(无进销存)
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class Recipients extends ActiveRecord
{

	public function tableName() {
		return 'no_invo_recipients';
	}

	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$this->attributes=$data;
			$this->sn=$this->sn?$this->sn:$this->buildSN();
			$this->comp_id = \Ivy::app()->user->comp_id;
			$this->dept_id = \Ivy::app()->user->dept_id;
			$this->create_user = \Ivy::app()->user->id;
			$this->create_time =time();
			if(!$this->save()) throw new CException("领用失败！");
			//出库单详细处理
			if($data['detail']){
				foreach ($data['detail'] as $v) {
					if(empty($v)) continue;				
					$td_m=new RecipientsDetail;
					$td_m->order_id=$this->id;
					$td_m->product_id=$v['pro_id'];
					$td_m->num=$v['num'];
					if(!$td_m->save())throw new CException("领用失败！关联单据保存失败!");
				}
			}
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $this->id;
	}
	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN(){
		$SNS='LY';
		$sn_model = \CompanySn::model()->nowNum($SNS);
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn=$SNS.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}
	/**
	 *  添加、编辑页面需要的模型
	 * @param  integer $status 
	 * @return 
	 */
	public function getEditModel($id=null){
		$model = new Recipients;
		if($id)
			$model->findByPk((int)$id);
		if($model->isNewRecord){
			//添加
			$model->detail=array();
		}else{
			//编辑
			$model->detail=RecipientsDetail::model()->getByProj($model->id);
		}
		
		return $model;
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

}	