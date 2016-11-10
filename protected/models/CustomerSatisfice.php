<?php
/**
 * 客户调查表
 */
use Ivy\core\ActiveRecord;
class CustomerSatisfice extends ActiveRecord
{
	public function tableName() {
		return 'customer_satisfice';
	}

	/**
	 * 获取调查表
	 * @param  [type] $practice_id [description]
	 * @return [type]        [description]
	 */
	public static function getListByPractice($practice_id,$where=" AND t.status=1")
	{

		return self::model()
		->field("cs.id,t.note,csd.score,cs.title")
		->join("customer_satisfice_detail csd ON t.id=csd.pro_id")
		->join('config_satisfice cs on csd.config_id=cs.id')
		->findAll("t.practice_id={$practice_id}".$where);
	}
	
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '正常',
			'-1'	=> '删除',
		);
		if($Pk === null) {
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