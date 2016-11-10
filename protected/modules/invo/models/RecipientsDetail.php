<?php
namespace invo;
/**
 * 详细
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class RecipientsDetail extends ActiveRecord
{

	public function tableName() {
		return 'invo_recipients_detail';
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
	 * 获取入库类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'1'		=> '采购入库',
			'2'		=> '调拨入库',
			'3'		=> '购买退回',
			'4'		=> '分仓退货',

		);
		if($Pk === null){
			return $Array;
		}else{
			if(array_key_exists($Pk, $Array)){
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}


}	