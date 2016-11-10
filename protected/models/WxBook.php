<?php
/**
 * 微信表
 */
use \Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class WxBook extends ActiveRecord
{

	//关闭缓存
	protected $_cache = false;

	public function tableName() {
		return 'wx_book';
	}
}	