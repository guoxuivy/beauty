<?php

use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderRefundMoney extends ActiveRecord
{
	public function tableName() {
		return 'order_refund_money';
	}

}