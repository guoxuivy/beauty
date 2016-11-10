<?php
/**
 * 客户寄存表
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerProdStorage extends ActiveRecord
{
	public function tableName() {
		return 'customer_prod_storage';
	}
	
	/**
	 * 卡内剩余项目购买添加
	 * @param  array $Data  [detail表row] 
	 * @param  object $model 
	 * @return 
	 */
	public function add($data)
	{
		$company_info=\Ivy::app()->user->getState('company_info');
		//检测是否已经存在寄存单
		$model = $this->find("order_id={$data['order_id']} and type={$data['buy_type']}");
		if(!$model){
			$storage = new CustomerProdStorage;
			$storage->order_id		=$data['order_id'];
			$storage->sn			=$this->buildSN();
			$storage->cu_id 		=$data['cu_id'];
			$storage->dept_id		=$data['md_id'];
			$storage->type		    =$data['buy_type'];
			$storage->create_user 	=\Ivy::app()->user->id;
			if(!$storage->save()) throw new CException("寄存单添加失败!");
			$model=$storage;
		}
		//写入detail表
		$d = new CustomerProdStorageDetail;

		$d->storage_id=$model->id;
		$d->detail_id=$data['id'];
		$d->cu_id=$data['cu_id'];
		$d->product_id=$data['pro_id'];
		$d->total_num=$data['num'];
		$d->remain_num=$data['num'];
		if(!$d->save()) throw new CException("添加失败！");
		if ($company_info['has_invo']=='Y') {//有进销存功能
			$comp_id = \Ivy::app()->user->comp_id;
            $dept_id = \Ivy::app()->user->dept_id;
			$pro_m=\invo\Dept::model()->find("comp_id={$comp_id} AND dept_id={$dept_id} AND product_id={$d->product_id} AND status=1");
            if(empty($pro_m))
            {
                return true;
            }
            $pro_m->storage_num=$pro_m->storage_num+$d->total_num;
            if(!$pro_m->save()) throw new CException("分仓商品更新失败!");
		}
	}


	/**
	 * 新建一个sn号 寄存
	 * @return [type] [description]
	 */
	public function buildSN(){
		$sn_model = \CompanySn::model()->nowNum('jc');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='jc'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}


	/**
	 * 获取状态
	 * @param 1购买、2赠送、3卡券兑换
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'1'		=> '购买',
			'2'		=> '赠送',
			'3'	=> '兑换',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}
			else {
				return false;
			}
		}
	}


}	