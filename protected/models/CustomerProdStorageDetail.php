<?php
/**
 * 客户寄存表 详细
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerProdStorageDetail extends ActiveRecord
{
	public function tableName() {
		return 'customer_prod_storage_detail';
	}
	
	/**
	 * 客户卡内剩余项目 不分组
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getReProList($uid)
	{
		$uid=(int)$uid;
		$_arr= self::model()
		->field(array("*,t.id as id"))
		->join('product_info pi on t.product_id=pi.id')
		->join('order_sale_detail d on t.detail_id=d.id')
		->where("t.cu_id={$uid} AND t.remain_num<>0")
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
		$res= \CustomerProdStorageDetail::model()
				->field(array("*,t.id as id"))
				->field(array("*,t.id as id,d.num as gm_num,t.remain_num as sy_num, ROUND(d.price*d.rebate/100,2) as cj_price "))
				->join('product_info pi on t.product_id=pi.id')
				->join('order_sale_detail d on t.detail_id=d.id')
				->where("t.id={$id}")
				->find();
		return $res;
	}

	/**
	 * 寄存商品退款
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function refund($data)
	{
		$model = CustomerProdStorageDetail::model()->findByPk($data['re_pro_id']);

		$before_num=$model->remain_num;
		$model->remain_num=$before_num-$data['num'];

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

		if(!$model->save()) throw new CException("寄存更新失败！");
		$company_info=\Ivy::app()->user->getState('company_info');
		if ($company_info['has_invo']=='Y') {//有进销存功能
			$comp_id = \Ivy::app()->user->comp_id;
            $dept_id = \Ivy::app()->user->dept_id;
			$pro_m=\invo\Dept::model()->find("comp_id={$comp_id} AND dept_id={$dept_id} AND product_id={$model->product_id} AND status=1");
            if(empty($pro_m))
            {
                return true;
            }
            $pro_m->storage_num=$pro_m->storage_num-$data['num'];
            if(!$pro_m->save()) throw new CException("分仓商品更新失败!");
		}
		//日志待扩展
		// $ldata['re_pro_id']		=$model->id;
		// $ldata['before_num']	=$before_num;
		// $ldata['user_num']		=$data['num'];
		// $ldata['after_num']		=$model->re_num;
		// $ldata['rel_id']		=$data['refund_id'];
		// $ldata['rel_type']		=2;
		// $ldata['rel_ext']		=$data['id'];
		// $ldata['pos_dir']		='out';

		// \CustomerProdStorageDetailLog::model()->add($ldata);
	}


}	