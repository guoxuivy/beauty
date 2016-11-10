<?php
namespace pos;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 前台开单操作通用方法 
 */
class PracticeModel extends Model
{
	public $o_s=null;//实操单 实例
	public $pros=array();//实操项目  detail 数组
	/**
	 * 实操单 确认卡扣
	 * @param int id 
	 * @return  
	 */
	public function PracticeHk($id){
		$model = $this->getPracticeModel($id,true);
		$order=$model->o_s;
		try{
			//开启事务处理  
			$this->db->beginT();
			$pro_err='';
			foreach ($model->pros as $detail) {
				$dModel=\PracticeOrderDetail::model()->findByPk($detail['id']);
				$CustomerReProject  =\CustomerReProject::model()->find("detail_id={$detail['detail_id']} AND cu_id={$order->cu_id} AND re_num<>0");
				$dModel->before_num = $CustomerReProject->re_num;
				$dModel->after_num  = $dModel->before_num-$dModel->use_num;
				if ($dModel->after_num<0) {
					throw new CException("卡内剩余项目不够扣,耗卡失败！");
				}
				if (!$dModel->save()) {
					throw new CException("实操详情保存错误,耗卡失败！");
				}
				$CustomerReProject->re_num   =$dModel->after_num;
				$CustomerReProject->user_num =$CustomerReProject->user_num+$detail['use_num'];
				if (!$CustomerReProject->save()) {
					throw new CException("客户剩余项目保存错误,耗卡失败！");
				}
				$OrderSaleDetail=\OrderSaleDetail::model()->findByPk($dModel->detail_id);
				if ($OrderSaleDetail->buy_type==1 && $OrderSaleDetail->num) {//购买
					$sc_price=sprintf("%.2f",substr(sprintf("%.3f", ($OrderSaleDetail->pay_price/$OrderSaleDetail->num*$dModel->use_num)), 0, -2));
					$OrderSaleDetail->var_price=$OrderSaleDetail->var_price-$sc_price;
					if($OrderSaleDetail->var_price>=0)
					{
						if ($dModel->after_num==0)
							$OrderSaleDetail->var_price=0;//项目余额的零头清零处理
						if (!$OrderSaleDetail->save()) {
							throw new CException("购买详情保存错误,耗卡失败！");
						}
					}else
					{
						throw new CException("项目余额不够扣,耗卡失败！");
					}
				}
				//根据配方耗库存中的额外数量(K未完)
				$company_info=\Ivy::app()->user->getState('company_info');
				$comp_id=\Ivy::app()->user->comp_id;
				$dept_id=\Ivy::app()->user->dept_id;
				if ($company_info['has_invo']=='Y') {//有进销存功能 有配方的
					$has_formula=true;
					if ($dModel->formula_id>0) {
						$ProjectFormulaCompose=\ProjectFormulaCompose::model()
		                						->field("pi.id,pi.name,t.num")
		                						->join('product_info pi ON t.rel_id=pi.id')
		                						->findAll("formula_id={$dModel->formula_id} AND t.rel_type=1");
		               	$pro_has_invo=true;
		                foreach ((array)$ProjectFormulaCompose as $value) {
		                	$pro_m=\invo\Dept::model()->find("comp_id={$comp_id} AND dept_id={$dept_id} AND product_id={$value['id']} AND status=1");
		                	if ($pro_m) {//存在扣减
		                		$pro_m->extra_num=$pro_m->extra_num-$value['num'];
		                		$pro_m->save();
		                	}else //不存在
		                	{
		                		if($pro_has_invo)
		                			$pro_has_invo=false;
		                	}
		                }
					}else{//没有配方的
						$has_formula=false;
					}
	                if ((!$has_formula) || (!$has_invo)) {
	                	$ProjectInfo=\ProjectInfo::model()->findByPk($dModel->project_id);
	                	$pro_err.='<br>'.$ProjectInfo->name;
	                }
	            }


				$ldata['re_pro_id']  =$CustomerReProject->id;
				$ldata['rel_id']     =$order->id;
				$ldata['rel_type']   =3;
				$ldata['before_num'] =$detail['before_num'];
				$ldata['user_num']   =$detail['use_num'];
				$ldata['after_num']  =$detail['after_num'];
				$ldata['rel_ext']    =$detail['id'];
				$ldata['pos_dir']    ='out';
				\CustomerReProLog::model()->add($ldata);
			}
			$order->status=1;
			$order->pay_time=time();
			if(!$order->save()) throw new CException("实操单保存错误,耗卡失败！");
			$CustomerInfo=\CustomerInfo::model()->findByPk($order->cu_id);
			$CustomerInfo->last_time=time();//更新到店时间
			$CustomerInfo->status=1;//强制更新至活跃状态
			$CustomerInfo->save();
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		if($pro_err)
			return $pro_err;
		return true;
	}
	/**
	 * 实操单 创建 草稿状态保存 (兼容编辑)
	 * @param array $data 创建卡券兑换需要的数组
	 * @return  int order_id 
	 */
	public function savePracticeNew($data){
		try{
			//开启事务处理  
			$this->db->beginT();
			$order              = new \PracticeOrder;
			$order->id          = $data['order_id']?$data['order_id']:null;
			$order->sn          = $data['sn']?$data['sn']:$order->buildSN();
			$order->md_id       = \Ivy::app()->user->dept_id;
			$order->comp_id     = \Ivy::app()->user->comp_id;
			$order->cu_id       = $data['cu_id'];
			$order->create_user = \Ivy::app()->user->id;
			$order->note        = $data['note'];
			
			if(!$order->save()) throw new CException("实操单创建失败！");
			//商品详细写入
			foreach ($data['pro'] as $pro) {
				$detail             = new \PracticeOrderDetail;
				$detail->por_id     = $order->id;
				$detail->detail_id  = $pro['detail_id'];
				$OrderSaleDetail    =\OrderSaleDetail::model()->findByPk($detail->detail_id);
				$detail->project_id = $OrderSaleDetail->pro_id;
				if(empty($pro['formula_id']))//选择默认配方
				{
					$ProjectFormula    =\ProjectFormula::model()->find("comp_id={$order->comp_id} AND project_id={$detail->project_id} AND is_default='true' AND status=1");
					$pro['formula_id'] = (int)$ProjectFormula->id;
				}
				
				$ProjectInfo    =\ProjectInfo::model()->findByPk($detail->project_id);
				if($OrderSaleDetail->buy_type=='1'){
					switch ($ProjectInfo->pra_type) {
						case '1':
							$detail->pra_price  =  $ProjectInfo->price*$pro['use_nums'];
							break;
						case '2':
							$detail->pra_price  =  $pro['use_nums']*round( ($OrderSaleDetail->price*$OrderSaleDetail->rebate/100-$OrderSaleDetail->card_gift)/$OrderSaleDetail->num );
							break;
						default:
							$detail->pra_price  =  $ProjectInfo->pra_money*$pro['use_nums'];
							break;
					}
				}
				if($OrderSaleDetail->buy_type=='2'||$OrderSaleDetail->buy_type=='3'){
					switch ($ProjectInfo->pra_type_z) {
						case '1':
							$detail->pra_price  =  $ProjectInfo->price*$pro['use_nums'];
							break;
						default:
							$detail->pra_price  =  $ProjectInfo->pra_money_z*$pro['use_nums'];
							break;
					}
				}

				
				$detail->formula_id = $pro['formula_id'];
				$detail->operators  =$pro['operators'];
				$CustomerReProject  =\CustomerReProject::model()->find("detail_id={$detail->detail_id} AND cu_id={$order->cu_id} AND re_num<>0");
				$detail->before_num = $CustomerReProject->re_num;
				$detail->use_num    =$pro['use_nums'];
				$detail->after_num  = $detail->before_num>0?$detail->before_num-$detail->use_num:$detail->before_num;
				if(!$detail->save()){
					throw new CException("实操单创建失败,详单制单失败！");
				} 
				if ($detail->operators) { //实操业绩写入
					$operators=@explode(',', $detail->operators);
					$_count=count($operators);
					if ($operators && $_count) {
						foreach ($operators as $key => $value) {
							$PracticeEffectUser= new \PracticeEffectUser;
							$PracticeEffectUser->detail_id=$detail->id;
							$PracticeEffectUser->user_id=$value;
							$PracticeEffectUser->rate=sprintf('%.2f',(100/$_count));
							$PracticeEffectUser->save();
						}
						
					}
				}
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
	 * 实操单 编辑 草稿状态保存
	 * @param array $data 数组
	 * @return  int order_id 
	 */
	public function editPracticeNew($order_id,$data){
		//删除实操单数据 （外键关联删除关联数据） 保留原有id SN
		$old_order = \PracticeOrder::model()->findByPk($order_id);
		$data['id']=$order_id;
		$data['sn']=$old_order->sn;
		//$old_order->orderDelete();
		if(!$old_order->orderDelete()){
			$this->_error[]=$old_order->popErr();
			return false;
		}
		return $this->savePracticeNew($data);
	}


	/**
	 * 实操单模型 编辑实操单时使用
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getPracticeModel($id=null){
		$this->o_s = \PracticeOrder::model()->findByPk($id);
		if(!$this->o_s) throw new CException("无此订单！");
		$this->pros = \PracticeOrderDetail::model()->where(array('por_id'=>$id,'status'=>1))->findAll();//所有商品
		return $this;
	}

}