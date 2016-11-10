<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单内容详细表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderRefundDetail extends ActiveRecord
{
	public function tableName() {
		return 'order_refund_detail';
	}
	/**
	 * 获取整单退多少钱
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function getSumMoney($id)
	{
		$model=self::model()->field("sum(`money`) as sum_money")->order("refund_id")->find("refund_id={$id}");
		return $model?$model->sum_money:0;
	}
}