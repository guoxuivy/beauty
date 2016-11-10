<?php
namespace invo;
/**
 * 商品盘库
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class Inventory extends ActiveRecord
{

	public function tableName() {
		return 'invo_inventory';
	}

	/**
	 * 写入盘库单和盘库单详细
	 * @param $data
	 * @return bool|null
	 */
	public function addNew($data){
		try{
			//开启事务处理
			$this->db->beginT();
			$comp_id = \Ivy::app()->user->comp_id;
			$dept_id = \Ivy::app()->user->dept_id;
			$model = new Inventory;
			$model->comp_id = \Ivy::app()->user->comp_id;
			$model->dept_id = \Ivy::app()->user->dept_id;
			$model->start_time = $this->getStartTime();
			$model->end_time = time();
			$model->type = 1;
			$model->is_check_ext = $data['is_check_ext'];
			$model->create_user = \Ivy::app()->user->id;
			$model->create_time = date('Y-m-d H:i:s');
			$model->status = 0;
			if(!$model->save()) throw new CException("盘库失败！");
			//盘库单详细处理
			foreach($data['data'] as $item){
				if($item['num']!=''){
					$model_detail = new InventoryDetail;
					$model_dept = Dept::model()->where("comp_id={$comp_id} and dept_id={$dept_id} and product_id={$item['product_id']}")->find();
					$model_detail->inventory_id = $model->id;
					$model_detail->product_id = $item['product_id'];
					$model_detail->dept_pro_id = $item['dept_pro_id'];
					$lastInfo = $this->getLastInfo($item['product_id']);
					$model_detail->last_num = $lastInfo['last_num'];
					$model_detail->last_ext_num = $lastInfo['last_ext_num'];
					$model_detail->curr_num = $model_dept['num'];
					$model_detail->curr_storage_num = $model_dept['storage_num'];
					$model_detail->curr_ext_num = $model_dept['extra_num'];
					$model_detail->in_num = $this->getInNum($this->getStartTime(),time(),$item['product_id']);
					$model_detail->out_num = $this->getOutNum($this->getStartTime(),time(),$item['product_id']);
					$model_detail->out_ext_num = '';
					if($item['num']-$model_dept['num']>0){
						$_num = $item['num']-$model_dept['num'];
						$model_detail->gain_num = $_num;
					}else{
						$_num = $model_dept['num']-$item['num'];
						$model_detail->loss_num = $_num;
					}
					$model_detail->check_num = $item['num'];
					if($data['is_check_ext']==1){
						$extra_num = $item['extra_num'];
					}else{
						$extra_num = $model_dept['extra_num'];
					}
					$model_detail->check_ext_num = $extra_num;
					$model_detail->balance_num = $_num;
					$model_detail->is_done = 0;
					$model_detail->is_auto = 0;
					if(!$model_detail->save()) throw new CException("盘库单详细写入失败！");
				}
			}
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $model->id;
	}

	/**
	 * 获取盘库周期开始日期
	 * @return int
	 */
	public function getStartTime(){
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_id = \Ivy::app()->user->dept_id;
		$result = Inventory::model()->where("comp_id={$comp_id} and dept_id={$dept_id} and status=1")
			->order("id desc")->find();
		if(!empty($result)){
			$start_time = $result['end_time'];
		}else{
			$_result = Dept::model()->where("comp_id={$comp_id} and dept_id={$dept_id} and status=1")
				->order("id asc")->find();
			$start_time = strtotime($_result['last_time']);
		}
		return $start_time;
	}

	/**
	 * 获取商品上期库存数量和额外数量
	 * @param $product_id
	 * @return array
	 */
	public function getLastInfo($product_id){
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_id = \Ivy::app()->user->dept_id;
		$result = Inventory::model()->where("comp_id={$comp_id} and dept_id={$dept_id} and status=1")
			->order("id desc")->find();
		if(!empty($result)){
			$_result = InventoryDetail::model()->where("inventory_id={$result['id']} and product_id={$product_id}")
				->find();
			$data = array('last_num'=>$_result['check_num'],'last_ext_num'=>$_result['check_ext_num']);
		}else{
			$data = array('last_num'=>0,'last_ext_num'=>0);
		}
		return $data;
	}

	/**
	 * 获取入库数量
	 * @param $start_time
	 * @param $end_time
	 * @param $product_id
	 * @return int
	 */
	public function getInNum($start_time,$end_time,$product_id){
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_id = \Ivy::app()->user->dept_id;
		$result = InDetail::model()
			->field(array("t.*"))
			->join('invo_in i_i on t.order_id=i_i.id')
			->where("i_i.comp_id={$comp_id} and i_i.dept_id={$dept_id} and i_i.in_status=1 and t.product_id={$product_id}
			and i_i.in_time>={$start_time} and i_i.in_time<={$end_time} and i_i.status=1")
			->findAll();
		$in_num = 0;
		foreach($result as $_result){
			$in_num += $_result['in_num'];
		}
		return $in_num;
	}

	/**
	 * 获取出库数量
	 * @param $start_time
	 * @param $end_time
	 * @param $product_id
	 * @return int
	 */
	public function getOutNum($start_time,$end_time,$product_id){
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_id = \Ivy::app()->user->dept_id;
		$result = OutDetail::model()
			->field(array("t.*"))
			->join('invo_out i_o on t.order_id=i_o.id')
			->where("i_o.comp_id={$comp_id} and i_o.dept_id={$dept_id} and i_o.out_status=1 and t.product_id={$product_id}
			and i_o.out_time>={$start_time} and i_o.out_time<={$end_time} and i_o.status=1")
			->findAll();
		$out_num = 0;
		foreach($result as $_result){
			$out_num += $_result['out_num'];
		}
		return $out_num;
	}

	public function getInventoryStatus($Pk=null){
		$Array = array(
			'1'		=> '已入账',
			'0'		=> '未入账',
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

	/**
	 * 获取盘库周期
	 * @param $pk
	 * @return string
	 */
	public function getPkZhouQi($pk){
		$inventory = Inventory::model()->findByPk($pk);
		$start = date('Y/m/d',$inventory->start_time);
		$end = date('Y/m/d',$inventory->end_time);
		return $start.'-'.$end;
	}


}	