<?php
/**
 * 客户卡内剩余项目
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerReProLog extends ActiveRecord
{
	public function tableName() {
		return 'customer_re_pro_log';
	}


	/**
	 * 卡内剩余项目变更记录
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function add($data)
	{	
		$model=new CustomerReProLog;
		$model->attributes=$data;
		return $model->save();
	}





}	