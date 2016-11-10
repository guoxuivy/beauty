<?php
/**
 * 个人账户
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class CustomerCapital extends ActiveRecord
{
	public function tableName(){
		return 'customer_capital';
	}
	/**
	 * 获取客户资金账户
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getListByUser($uid)
	{
		$uid=(int)$uid;
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$_arr= \CompanyCapital::model()
		->field(array("*","t.note",))
		->join("customer_capital cc on t.id=cc.capital_id  AND cc.cu_id={$uid}")
		->where("t.comp_id={$company} AND t.`status`=1")
		->findAll();

		return $_arr;
	}
	/**
	 * 获取资金账户数组
	 * @param  [type] $uid [description]
	 * @return [type]      [description]
	 */
	public static function getListArr($uid,$type=1)
	{
		$uid=(int)$uid;
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$sqlw="";
		if($type!='all')
			$sqlw=" AND t.type={$type}";
		$model=\CompanyCapital::model()
		->field(array("t.name","t.id",))
		->where("t.comp_id={$company} AND t.`status`=1  {$sqlw}")
		->findAll();
		$_arr=array();
		if ($model) {
			foreach ($model as $key => $value) {
				$_arr[$value['id']]=$value['name'];
			}
		}
		return $_arr;
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return CException
	 */
	// $data['capital_id']	=$capital_id;
	// $data['money']		=$money; //变动金额
	// $data['cu_id']		=$cu_id;
	// $data['rel_id']		=$order_id;
	// $data['rel_type']	=1;
	// $data['pos_dir']		="out"; //不填 默认花钱
	public function edit($data){
		$money = abs($data['money']); 		//取绝对值
		if($money == 0) return;

		$model=$this->find("cu_id={$data['cu_id']} AND capital_id={$data['capital_id']}");
		if(!$model)
			$model = new CustomerCapital;
		$model->attributes=$data;

		if($model->getIsNewRecord()){
			$model->balance=0;
			$before_money=0;
		}else{
			$before_money=$model->balance;
		}
		if(isset($data['pos_dir']) && $data['pos_dir']=="in"){
			$model->balance=$model->balance+$money;
		}else{
			$model->balance=$model->balance-$money;
			$data['pos_dir']="out";
		}
		if ($model->balance<0) {
			throw new CException("账户不能为负数！");
		}

		if(!$model->save()) throw new CException("账户修改失败！");

		//日志写入
		$ldata['cu_capital_id']	=$model->id;
		$ldata['before_money']	=$before_money;
		$ldata['money']			=$money;
		$ldata['after_money']	=$model->balance;
		$ldata['rel_id']		=$data['rel_id'];
		$ldata['rel_type']		=$data['rel_type'];
		$ldata['pos_dir']		=$data['pos_dir'];
		\CustomerCapitalLog::model()->add($ldata);
	}

}	