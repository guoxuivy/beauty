<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 退单表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderRefund extends ActiveRecord
{
	public function tableName() {
		return 'order_refund';
	}


	/**
	 *  更新状态(删除)
	 * @param  integer $status 
	 * @return 
	 */
	public function updateStatus($status=-1){
		$this->status=$status;
		return $this->update();
	}
	/**
	 * 状态的停用启用
	 * @return 
	 */
	public function updateUseStatus()
	{
		if(!in_array($this->status, array(1,0)))
			return false;
		$this->status=$this->status==0?1:0;
		return $this->updateStatus($this->status);
	}
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null,$css=false){
		$Array = array(
			'1'		=> '正常',
			'0'		=> '草稿',
			'-1'	=> '删除',
		);
		if($Pk === null) {
			return $Array;
		}else {
			if(array_key_exists($Pk, $Array)) {
				if($css&&$Pk=='-1')
					return '<span class="khgl_04">'.$Array[$Pk].'</span>';
				if($css&&$Pk=='1')
					return '<span class="khgl_05">'.$Array[$Pk].'</span>';
				return $Array[$Pk];
			}else {
				return false;
			}
		}
	}

	/**
	 * 获取付款状态
	 * @param  $Pk 
	 * @param  $css 是否加载样式
	 * @return 
	 */
	public static function  getPayStatus($Pk=null,$css=false){
		$Array = array(
			'1'		=> '已付款',
			'0'		=> '未付款',
		);
		if($Pk === null) {
			return $Array;
		}else {
			if(array_key_exists($Pk, $Array)) {
				if($css&&$Pk)
					return '<span class="khgl_05">'.$Array[$Pk].'</span>';
				return $Array[$Pk];
			}else {
				return false;
			}
		}
	}
	/**
	 * 获取账审状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getCheckStatus($Pk=null,$css=false){
		$Array = array(
			'1'		=> '已审核',
			'0'		=> '未审核',
		);
		if($Pk === null) {
			return $Array;
		}else {
			if(array_key_exists($Pk, $Array)) {
				if($css&&$Pk)
					return '<span class="khgl_05">'.$Array[$Pk].'</span>';
				return $Array[$Pk];
			}else {
				return false;
			}
		}
	}


	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN(){
		$sn_model = \CompanySn::model()->nowNum('tk');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='tk'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}

	/**
	 * 获取退款单退款金额合计
	 * @param  $Pk 
	 * @return 
	 */
	public static function getAllMoney($id=null){
		$res = \OrderRefundDetail::model()
		->field(array("IFNULL( SUM(money), 0) as money"))
		->find("refund_id=".$id);

		$res1 = \OrderRefundMoney::model()
		->field(array("IFNULL( SUM(money), 0) as money"))
		->find("refund_id=".$id);
		return $res['money']+$res1['money'];
	}

	/**
	 * 获取退款单退款去向
	 * @param  $Pk 
	 * @return string
	 */
	public static function getMoneyDir($id=null){
		$list = \CustomerCapitalLog::model()
		->field(array("t.*,c.name as cp_name"))
		->join('customer_capital cc on cc.id=t.cu_capital_id')
		->join('company_capital c on c.id=cc.capital_id')
		->findAll("rel_type=2 AND rel_id=".$id);
		$str=array();
		foreach ($list as $value) {
			if($value['pos_dir']=='in')
				$str[] =$value['cp_name']."转入".$value['money'];
			else
				$str[] =$value['cp_name']."提现".$value['money'];
		}
		return implode("|", $str);
	}

	/**
	 * 获取退款单退款去向、退款金额、提现金额
	 * @param null $id
	 * @return array
	 */
	public function getMoneyType($id=null){
		$list = \CustomerCapitalLog::model()
			->field(array("t.*,c.name as cp_name"))
			->join('customer_capital cc on cc.id=t.cu_capital_id')
			->join('company_capital c on c.id=cc.capital_id')
			->findAll("rel_type=2 AND rel_id=".$id);
		$str=array();$zr = 0;$tx = 0;
		foreach ($list as $value) {
			if($value['pos_dir']=='in'){
				$str[] =$value['cp_name']."转入".$value['money'];
				$zr += $value['money'];
			}else{
				$str[] =$value['cp_name']."提现".$value['money'];
				$tx += $value['money'];
			}
		}
		return $data = array(
			'dir'=>implode("|", $str),
			'zr_money'=>$zr,
			'tx_money'=>$tx,
		);
	}


}