<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-29 17:58:27
 */
/**
 * 客户卡内剩余项目
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerReProject extends ActiveRecord
{
	public function tableName() {
		return 'customer_re_project';
	}
	/**
	 * 客户卡内剩余项目总疗次
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getSumByUser($uid)
	{
		$uid=(int)$uid;
		$_arr= self::model()
			->field(array("sum(`t`.`re_num`) as sum_re_num"))
			->where("t.cu_id={$uid} AND t.re_num<>0")
			//->group("t.project_id")
			->find();
		return $_arr;
	}
	/**
	 * 客户卡内剩余项目
	 * @param  integer $uid [description]
	 * @return [type]        [description]
	 */
	public static function getListByUser($uid,$limit=6)
	{
		$uid=(int)$uid;
		$_arr= self::model()
			->field(array("*","sum(`t`.`re_num`) as sum_re_num"))
			->join('project_info pi on t.project_id=pi.id')
			->where("t.cu_id={$uid} AND t.re_num<>0")
			->group("t.project_id")
			->limit(0,$limit)
			->findAll();
		return $_arr;
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
			->join('project_info pi on t.project_id=pi.id')
			->join('order_sale_detail d on t.detail_id=d.id')
			->where("t.cu_id={$uid} AND t.re_num<>0")
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
		$res= \CustomerReProject::model()
			->field(array("*,t.id as id,d.num as gm_num,t.re_num as sy_num, ROUND(d.price*d.rebate/100,2) as cj_price "))
			->join('project_info pi on t.project_id=pi.id')
			->join('order_sale_detail d on t.detail_id=d.id')
			->where("t.id={$id}")
			->find();
		return $res;
	}


	/**
	 * 卡内剩余项目购买添加
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function add($data)
	{
		$model = new CustomerReProject;
		$model->detail_id	=$data['id'];
		$model->cu_id 		=$data['cu_id'];
		$model->project_id	=$data['pro_id'];
		$model->re_num 		=$data['num'];
		$model->user_num	=0;
		if(!$model->save()) throw new CException("添加失败！");

		$ldata['re_pro_id']		=$model->id;
		$ldata['before_num']	=0;
		$ldata['user_num']		=$data['user_num'];
		$ldata['after_num']		=$data['num'];
		$ldata['rel_id']		=$data['order_id'];
		$ldata['rel_type']		=1;
		$ldata['rel_ext']		=$data['id'];
		$ldata['pos_dir']		='in';

		\CustomerReProLog::model()->add($ldata);
	}

	/**
	 * 卡内剩余项目购买添加
	 * @param  array $Data  [detail]
	 * @param  object $model 
	 * @return 
	 */
	public function refund($data)
	{
		$model = CustomerReProject::model()->findByPk($data['re_pro_id']);

		$before_num=$model->re_num;
		$model->re_num=$before_num-$data['num'];
		if ($model->re_num<0) {
			throw new CException("卡内剩余项目不够扣！");
		}
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

		if(!$model->save()) throw new CException("卡内剩余更新失败！");

		$ldata['re_pro_id']		=$model->id;
		$ldata['before_num']	=$before_num;
		$ldata['user_num']		=$data['num'];
		$ldata['after_num']		=$model->re_num;
		$ldata['rel_id']		=$data['refund_id'];
		$ldata['rel_type']		=2;
		$ldata['rel_ext']		=$data['id'];
		$ldata['pos_dir']		='out';

		\CustomerReProLog::model()->add($ldata);
	}





}	