<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单内容详细表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderSaleDetail extends ActiveRecord
{
	public function tableName() {
		return 'order_sale_detail';
	}



	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getBuyType($Pk=null){
		$Array = array(
			'1'	=> '购买',
			'2'	=> '赠送',
			'3'	=> '兑换',
		);
		if($Pk === null) {
			return $Array;
		}else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}else {
				return false;
			}
		}
	}

	/**
	 * 客户卡内剩余项目 不分组
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getSn($id)
	{
		$id=(int)$id;
		$_arr= self::model()
		->field(array("o.sn"))
		->join('order_sale o on t.order_id=o.id')
		->where("t.id={$id}")
		->find();
		return $_arr['sn'];
	}

}