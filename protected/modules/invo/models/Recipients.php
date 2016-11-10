<?php
namespace invo;
/**
 * 领用单
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class Recipients extends ActiveRecord
{

	public function tableName() {
		return 'invo_recipients';
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
			$this->save();
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



}	