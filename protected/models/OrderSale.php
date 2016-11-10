<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 订单表模型
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
use Ivy\core\Route;
class OrderSale extends ActiveRecord
{
	public function tableName() {
		return 'order_sale';
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
	 * 获取付款状态(期初开账)
	 * @param  $Pk 
	 * @param  $css 是否加载样式
	 * @return 
	 */
	public static function  getPayStatusForInit($Pk=null,$css=false){
		$Array = array(
			'1'		=> '已开账',
			'0'		=> '未开账',
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
	public function buildSN($type=''){
        if(empty($type)) $type = 'gm';
		$sn_model = \CompanySn::model()->nowNum($type);
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn=$type.date("Ymd").$new_count;
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

			\OrderSaleDetail::model()->deleteAll("buy_type in (1,2) AND order_id=".$this->id);

			$this->delete();

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return true;
	}
	/**
	 * 获取订单类型 0还款、1购买、2充值、3项目兑换、4积分兑换
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null,$css=false){
		$Array = array(
			'0' => '还款',
			'1' => '购买',
			'2' => '充值',
			'3' => '项目兑换',
			'4' => '积分兑换',
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
	 * 获取交易url
	 * @param  [type] $type [description]
	 * @param  [type] $id   [description]
	 * @return [type]       [description]
	 */
	public function getOrderUrl($type,$id)
	{
		$url='';
		$Route=new Route();
		switch ($type) {
			case '0':
				$url=$Route->url("pos/repay/edit",array("id"=>$id));
				break;
			case '1':
				$url=$Route->url("pos/sale/edit",array("id"=>$id));
				break;
			case '2':
				$url=$Route->url("pos/recharge/edit",array("id"=>$id));
				break;
			case '3':
				$url=$Route->url("pos/voucher/view",array("id"=>$id));
				break;
			case '5':
				$url=$Route->url("pos/refund/view",array("id"=>$id));
				break;
			default:
				# code...
				break;
		}
		return $url;
	}

	/**
	 * 获取订单内容 字符串
	 * @param  $id 
	 * @return 
	 */
	public static function  getBody($id=null){
		$str="";
		$model = self::model()->findByPk($id);
		switch ($model->type) {
			case '0':
				$str.="还款".$model->price."元";
				break;
			case '1':
				$list = \OrderSaleDetail::model()->findAll("buy_type<>3 AND order_id=".$id);
				foreach ($list as $v) {
					$pro = \pos\SaleModel::model()->getProInfo($v['pro_id'],$v['pro_type']);
					$b_type = $v['buy_type']==1?"购买":"赠送";
					$str.=$b_type.$pro->name."(".$v['num'].")";
				}
				break;
			case '2':
				$str.="充值".$model->price."元";
				break;
			default:
				break;
		}
		return $str;
	}
}