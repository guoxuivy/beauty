<?php
/**
 * @Author: K
 * @Date:   2015-08-03 14:11:31
 * @Last Modified by:   K
 * @Last Modified time: 2015-09-29 11:03:38
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class OrderVoucher extends ActiveRecord
{
	public function tableName() {
		return 'order_voucher';
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
			'1'		=> '已兑换',
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
			'1'  => '已确认',
			'0'  => '未确认',
			'-1' =>	'已废除',
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
		$sn_model = \CompanySn::model()->nowNum('dh');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='dh'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}
	/**
	 * 获取订单详情(项目产品卡券名称)
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function getProNames($id)
	{
		$model=\OrderSaleDetail::model()->findAll("order_id={$id}");
		$_arr=array();
		if ($model) {
			foreach ($model as $key => $value) {
				$proinf=self::getProInfo($value['pro_id'],$value['pro_type']);
				if ($proinf) {
					$_arr[]=$proinf->name;
				}
			}
		}
		return @implode(',',$_arr);
	}
	/**
	 * 获取商品信息
	 * @param  [type]  $id   [description]
	 * @param  integer $type [description]
	 * @return [type]        [description]
	 */
	public static function getProInfo($id,$type=1){
		switch ($type) {
			case 1:
				$res = \ProjectInfo::model()->findByPk($id);
				break;
			case 2:
				$res = \ProductInfo::model()->findByPk($id);
				break;
			case 3:
				$res = \ConfigVoucher::model()->findByPk($id);
				break;
			default:
				$res = null;
				break;
		}
		return $res;
	}
	/**
	 * 订单删除  放弃外键关联删除 使用事务约束删除
	 * @param array $data 创建订单需要的数组
	 * @return  int order_id 
	 */
	public function orderDelete(){
		try{
			//开启事务处理  
			$this->db->beginT();

			\OrderSaleDetail::model()->deleteAll("buy_type =3 AND order_id=".$this->id);

			$this->delete();

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return true;
	}
}