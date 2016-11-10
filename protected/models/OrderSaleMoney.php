<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单赠送的现金和积分构成表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderSaleMoney extends ActiveRecord
{
	public function tableName() {
		return 'order_sale_money';
	}

}