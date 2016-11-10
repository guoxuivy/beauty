<?php
/**
 * 负债表
 */
use \Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class StaticFz extends ActiveRecord
{

	//关闭缓存
	protected $_cache = false;

	public function tableName() {
		return 'static_fz';
	}
}	