<?php
/**
 * @Author: Administrator
 * @Date:   2016-03-28 14:41:01
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-03-28 14:51:27
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderSaleTags extends ActiveRecord
{
	public function tableName() {
		return 'order_sale_tags';
	}
	/**
	 * 获取订单Tags
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function getTags($id)
	{
		$id=(int)$id;
		$t='';
		$result=self::model()->join('company_tags ct on t.tag_id=ct.id')->findAll("order_id={$id}");
		foreach ($result as $key => $value) {
			if($t=='')
				$t=$value['name'];
			else
				$t=$t.';'.$value['name'];
		}
		return $t?$t:'无';
	}
}