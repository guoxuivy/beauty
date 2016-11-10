<?php
/**
 * 个人账户 流水
 */
use Ivy\core\ActiveRecord;
class CustomerCapitalLog extends ActiveRecord
{
	public function tableName() {
		return 'customer_capital_log';
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function add($data)
	{
		$model=new CustomerCapitalLog;
		$model->attributes=$data;
		if(!$model->save()) throw new CException("账户日志添加失败！");
	}

	/**
	 * 获取客户账户动态
	 * @param $cu_id
	 * @return array
	 */
	public function getCapitalLog($cu_id){
		$sql = "
			SELECT cu_total.id as id,cu_total.type as type,left(cu_total.ctime,16) AS ctime,cu_total.sn as sn,cu_total.cvname as cvname,
				cu_total.pos_dir as pos_dir,cu_total.money as money,cu_total.before_money as before_money,cu_total.after_money as after_money
			FROM
			(SELECT os.id as id,os.type as type,t.create_time AS ctime,os.sn as sn,cv.name as cvname,t.pos_dir as pos_dir,
				t.money as money,t.before_money as before_money,t.after_money as after_money
			FROM customer_capital_log t
			LEFT JOIN customer_capital cc on t.cu_capital_id=cc.id
			LEFT JOIN company_capital cv on cc.capital_id=cv.id
			LEFT JOIN order_sale os on t.rel_id=os.id
			WHERE cc.cu_id={$cu_id} and t.rel_type=1
			UNION ALL
			SELECT os.id as id,5 as type,t.create_time AS ctime,os.sn as sn,cv.name as cvname,t.pos_dir as pos_dir,
				t.money as money,t.before_money as before_money,t.after_money as after_money
			FROM customer_capital_log t
			LEFT JOIN customer_capital cc on t.cu_capital_id=cc.id
			LEFT JOIN company_capital cv on cc.capital_id=cv.id
			LEFT JOIN order_refund os on t.rel_id=os.id
			WHERE cc.cu_id={$cu_id} and t.rel_type=2
			UNION ALL
			SELECT os.id as id,3 as type,t.create_time AS ctime,os.sn as sn,cv.name as cvname,t.pos_dir as pos_dir,
				t.money as money,t.before_money as before_money,t.after_money as after_money
			FROM customer_capital_log t
			LEFT JOIN customer_capital cc on t.cu_capital_id=cc.id
			LEFT JOIN company_capital cv on cc.capital_id=cv.id
			LEFT JOIN order_voucher os on t.rel_id=os.id
			WHERE cc.cu_id={$cu_id} and t.rel_type=3) cu_total
			ORDER BY cu_total.ctime DESC
			LIMIT 20
			";
		$result = $this->findAllBySql($sql);
		return $result;
	}
	
}	