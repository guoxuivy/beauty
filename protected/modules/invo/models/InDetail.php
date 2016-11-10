<?php
namespace invo;
/**
 * 入库商品详细
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class InDetail extends ActiveRecord
{

	public function tableName() {
		return 'invo_in_detail';
	}
}	