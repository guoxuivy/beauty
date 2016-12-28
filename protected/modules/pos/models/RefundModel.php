<?php
namespace pos;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 前台开单操作通用方法 退款
 */
class RefundModel extends Model
{
	public $o_s=null;//order_refund 实例
	public $pros=array();//购买商品  detail 数组
	public $capital=array();//账户退款 数组

	/**
	 * 退款单 制单保存
	 * @param array $data 创建订单需要的数组
	 * @return  int order_id 
	 */
	public function editNew($data){
		if(isset($data['order_id']) && $data['order_id']>0){
			//删除退款单数据 （外键关联删除关联数据） 保留原有id SN
			$old_order = \OrderSale::model()->findByPk($order_id);
			$data['id']=$order_id;
			$data['sn']=$old_order->sn;
			$old_order->delete();
		}
		return $this->saveNew($data);
	}
	/**
	 * 购买订单 创建 草稿状态保存 (兼容编辑)
	 * @param array $data 创建订单需要的数组
	 * @return  int order_id 
	 */
	public function saveNew($data){
		try{
			//开启事务处理  
			$this->db->beginT();
			$order = new \OrderRefund;
			$order->id 			= $data['id']?$data['id']:null;
			$order->sn 			= $data['sn']?$data['sn']:$order->buildSN();
			$order->md_id 		= \Ivy::app()->user->dept_id;
			$order->comp_id		= \Ivy::app()->user->comp_id;
			$order->cu_id 		= $data['cu_id'];
			$order->made_id 	= \Ivy::app()->user->id;
			$order->note 		= $data['note'];

			if(!$order->save()) throw new CException("订单创建失败！");
			//直接退款写入
			foreach ($data['capital'] as $capital) {
				$c_m = new \OrderRefundMoney;
				$c_m->refund_id 	= $order->id;
				$c_m->capital_id 	= $capital['capital_id'];
				$c_m->money 		= $capital['money'];
				if(!$c_m->save()) throw new CException("订单创建失败,直接退款失败！");
			}


			//商品详细写入
			foreach ($data['pro'] as $pro) {
				$detail = new \OrderRefundDetail;
				$detail->refund_id 	= $order->id;
				$detail->re_pro_id 	= $pro['re_pro_id'];
				$detail->pro_type 	= $pro['pro_type'];
				$detail->md_id 		= $order->md_id;
				$detail->cu_id 		= $order->cu_id;
				$detail->num 		= $pro['num'];
				$detail->money 		= $pro['money'];

				if(!$detail->save()) throw new CException("订单创建失败,详单制单失败！");
				//本商品赠送账户写入
				$this->detailMoneyIn($detail->id,$pro['tk_detail']);
				
			}

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $order->id;
	}

	/**
	 * 商品的退款详情写入
	 * @param string $data 2|50,3|20,4|30  账户id|金额 
	 */
	public function detailMoneyIn($detail_id,$data=""){
		if(!$data) return;
		foreach (explode(',',$data) as $v) {
			list($capital_id,$money)=explode('|',$v);
			if($money==0) continue;
			//$capital_id 为公司配置中的ID 
			$model= new \OrderRefundDetailMoney;
			$model->capital_id 	=$capital_id;
			$model->money 		=$money;
			$model->refund_detail_id 	=$detail_id;
			if(!$model->save()) throw new CException("订单创建失败,资金详细写入失败！");
		}
	}

	/**
	 * 确认退款
	 * @param  [type] $order_id [退款单id]
	 * @param  [type] $post     [description]
	 * @return [type]           [description]
	 */
	public function confirm($order_id){
		try{
			//开启事务处理  
			$this->db->beginT();
			$o = \OrderRefund::model()->findByPk($order_id);
			if($o->status==1) throw new CException("不可重复退款！");
			$o->status=1;
			$o->pay_time=time();
			if(!$o->save()) throw new CException("状态修改失败！");
			//账户流水写入
			$this->addCapitals($order_id);
			//项目、产品、卡券写入卡内剩余 扣除
			$this->delRePro($order_id);

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $order_id;
	}

	/**
	 * 订单数据写入客户账户流水
	 * @param  [type] $order_id     [订单id]
	 * @param  [type] $dir     		[入 or 出 （账户资金）]
	 * @return [CException]         [抛出异常]
	 */
	public function addCapitals($order_id){
		$order = \OrderRefund::model()->findByPk($order_id);
		$cu_id=$order->cu_id;
		
		//直接账户退款
    	$list_m = \OrderRefundMoney::model()->where(array('refund_id'=>$order_id))->findAll();
    	foreach ($list_m as $v) {
    		$data=array();
			$data['capital_id']	=$v['capital_id'];
			$data['money']		=$v['money'];
			$data['cu_id']		=$cu_id;
			$data['rel_id']		=$order_id;
			$data['rel_type']	=2;
			$data['pos_dir']	="out";
			\CustomerCapital::model()->edit($data);
    	}
		//项目、产品、卡券退款数据流水数据收集
		$capital_list=array();
		$list=\OrderRefundDetailMoney::model()
			->field('t.capital_id,t.money,d.cu_id')
			->join("order_refund_detail as d on d.id=t.refund_detail_id")
			->where("d.refund_id=".$order_id )
			->findAll();
		foreach ($list as $value) {
			if(isset($capital_list[$value['capital_id']])) 
				$capital_list[$value['capital_id']]+=$value['money'];
			else
				$capital_list[$value['capital_id']]=$value['money'];
		}
		foreach ($capital_list as $capital_id => $money) {
			$data=array();
			$data['capital_id']	=$capital_id;
			$data['money']		=$money;
			$data['cu_id']		=$cu_id;
			$data['rel_id']		=$order_id;
			$data['rel_type']	=2;
			$data['pos_dir']	="in";
			\CustomerCapital::model()->edit($data);
		}
	}

	/**
	 * 订单数据写入客户卡内剩余 项目、产品、卡券
	 * @param  [type] $order_id     [订单id]
	 * @param  [type] $dir     		[入 or 出 ]
	 * @return [CException]         [抛出异常]
	 */
	public function delRePro($order_id){
		$detail_list = \OrderRefundDetail::model()->where("refund_id={$order_id}")->findAll();
		foreach($detail_list as $pro){
			switch ($pro['pro_type']) {
				case '1':
					\CustomerReProject::model()->refund($pro);
					break;
				case '2':
					\CustomerProdStorageDetail::model()->refund($pro);
					break;
				case '3':
					\CustomerVoucher::model()->refund($pro);
					break;
				default:
					throw new CException("无此退款类型！");
					break;
			}
		}
	}


	/**
	 * 订单模型 预览编辑时使用
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getOrderModel($id=null){

		$this->o_s = \OrderRefund::model()->findByPk($id);

		if(!$this->o_s) throw new CException("无此订单！");

		$this->capital = \OrderRefundMoney::model()->where(array('refund_id'=>$id))->findAll();

		$this->pros = \OrderRefundDetail::model()->where(array('refund_id'=>$id))->findAll();//所有商品

		//获取项目资金构成
		foreach ($this->pros as &$pro) {
			$pro['money_str']=$this->proMoneyStr($pro['id']);
		}
		return $this;
	}
	/**
	 * 商品付款组成 字符串
	 * @param  [type] $detail_id [description]
	 * @return [type]            [description]
	 */
	public function proMoneyStr($detail_id){
		$money=\OrderRefundDetailMoney::model()->where(array('refund_detail_id'=>$detail_id))->findAll();//money 构成
		$money_str=array();
		foreach ($money as $v) {
			$money_str[]=$v['capital_id']."|".$v['money'];
		}
		$money_str=implode(',',$money_str);
		return $money_str;
	}


	/*****工具方法****/

	/**
	 * 获取所退商品商品信息 项目产品卡券
	 * @param  [type]  $re_pro_id   [description]
	 * @param  integer $type [description]
	 * @return [type]        [description]
	 */
	public function getProInfo($id,$type=1){
		switch ($type) {
			case 1:
				$res= \CustomerReProject::findOne($id);
				break;
			case 2:
				$res = \CustomerProdStorageDetail::findOne($id);
				break;
			case 3:
				$res = \CustomerVoucher::findOne($id);
				break;
			default:
				$res = null;
				break;
		}
		return $res;
	}

	
	//制单人姓名
	public function getEmployName($id=null){
		$id=is_null($id)?$this->o_s->made_id:$id;
		$info = \EmployUser::model()->findByPk($id);
		if($info)
			return $info->netname;
		return "";
	}


}