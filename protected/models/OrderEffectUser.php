<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单业绩模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderEffectUser extends ActiveRecord
{
	public function tableName() {
		return 'order_effect_user';
	}




}