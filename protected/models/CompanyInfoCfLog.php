<?php
/**
 * 催费记录
 * @Author: guox
 * @Date:   2016-1-12 14:15:00
 * @Last Modified time: 2016-01-14 15:48:59
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CompanyInfoCfLog extends ActiveRecord
{

	//关闭缓存
	protected $_cache = false;
	public function tableName() {
		return 'company_info_cf_log';
	}

}