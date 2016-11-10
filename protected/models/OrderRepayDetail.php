<?php
/**
 * 还款来源表
 */
use Ivy\core\ActiveRecord;
class OrderRepayDetail extends ActiveRecord
{
	public function tableName() {
		return 'order_repay_detail';
	}

    /**
     * 添加还款来源表
     * @param $repay
     * @param $order
     * @throws CException
     */
    public function addRepayDetail($repay,$order){
        $pro_info = explode('|',$repay['proInfo']);
        $this->deleteAll("order_id={$order->id} and order_detail_id={$pro_info[2]}");
        if(!empty($repay['cash'])){
            $detail = new \OrderRepayDetail();
            $id = $this->isNewModel($order->id,$pro_info[2],1);
            if(!empty($id)){
                $detail = \OrderRepayDetail::model()->findByPk($id);
            }
            $detail->order_id = $order->id;
            $detail->order_detail_id = $pro_info[2];
            $detail->cu_capital_id = null;
            $detail->before_money = $repay['arrears'];
            $detail->money = $repay['cash'];
            $detail->type = 1;
            $detail->create_time = date('Y-m-d H:i:s');
            if(!$detail->save()) throw new CException("订单创建失败,添加现金还款来源表失败！");
        }
        if(!empty($repay['capitals'])){
            foreach(explode(',',$repay['capitals']) as $capitals){
                $capital = explode('|',$capitals);
                $capital_info = \CustomerCapital::model()
                    ->field('t.*')
                    ->where("t.cu_id={$order->cu_id} and t.capital_id={$capital[0]}" )
                    ->findAll();
                $detail = new \OrderRepayDetail();
                $id = $this->isNewModel($order->id,$pro_info[2],2,$capital_info[0]['id']);
                if(!empty($id)){
                    $detail = \OrderRepayDetail::model()->findByPk($id);
                }
                $detail->order_id = $order->id;
                $detail->order_detail_id = $pro_info[2];
                $detail->cu_capital_id = $capital_info[0]['id'];
                $detail->before_money = $repay['arrears'];
                $detail->money = $capital[1];
                $detail->type = 2;
                $detail->create_time = date('Y-m-d H:i:s');
                if(!$detail->save()) throw new CException("订单创建失败,添加现金卡扣还款来源表失败！");
            }
        }
    }

    public function isNewModel($order_id,$order_detail_id,$type,$cu_capital_id=null){
        $map['t.order_id']	= 	array('eq',$order_id);
        $map['t.order_detail_id']	= 	array('eq',$order_detail_id);
        $map['t.type']	= 	array('eq',$type);
        if(!empty($cu_capital_id)){
            $map['t.cu_capital_id']	= 	array('eq',$cu_capital_id);
        }
        $this->where($map)->find();
        return $this->id;
    }

    /**
     * 获取还款的现金和卡扣现金信息
     * @param $order_id
     * @param $order_detail_id
     * @return array
     */
    public function getRepayMoney($order_id,$order_detail_id){
        $repayCard = \OrderRepayDetail::model()
            ->field("sum(t.money) as card")
            ->where("t.order_id={$order_id} and t.order_detail_id={$order_detail_id} and t.type=2")
            ->findAll();
        $repayCash = \OrderRepayDetail::model()
            ->field("sum(t.money) as cash")
            ->where("t.order_id={$order_id} and t.order_detail_id={$order_detail_id} and t.type=1")
            ->findAll();
        return array('card'=>$repayCard[0]['card'],'cash'=>$repayCash[0]['cash']);
    }

    public function updateArrears($data){
        //try{
            //开启事务处理
            //$this->db->beginT();
            $id = $data['order_id'];
            $order = \OrderSale::model()->findByPk($id);

            $repayCard = \OrderRepayDetail::model()
                ->field("t.*")
                ->where("t.order_id={$id} and t.type=2")
                ->findAll();
            $_repayCard = array();
            foreach($repayCard as $item){
                $_repayCard[$item['cu_capital_id']] += $item['money'];
            }
            foreach($_repayCard as $cu_capital_id=>$balance){
                $cu_capital = \CustomerCapital::model()->findByPk($cu_capital_id);
                // 现金账户流水
                $cu_capital_log = new \CustomerCapitalLog();
                $cu_capital_log->cu_capital_id = $cu_capital_id;
                $cu_capital_log->after_money  = $cu_capital->balance!=0?($cu_capital->balance-$balance):0;
                $cu_capital_log->money 		   = $balance;
                $cu_capital_log->before_money   = $cu_capital->balance;
                $cu_capital_log->rel_id        = $order->id;
                $cu_capital_log->rel_type      = 1;
                $cu_capital_log->rel_ext       = $order->note;
                $cu_capital_log->pos_dir       = 'out';
                if(!$cu_capital_log->save()) throw new CException("订单付款失败,更新客户账户流水失败！");
                $_SESSION['order'] = $cu_capital_log->save();
                // 现金账户卡扣
                $cu_capital->balance = $cu_capital->balance-$balance;
                $cu_capital->last_time = date('Y-m-d H:i:s');
                if(!$cu_capital->update()) throw new CException("订单付款失败,现金账户卡扣失败！");
            }

            $order_sale_detail = \OrderSaleDetail::model()
                ->field("t.*")
                ->join("order_repay_detail as ord on t.id=ord.order_detail_id")
                ->where("ord.order_id={$id}")
                ->group("t.id")
                ->findAll();
            $cash = 0;$cash_card = 0;
            foreach($order_sale_detail as $key=>$detail){
                $repay = $this->getRepayMoney($id,$detail['id']);
                // 更新欠款订单详细
                $order_detail = \OrderSaleDetail::model()->findByPk($detail['id']);
                $order_detail->arrears = $order_detail->arrears-($repay['cash']+$repay['card']);
                $order_detail->var_price = $order_detail['var_price']+($repay['cash']+$repay['card']);
                if(!$order_detail->update()) throw new CException("订单付款失败,更新订单详情失败！");

                // 更新欠款订单
                $old_order = \OrderSale::model()->findByPk($detail['order_id']);
                $old_order->current_arrears = $old_order->current_arrears-($repay['cash']+$repay['card']);
                if(!$old_order->update()) throw new CException("订单付款失败,更新订单详情失败！");

                // 现金和账户卡扣总和
                $cash += $repay['cash'];
                $cash_card += $repay['card'];
            }

            // 更新客户欠款
            $order = \OrderSale::model()->findByPk($id);
            $customer_info = \CustomerInfo::model()->findByPk($order->cu_id);
            $customer_info->arrears = $customer_info->arrears-($order->pay_price);
            $_SESSION['order'] = $customer_info->arrears.'==='.$order->pay_price.'==='.$id;
            if(!$customer_info->update()) throw new CException("订单付款失败,更新订单详情失败！");

            // 订单付款
            $order->cash_card = $cash_card;
            $order->cash = $data['cash'];
            $order->cash_pos = $data['cash_pos'];
            $order->pos_type = $data['pos_type'];
            $order->cash_tra = $data['cash_tra'];
            $order->tra_type = $data['tra_type'];
            $order->cash_other = $data['cash_other'];
            $order->other_type = $data['other_type'];
            $order->pay_time = time();
            $order->pay_status = 1;
            $order->status = 1;
            if(!$order->update()) throw new CException("订单付款失败！");
            return $order->cu_id;

        /*}catch(CException $e){
            $this->_error[]=$e->getMessage();
            $this->db->rollbackT();
            return false;
        }*/

    }
	
}	