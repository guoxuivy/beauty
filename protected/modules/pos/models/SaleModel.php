<?php
namespace pos;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 正常购买 、卡券兑换、订单还款
 */
class SaleModel extends Model
{
	public $o_s=null;//sale_order 实例
	public $pros=array();//购买商品  detail 数组

	
	/**
	 * 卡券兑换 创建 草稿状态保存 (兼容编辑)
	 * @param array $data 创建卡券兑换需要的数组
	 * @return  int order_id 
	 */
	public function saveVoucherNew($data){
		try{
			//开启事务处理
			$this->db->beginT();
			$order = new \OrderVoucher;
			$order->id      = $data['order_id']?$data['order_id']:null;
			$order->sn      = $data['sn']?$data['sn']:$order->buildSN();
			$order->md_id   = \Ivy::app()->user->dept_id;
			$order->cu_id   = $data['cu_id'];
			$order->comp_id = \Ivy::app()->user->comp_id;
			$order->made_id = \Ivy::app()->user->id;
			$order->vo_id   =$data['vo_id'];
			$order->capital_id=(int)$data['capital_id'];
			if($order->capital_id>0)
				$order->money=$data['money'];
			$order->gift_capital_id=(int)$data['gift_capital_id'];
			if($order->gift_capital_id>0)
				$order->gift_money=$data['gift_money'];
			$order->note 		= $data['note'];
			if ($data['pay']==1) {
				$order->status=1;
				$order->pay_time=time();
			}
			if(!$order->save()) throw new CException("卡券兑换创建失败！");
			//商品详细写入
			foreach ($data['pro'] as $pro) {
				$detail = new \OrderSaleDetail;
				$detail->order_id 	= $order->id;
				$detail->md_id 		= $order->md_id;
				$detail->cu_id 		= $order->cu_id;
				$detail->pro_id 	= $pro['pro_id'];
				$detail->num 		= $pro['num'];
				$detail->pro_type 	= $pro['pro_type'];
				$detail->price 	= $pro['price']*$pro['num'];
				$detail->var_price 	= $pro['price']*$pro['num'];
				$detail->buy_type 	= '3';
				if(!$detail->save()){
					throw new CException("卡券兑换创建失败,详单制单失败！");
				} 
			}

			//账户变更
			if ($order->status==1) {
				$arr=array();
				$arr['capital_id']	=$data['capital_id'];
				$arr['money']		=$data['money'];
				$arr['cu_id']		=$data['cu_id'];
				$arr['rel_id']		=$order->id;
				$arr['rel_type']	=3;
				$arr['pos_dir']		="in";
				if ($order->capital_id>0 && $order->money>0) {//钱兑换
					\CustomerCapital::model()->edit($arr);
				}
				if ($order->gift_capital_id>0 && $order->gift_money>0) {//钱兑换
					$arr['capital_id']	=$data['gift_capital_id'];
					$arr['money']		=$data['gift_money'];
					\CustomerCapital::model()->edit($arr);
				}
				//项目、产品、卡券写入卡内剩余
				$this->addRePro($order->id,3);
			}
			$lastMoney=\CustomerVoucher::getLastMoney($order->vo_id);
			//$lastMoneyZS=\CustomerVoucher::getLastMoneyZS($order->vo_id);
			$lastPro=\CustomerVoucher::getLastPro($order->vo_id);
			if ($lastMoney==0 && !$lastPro) { //标记为已兑换
				$CustomerVoucher=\CustomerVoucher::model()->findByPk($order->vo_id);
				$CustomerVoucher->status=2;
				$CustomerVoucher->save();
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
	 * 购买卡券兑换 编辑 草稿状态保存
	 * @param array $data 创建卡券兑换需要的数组
	 * @return  int order_id 
	 */
	public function editVoucherNew($order_id,$data){
		//删除卡券兑换数据 （外键关联删除关联数据） 保留原有id SN
		$old_order = \OrderVoucher::model()->findByPk($order_id);
		$data['id']=$order_id;
		$data['sn']=$old_order->sn;
		//$old_order->orderDelete();
		if(!$old_order->orderDelete()){
			$this->_error[]=$old_order->popErr();
			return false;
		}
		return $this->saveVoucherNew($data);
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
			$order = new \OrderSale;
			$order->attributes=$data;
			$order->id 			= $data['id']?$data['id']:null;
			$order->md_id 		= \Ivy::app()->user->dept_id;
			$order->made_id 	= \Ivy::app()->user->id;
			$order->comp_id = \Ivy::app()->user->comp_id;
            if($data['type']=='1'||$data['type']=='') $sn = '';
            if($data['type']=='2') $sn='cz';
            if($data['type']=='0') $sn='hk';

			$order->sn 			= $data['sn']?$data['sn']:$order->buildSN($sn);
			// $order->effect_user = implode(',',$effect_user);
			// $order->effect_rate = implode(',',$effect_rate);
			if($order->is_init==1){
							$order->cash=$order->pay_price-$order->arrears;
							$CustomerInfo=\CustomerInfo::model()->findByPk($order->cu_id);
							$CustomerInfo->cart_init=1;
							$CustomerInfo->save();
						}
            $order->type 		= $data['type']!=''?$data['type']:1;
            //订单积分写入
            $order->score=(int)$data['gift_jf'];
			if(!$order->save()) throw new CException("订单创建失败！");
			//业绩写入
			if($data['effect'])
				foreach($data['effect'] as $e){
					$effectM = new \OrderEffectUser;
					$effectM->order_id=$order->id;
					$effectM->user_id =$e['e_id'];
					$effectM->rate    =$e['e_rate'];
					if(!$effectM->save()) throw new CException("订单创建失败！");
				}
			$capital_arr=array();
			//商品详细写入
			foreach ($data['pro'] as $pro) {
				$detail = new \OrderSaleDetail;
				$detail->order_id 	= $order->id;
				$detail->md_id 		= $order->md_id;
				$detail->cu_id 		= $order->cu_id;

				$detail->pro_id 	= $pro['pro_id'];
				$detail->num 		= $pro['num'];
				$detail->price 		= $pro['price'];
				$detail->pay_price 	= $pro['pay_price'];
				$detail->rebate 	= $pro['rebate'];
				$detail->card_gift 	= $pro['card_gift'];
				$detail->pro_type 	= $pro['pro_type'];
				$detail->promotion_id 	= $pro['promotion_id'];
				if($pro['arrears'])
					$detail->arrears=$pro['arrears'];
				if($order->is_init==1){
					$detail->cash=$detail->pay_price-$detail->arrears;
					$detail->var_price=$detail->cash;
				}
				$detail->buy_type 	= '1';
				//更新卡券数量
				if($pro['pro_type']==3){
					$this->updateVoucherNum($pro['pro_id'],$pro['num']);
				}
				if(!$detail->save()){
					throw new CException("订单创建失败,详单制单失败！");
				} 
				//账户合集
				if($pro['gift_detail'])
					foreach (explode(',',$pro['gift_detail']) as $v) {
						list($capital_id,$money)=explode('|',$v);
						if($money==0) continue;
						$capital_arr[$capital_id]+=$money;
					}
				//本商品赠送账户写入
				$this->detailGiftMoneyIn($detail->id,$pro['gift_detail']);
			}
			//检查账户是否够扣减
			if($capital_arr)
				foreach ($capital_arr as $key => $value) {
					$CustomerCapital=\CustomerCapital::model()->find("cu_id={$order->cu_id} AND capital_id={$key}");
					if($CustomerCapital){
						if(($CustomerCapital->balance-$value)<0)
							throw new CException("订单创建失败,账户抵扣错误！");
					}else
					{
						throw new CException("订单创建失败,账户抵扣错误！");
					}
				}
            if(!empty($data['repay'])){
                foreach($data['repay'] as $repay){
                    \OrderRepayDetail::model()->addRepayDetail($repay,$order);
                }
            }

			//订单赠送详细写入
			$this->addNewGift($order,$data);

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $order->id;
	}

	/**
	 * 更新卡券数量
	 * @param $vid
	 * @param $num
	 * @throws CException
	 */
	public function updateVoucherNum($vid,$num){
		$voucher = \ConfigVoucher::model()->findByPk($vid);
		if($voucher->num!=-1){
			$voucher->num = $voucher['num']-$num;
			if(!$voucher->save()) throw new CException("订单创建失败,卡券数量更新失败！");
		}
	}


	/**
	 * 订单赠送详细写入
	 */
	public function addNewGift($order,$data){
		if($data['gift']){
			foreach ($data['gift'] as $gift) {
				$detail = new \OrderSaleDetail;
				$detail->order_id 	= $order->id;
				$detail->md_id 		= $order->md_id;
				$detail->cu_id 		= $order->cu_id;
				$detail->pro_id 	= $gift['pro_id'];
				$detail->num 		= $gift['num'];
				$detail->price 		= $gift['price'];
				$detail->var_price 		= $gift['price'];
				$detail->pro_type 	= $gift['pro_type'];
				$detail->buy_type 	= '2';
				//更新卡券数量
				if($gift['pro_type']==3){
					$this->updateVoucherNum($gift['pro_id'],$gift['num']);
				}
				if(!$detail->save()) throw new CException("订单创建失败,赠送详单制单失败！");
			}
		}
		if($data['gift_xj']){
			foreach ($data['gift_xj'] as $gift_xj) {
				$detail = new \OrderSaleMoney;
				$detail->order_id 	= $order->id;
				$detail->capital_id = $gift_xj['capital_id'];
				$detail->cash 		= $gift_xj['cash'];
				$detail->type 		= '1';
				if(!$detail->save()) throw new CException("订单创建失败,赠送现金制单失败！");
			}
		}
		if($data['gift_jf']){
			$detail = new \OrderSaleMoney;
			$detail->order_id 	= $order->id;
			$detail->cash 		= $data['gift_jf'];
			$detail->type 		= '2';
			if(!$detail->save()) throw new CException("订单创建失败,赠送积分制单失败！");
		}
		
	}

	/**
	 * 商品的赠送账户抵扣详情写入
	 * @param string $data 2|50,3|20,4|30  赠送类型账户id|金额 
	 */
	public function detailGiftMoneyIn($detail_id,$data=""){
		if(!$data) return;
		foreach (explode(',',$data) as $v) {
			list($capital_id,$money)=explode('|',$v);
			if($money==0) continue;
			//$capital_id 为公司配置中的ID 需要转换为该客户持有的id 待完善
			$model= new \OrderSaleDetailMoney;
			$model->capital_id 	=$capital_id;
			$model->money 		=$money;
			$model->detail_id 	=$detail_id;

			if(!$model->save()) throw new CException("订单创建失败,资金详细写入失败！");
		}
	}


	/**
	 * 订单模型 编辑订单时使用
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getOrderModel($id=null,$all_pro=false){

		$this->o_s = \OrderSale::model()->findByPk($id);

		if(!$this->o_s) throw new CException("无此订单！");

		if(!$all_pro)
			$this->pros = \OrderSaleDetail::model()->where(array('order_id'=>$id,'buy_type'=>'1'))->findAll();//购买商品
		else
			$this->pros = \OrderSaleDetail::model()->where(array('order_id'=>$id))->findAll();//所有商品
		//获取项目资金构成
		foreach ($this->pros as &$pro) {
			$pro['money_str']=$this->proMoneyStr($pro['id']);
		}
		return $this;
	}
	/**
	 * 卡券兑换模型 编辑卡券兑换时使用
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getVoucherModel($id=null,$all_pro=false){

		$this->o_s = \OrderVoucher::model()->findByPk($id);

		if(!$this->o_s) throw new CException("无此订单！");

		if(!$all_pro)
			$this->pros = \OrderSaleDetail::model()->where(array('order_id'=>$id,'buy_type'=>'3'))->findAll();//购买商品
		else
			$this->pros = \OrderSaleDetail::model()->where(array('order_id'=>$id,'buy_type'=>'3'))->findAll();//所有商品
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
		$money=\OrderSaleDetailMoney::model()->where(array('detail_id'=>$detail_id))->findAll();//money 构成
		$money_str=array();
		foreach ($money as $v) {
			$money_str[]=$v['capital_id']."|".$v['money'];
		}
		$money_str=implode(',',$money_str);
		return $money_str;
	}



	/**
	 * 购买订单 编辑 草稿状态保存
	 * @param array $data 创建订单需要的数组
	 * @return  int order_id 
	 */
	public function editNew($order_id,$data){
		//删除订单数据 （外键关联删除关联数据） 保留原有id SN
		$old_order = \OrderSale::model()->findByPk($order_id);
		$data['id']=$order_id;
		$data['sn']=$old_order->sn;
		//$old_order->orderDelete();
		if(!$old_order->orderDelete()){
			$this->_error[]=$old_order->popErr();
			return false;
		}
		return $this->saveNew($data);
	}



	/*****工具方法****/

	/**
	 * 获取商品信息
	 * @param  [type]  $id   [description]
	 * @param  integer $type [description]
	 * @return [type]        [description]
	 */
	public function getProInfo($id,$type=1){
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


	public function getCuName($id=null){
		$id=is_null($id)?$this->o_s->cu_id:$id;
		$info = \CustomerInfo::model()->findByPk($id);
		if($info)
			return $info->name;
		return "";
	}
	//(1购买、2充值、3项目兑换、4积分兑换,5还款)
	public function getOrderType($type=null){
		$type=is_null($type)?$this->o_s->type:$type;
		$type_all=array('0'=>"还款",'1'=>"购买",'2'=>"充值",'3'=>"项目兑换",'4'=>"分兑换");
		if(isset($type_all[$type]))
			return $type_all[$type];
		return "";
	}
	//制单人姓名
	public function getEmployName($id=null){
		$id=is_null($id)?$this->o_s->made_id:$id;
		$info = \EmployUser::model()->findByPk($id);
		if($info)
			return $info->netname;
		return "";
	}
    //订单付款状态
    public function getOrderPayStatus($type=null){
        $type=is_null($type)?$this->o_s->pay_status:$type;
        $type_all=array('0'=>"未付款",'1'=>"已付款");
        if(isset($type_all[$type]))
            return $type_all[$type];
        return "";
    }

    /**
     * 获取客户的资金账户
     */
    public function getCuCapitals($cu_id){
        $customer_info = \CustomerInfo::model()->findByPk($cu_id);
        $capital_list = \CompanyCapital::model()
            ->field('t.*')
            ->where("t.type=1 and comp_id={$customer_info->company_id} and status=1" )
            ->findAll();
        return $capital_list;
    }

    /**
     * 获取客户的资金账户详情
     */
    public function getCuCapitalDetails($cu_id){
        $customer_info = \CustomerInfo::model()->findByPk($cu_id);
        $capital_list = \CompanyCapital::model()
            ->field('t.*,IFNULL(cc.balance,0.00) AS balance')
            ->join("customer_capital as cc on cc.capital_id=t.id")
            ->where("t.type=1 and t.comp_id={$customer_info->company_id} and cc.cu_id={$cu_id}" )
            ->findAll();
        return $capital_list;
    }

    /**
     * 获取用户充值的账户和金额
     * @param $order_id
     * @return mixed
     */
    public function getCzCuCapitals($order_id){
        $capital_list=\CompanyCapital::model()
            ->field('t.*,IFNULL(os.price,0.00) AS price')
            ->join("order_sale_detail as os on os.pro_id=t.id")
            ->where("os.order_id={$order_id} and os.pro_type=4" )
            ->findAll();
        return $capital_list;
    }

    /**
     * 获取订单销售人员和业绩比例
     * @param $order_id
     * @return mixed
     */
    public function getUserEffect($order_id){
        $effect_list = \OrderEffectUser::model()
            ->field("t.*,eu.netname as name,ep.position_name as position_name")
            ->join("employ_user as eu on t.user_id=eu.id")
            ->join("employ_position as ep on eu.position_id=ep.id")
            ->where("t.order_id={$order_id}")
            ->findAll();
        return $effect_list;
    }

    /**
     * 获取充值订单账户详细
     * @param $order_id
     * @return mixed
     */
    public function getOrderSaleDetail($order_id){
        $order_sale_detail = \OrderSaleDetail::model()
            ->field("t.*")
            ->where("t.order_id={$order_id} and pro_type=4 and buy_type=1")
            ->findAll();
        return $order_sale_detail;
    }

    /**
     * 获取赠送的账户金额
     * @param $order_id
     * @return mixed
     */
    public function getOrderSaleMoney($order_id){
        $order_sale_money = \OrderSaleMoney::model()
            ->field("t.*")
            ->where("t.order_id={$order_id} and type=1")
            ->findAll();
        return $order_sale_money;
    }

    /**
     * 获取客户欠款订单
     * @param $cu_id
     * @return mixed
     */
    public function getArrearsOrder($cu_id){
        $order_sale = \OrderSale::model()
            ->field("t.*")
            ->where("t.cu_id={$cu_id} and current_arrears>0")
            ->findAll();
        return $order_sale;
    }

    /**
     * 获取客户欠款订单详细
     * @param $cu_id
     * @return mixed
     */
    public function getArrearsOrderDetail($cu_id){
        $order_sale_detail = \OrderSaleDetail::model()
            ->field("t.*")
            ->where("t.cu_id={$cu_id} and arrears>0")
            ->findAll();
        return $order_sale_detail;
    }
    /**
	 * 付款 （期初）
	 * @param  [type] $order_id [description]
	 * @param  [type] $post     [description]
	 * @return [type]           [description]
	 */
	public function payForInit($order_id){
		try{
			//开启事务处理  
			$this->db->beginT();
			$order = \OrderSale::model()->findByPk($order_id);
			$order->attributes=$data;

			//整单赠送账户资金写入个人账户
            $order_sale_money = $this->getOrderSaleMoney($order_id);
            if(!empty($order_sale_money)){
                foreach($order_sale_money as $money){
                	$arr=array();
					$arr['capital_id']	=$money['capital_id'];
					$arr['money']		=$money['cash'];
					$arr['cu_id']		=$order->cu_id;
					$arr['rel_id']		=$order->id;
					$arr['rel_type']	=1;
					$arr['pos_dir']		="in";
					\CustomerCapital::model()->edit($arr);
                    //\CustomerCapital::model()->updateCustomerCapital($order->cu_id,$money['capital_id'],$money['cash']);
                    //\CustomerCapital::model()->updateCustomerCapitalLog($order->cu_id,$money['capital_id'],$money['cash'],$order);
                }
            }

			$order->status 			= 1;	
			$order->pay_status 		= 1;	
			$order->pay_time 		= time();	
			if(!$order->save()) throw new CException("订单付款失败！");


			//账户流水写入
			$this->addCapitals($order_id);
			//项目、产品、卡券写入卡内剩余
			$this->addRePro($order_id);
			//更新客户账户欠款
			\CustomerInfo::model()->addArrears($order->cu_id,$order->arrears);
			$CustomerInfo=\CustomerInfo::model()->findByPk($order->cu_id);
			$CustomerInfo->cart_init=2;
			$CustomerInfo->save();
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $order->id;
	}
	/**
	 * 付款 （购买）
	 * @param  [type] $order_id [description]
	 * @param  [type] $post     [description]
	 * @return [type]           [description]
	 */
	public function pay($order_id,$data=null){
		try{
			//开启事务处理  
			$this->db->beginT();
			$cash_card=0;		//整单现金账户卡扣合计
			$arrears=0;			//整单欠款合计

			//商品详细写入
			foreach ($data['pro'] as $pro) {
				$card_cash=0;
				foreach ($pro['capital_money'] as $c_m) {
					if($c_m['money']==0) continue;
					$model_c_m = new \OrderSaleDetailMoney;
					$model_c_m->capital_id 	= $c_m['capital_id'];
					$model_c_m->money 		= $c_m['money'];
					$model_c_m->detail_id 	= $pro['detail_id'];
					if(!$model_c_m->save()) throw new CException("订单创建失败,资金详细写入失败！");
					$card_cash += $c_m['money'];
				}
				$detail = \OrderSaleDetail::model()->findByPk($pro['detail_id']);
				$detail->arrears=$pro['arrears'];
				$detail->var_price=$detail['pay_price']-$pro['arrears'];
				$detail->card_cash=$card_cash;			//现金账户卡扣金额
				$detail->cash = $detail->pay_price-$detail->card_cash-$detail->arrears;//实收现金计算
				if(!$detail->save()) throw new CException("订单付款失败,详单付款失败！");
				$cash_card	+= $card_cash;
				$arrears	+= $pro['arrears'];
			}


			$order = \OrderSale::model()->findByPk($order_id);
			$order->attributes=$data;

            // 充值订单更新
            if(!empty($data['cz'])){
                foreach ($data['cz'] as $cz) {
                    $order_detail = \OrderSaleDetail::model()->findByPk($cz['detail_id']);
                    $order_detail->cash = $cz['price'];
                    if(!$order_detail->save()) throw new CException("充值订单详情更新失败！");
                	$arr=array();
					$arr['capital_id']	=$cz['capital_id'];
					$arr['money']		=$cz['price'];
					$arr['cu_id']		=$order->cu_id;
					$arr['rel_id']		=$order->id;
					$arr['rel_type']	=1;
					$arr['pos_dir']		="in";
					\CustomerCapital::model()->edit($arr);
                    //\CustomerCapital::model()->updateCustomerCapital($order->cu_id,$cz['capital_id'],$cz['price']);
                    //\CustomerCapital::model()->updateCustomerCapitalLog($order->cu_id,$cz['capital_id'],$cz['price'],$order);
                }
            }

            //整单赠送账户资金写入个人账户
            $order_sale_money = $this->getOrderSaleMoney($order_id);
            if(!empty($order_sale_money)){
                foreach($order_sale_money as $money){
                	$arr=array();
					$arr['capital_id']	=$money['capital_id'];
					$arr['money']		=$money['cash'];
					$arr['cu_id']		=$order->cu_id;
					$arr['rel_id']		=$order->id;
					$arr['rel_type']	=1;
					$arr['pos_dir']		="in";
					\CustomerCapital::model()->edit($arr);
                    //\CustomerCapital::model()->updateCustomerCapital($order->cu_id,$money['capital_id'],$money['cash']);
                    //\CustomerCapital::model()->updateCustomerCapitalLog($order->cu_id,$money['capital_id'],$money['cash'],$order);
                }
            }
            //积分
            $config=\CompanyInfo::getConfig('score_config'); 
			if($config['openAll']=='true' && $config['openBySale']=='true'){
				$_score=($order->cash+$order->cash_pos+$order->cash_tra+$order->cash_other)*$config['scoreBl'];
				if ($config['scoreFor']=='all') {//所有顾客
					$order->score=$_score+$order->score;
				}else{
					$cmodel = \CustomerInfo::model()
					->field("cm.is_member")
					->join("config_membership cm on t.membership_id=cm.id")
					->find("t.id={$order->cu_id}");
					if ($cmodel->is_member && $config['scoreFor']==1) {
						$order->score=$_score+$order->score;
					}else{
						$order->score=$_score+$order->score;
					}
				}
			}

            //更新 order_sale
			$order->cash_card		= $cash_card;
			$order->arrears 		= $arrears;
			$order->current_arrears = $arrears;
			$order->status 			= 1;	
			$order->pay_status 		= 1;	
			$order->pay_time 		= time();	
			if(!$order->save()) throw new CException("订单付款失败！");
			//更新客户积分
			if($order->score){
				\CustomerInfo::model()->addScore($order->cu_id,$order->score,$order->id);
			}
			//入会购买
			if($order->rh_id>0){
				$cu_model = \CustomerInfo::model()->findByPk($order->cu_id);
				$cu_model->membership_id=$order->rh_id;
				$cu_model->enrollment_time=time();
				if(!$cu_model->save()) throw new CException('入会升级失败！');
			}

			//账户流水写入
			$this->addCapitals($order_id);
			//项目、产品、卡券写入卡内剩余
			$this->addRePro($order_id);
			//更新客户账户欠款
			\CustomerInfo::model()->addArrears($order->cu_id,$arrears);

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $order->id;
	}

	/**
	 * 订单数据写入客户账户流水
	 * @param  [type] $order_id     [订单id]
	 * @param  [type] $dir     		[入 or 出 （账户资金）]
	 * @return [CException]         [抛出异常]
	 */
	public function addCapitals($order_id,$dir="out"){
		$capital_list=array();//整单账户流水数据收集
		$list=\OrderSaleDetailMoney::model()
			->field('t.capital_id,t.money,d.cu_id')
			->join("order_sale_detail as d on d.id=t.detail_id")
			->where("d.order_id=".$order_id )
			->findAll();
		foreach ($list as $value) {
			if(isset($capital_list[$value['capital_id']])) 
				$capital_list[$value['capital_id']]+=$value['money'];
			else
				$capital_list[$value['capital_id']]=$value['money'];
		}
		$cu_id=$list[0]['cu_id'];
		foreach ($capital_list as $capital_id => $money) {
			$data=array();
			$data['capital_id']	=$capital_id;
			$data['money']		=$money;
			$data['cu_id']		=$cu_id;
			$data['rel_id']		=$order_id;
			$data['rel_type']	=1;
			$data['pos_dir']	=$dir;
			\CustomerCapital::model()->edit($data);
		}
	}
	/**
	 * 订单数据写入客户卡内剩余 项目、产品、卡券
	 * @param  [type] $order_id     [订单id]
	 * @param  [type] $dir     		[入 or 出 ]
	 * @return [CException]         [抛出异常]
	 */
	public function addRePro($order_id,$buy_type=array(1,2)){
		if (is_array($buy_type)) {
			$sqlw=" and buy_type in (".implode(',',$buy_type).")";
		}else{
			$sqlw=" and buy_type=".(int)$buy_type;
		}
		$detail_list = \OrderSaleDetail::model()->where("order_id={$order_id} {$sqlw}")->findAll();
		foreach($detail_list as $pro){
			switch ($pro['pro_type']) {
				case '1':
					\CustomerReProject::model()->add($pro);
					break;
				case '2':
					\CustomerProdStorage::model()->add($pro);
					break;
				case '3':
					\CustomerVoucher::model()->add($pro);
					break;
				default:
					break;
			}
		}
	}

	/**
	 * 获取订单现金卡扣分类合计（现金属性账户）
	 * @return [array] [description]
	 */
	public function getXJList(){
		$res=array();
		$list=\OrderSaleDetailMoney::model()
			->field('t.capital_id,t.money,cc.name')
			->join("order_sale_detail as d on d.id=t.detail_id")
			->join("company_capital as cc on cc.id=t.capital_id")
			->where("cc.type=1 AND d.order_id=".$this->o_s->id )
			->findAll();
		if($list){
			foreach ($list as $v) {
				$res[$v['name']]+=$v['money'];
			}
		}
		return $res;
	}

}