<?php
/**
 * 客户卡券
 */
use Ivy\core\ActiveRecord;
class CustomerVoucher extends ActiveRecord
{
	public function tableName() {
		return 'customer_voucher';
	}
	public function init(){
		parent::init();
		$time=time();
		//标记过期卡券
		\Ivy::app()->db->_exec("update customer_voucher SET `status`=0 where voucher_id in (select id from config_voucher where etime<{$time}) and `status`=1");
		return true;
	}
	/**
	 * 获取可用卡券,已用卡券,失效卡券(统计)
	 * @param  [type] $cu_id [description]
	 * @return [type]        [description]
	 */
	public static function getNum($cu_id)
	{
		$model=self::model()->field("sum(IF(t.status=1,'1',0)) as kykj,sum(IF(t.status=2,'1',0)) as yykj,sum(IF(t.status=0,'1',0)) as sxkj")
				->find("cu_id={$cu_id} and t.status>=0");
		return $model;
	}
	/**
	 * 获取卡券
	 * @param  [type] $cu_id [description]
	 * @return [type]        [description]
	 */
	public static function getList($cu_id,$where=" AND t.status=1")
	{
		return self::model()
		->field("t.*,cv.*,t.id,osd.buy_type")
		->join("config_voucher cv ON t.voucher_id=cv.id")
		->join('order_sale_detail osd on t.detail_id=osd.id')
		->findAll("t.cu_id={$cu_id}".$where);
	}
	/**
	 * 获取卡券剩余现金金额
	 * @param  [type] $vo_id [description]
	 * @return [type]        [description]
	 */
	public static function getLastMoney($vo_id)
	{
		$sale_money=$last_money=0;
		$model=self::model()->field("cv.money as xj_money")->join("config_voucher cv on t.voucher_id=cv.id")->findByPk($vo_id);
		$sale_money=$model->xj_money;
		$OrderVoucher=\OrderVoucher::model()->field("sum(money) as sum_money")->find("vo_id={$vo_id} and status=1");
		if ($OrderVoucher) {
			$last_money=$sale_money-$OrderVoucher->sum_money;
		}
		return $last_money>0?$last_money:0;
	}
	/**
	 * 获取卡券剩余赠送金额
	 * @param  [type] $vo_id [description]
	 * @return [type]        [description]
	 */
	public static function getLastMoneyZS($vo_id)
	{
		$sale_money=$last_money=0;
		$model=self::model()->field("cv.give_money")->join("config_voucher cv on t.voucher_id=cv.id")->findByPk($vo_id);
		$sale_money=$model->give_money;
		$OrderVoucher=\OrderVoucher::model()->field("sum(gift_money) as sum_money")->find("vo_id={$vo_id} and status=1");
		if ($OrderVoucher) {
			$last_money=$sale_money-$OrderVoucher->sum_money;
		}
		return $last_money>0?$last_money:0;
	}
	/**
	 * 获取卡券剩余项目和产品
	 * @param  [type] $vo_id [description]
	 * @return [type]        [description]
	 */
	public static function getLastPro($vo_id)
	{
		$last_pro=array();
		$model=self::model()
		->field("cvc.pro_type,cvc.pro_id,cvc.num")
		->join("config_voucher_compose cvc on t.voucher_id=cvc.voucher_id")
		->findAll("t.id={$vo_id}");//卡券原有
		$OrderVoucher=\OrderVoucher::model()
		->field("osd.pro_type,osd.pro_id,osd.num")
		->join("order_sale_detail osd on t.id=osd.order_id AND osd.buy_type=3")
		->findAll("t.vo_id={$vo_id} and t.status=1");//卡券已经兑换
		if ($model) {
			foreach ($model as $value) {
				if ($OrderVoucher) {
					foreach ($OrderVoucher as $value2) {
						if ($value['pro_type']==$value2['pro_type'] && $value['pro_id']==$value2['pro_id']) {
							$value['num']=$value['num']>0?($value['num']-$value2['num']):0;
						}
					}
				}

				if($value['num']>0)
				{
					$pro_info=\pos\SaleModel::model()->getProInfo($value['pro_id'],$value['pro_type']);
					$pro_info->pro_type=$value['pro_type'];
					$pro_info->pro_id=$value['pro_id'];
					$pro_info->num=$value['num'];
					$last_pro[]=$pro_info;
				}
			}
		}
		return $last_pro;
	}
	/**
	 * 获取卡券剩余项目和产品(名称串)
	 * @param  [type] $vo_id [description]
	 * @return [type]        [description]
	 */
	public static function getLastProNames($vo_id,$type=null)
	{
		$project=$product="";
		$pro=self::getLastPro($vo_id);
		if ($pro) {
			foreach ($pro as $value) {
				if ($value->pro_type==1) {//项目
					$project.=$value->name.' '.$value->num.'次,';
				}else
				{
					$product.=$value->name.' '.$value->num.'个,';
				}
			}
		}
		if($type==1)
			return $project;
		elseif($type==2)
			return $product;
		return array('project'=>$project,'product'=>$product);
	}
	/**
	 * 判断是否有剩余(有返回true)
	 * @param  [type]  $vo_id [description]
	 * @return boolean        [description]
	 */
	public static function isHaveLast($vo_id)
	{
		$money=self::getLastMoney($vo_id);
		$pro=self::getLastPro($vo_id);
		if ($money==0 && empty($pro)) {
			$model=self::model()->findByPk($vo_id);//卡券原有
			if($model->status==1){
				$model->status=2;
				$model->save();
			}
			return false;
		}
		return true;
	}
	/**
	 * 客户所拥有卡券 不分组
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getReProList($uid)
	{
		$uid=(int)$uid;
		$_arr= self::model()
			->field(array("*,t.id as id"))
			->join('config_voucher v on t.voucher_id=v.id')
			->join('order_sale_detail d on t.detail_id=d.id')
			->where("t.cu_id={$uid} AND t.status=1")
			->findAll();
		return $_arr;
	}

	/**
	 * 获取单条详细记录
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function findOne($id=null)
	{
		$id=is_null($id)?$this->id:$id;
		$res= \CustomerVoucher::model()
			->field(array("*,t.id as id,d.num as gm_num,1 as sy_num, ROUND(d.price*d.rebate/100,2) as cj_price "))
			->join('config_voucher v on t.voucher_id=v.id')
			->join('order_sale_detail d on t.detail_id=d.id')
			->where("t.id={$id}")
			->find();
		return $res;
	}

	/**
	 * 卡券购买添加
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function add($data)
	{	
		//多张卡多次写入数据
		for ($i=0; $i < $data['num']; $i++) { 
			$model=new CustomerVoucher;
			$model->detail_id	=$data['id'];
			$model->cu_id=$data['cu_id'];
			$model->voucher_id=$data['pro_id'];
			$model->num=1;
			if(!$model->save()) throw new CException("卡券添加失败！");
		}
		
		//日志待扩展
	}
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'0'		=> '失效',
			'1'		=> '可兑换',
			'2'		=> '已兑换',
			'-1'	=> '删除',
		);
		if($Pk === null) {
			return $Array;
		}else{
			if(array_key_exists($Pk, $Array)){
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}
	/**
	 * 卡券退款
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function refund($data)
	{	
		//多张卡多次写入数据
		$model = CustomerVoucher::model()->findByPk($data['re_pro_id']);
		$model->status=-1;

		$OrderSaleDetail=\OrderSaleDetail::model()->findByPk($model->detail_id);
		if ($OrderSaleDetail->buy_type==1 && $OrderSaleDetail->num) {//购买
			$refund_money=$OrderSaleDetail->pay_price/$OrderSaleDetail->num*$data['num'];
			if ($OrderSaleDetail->var_price>=$refund_money) {
				$OrderSaleDetail->var_price=$OrderSaleDetail->var_price-$refund_money;
			}else
			{
				$OrderSaleDetail->arrears=$OrderSaleDetail->arrears-($refund_money-$OrderSaleDetail->var_price);
				$OrderSaleDetail->var_price=0;
				if($OrderSaleDetail->arrears<0)
					$OrderSaleDetail->arrears=0;
				$OrderSale=\OrderSale::model()->findByPk($OrderSaleDetail->order_id);
				$OrderSale->current_arrears=$OrderSale->current_arrears-($refund_money-$OrderSaleDetail->var_price);
				if($OrderSale->current_arrears<0)
					$OrderSale->current_arrears=0;
				if (!$OrderSale->save()) {
					throw new CException("订单欠款保存错误！");
				}
				$CustomerInfo=\CustomerInfo::model()->findByPk($OrderSaleDetail->cu_id);
				$CustomerInfo->arrears=$CustomerInfo->arrears-($refund_money-$OrderSaleDetail->var_price);
				if($CustomerInfo->arrears<0)
					$CustomerInfo->arrears=0;
				if (!$CustomerInfo->save()) {
					throw new CException("客户欠款保存错误！");
				}
			}
			if (!$OrderSaleDetail->save()) {
				throw new CException("购买详情保存错误！");
			}
		}

		if(!$model->save()) throw new CException("卡券更新失败！");
		//日志待扩展
	}


}	