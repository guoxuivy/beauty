<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单内容详细支付构成表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderRefundDetailMoney extends ActiveRecord
{
	public function tableName() {
		return 'order_refund_detail_money';
	}

}