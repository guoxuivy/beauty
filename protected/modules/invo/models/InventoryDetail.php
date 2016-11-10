<?php
namespace invo;
/**
 * 商品盘库详细
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class InventoryDetail extends ActiveRecord
{

	public function tableName() {
		return 'invo_inventory_detail';
	}

	public function updateDetail($data){
		foreach($data as $item){
			$inventoey_detail = InventoryDetail::model()->findByPk($item['detail_id']);
			$inventoey_detail->curr_num = $item['curr_num'];
			$inventoey_detail->check_num = $item['check_num'];
			if($item['check_num']-$item['curr_num']>0){
				$_num = $item['check_num']-$item['curr_num'];
				$inventoey_detail->gain_num = $_num;
				$inventoey_detail->loss_num = '';
			}else{
				$_num = $item['curr_num']-$item['check_num'];
				$inventoey_detail->gain_num = '';
				$inventoey_detail->loss_num = $_num;
			}
			$inventoey_detail->balance_num = $_num;
			if(isset($item['check_ext_num'])){
				$inventoey_detail->check_ext_num = $item['check_ext_num'];
			}
			if(!$inventoey_detail->save()) throw new CException("盘库单详细更新失败！");
		}
		return true;
	}


}	