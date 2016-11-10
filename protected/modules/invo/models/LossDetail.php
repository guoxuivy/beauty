<?php
namespace invo;
/**
 * @Author: K
 * @Date:   2015-10-12 16:07:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-22 14:10:44
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class LossDetail extends ActiveRecord
{
	public function tableName() {
		return 'invo_gain_loss_detail';
	}

	public function getByProj($id=null){
		$map['order_id']=array('eq',$id);
		$res = $this
			->field(array('t.*','tc.name AS top_name,c.name AS c_name,p.name AS p_name,p.code p_code,p.specs p_specs,p.unit p_unit,ind.num max_num'))
			->join('product_info p on p.id=t.product_id')
			->join('invo_dept ind on ind.id=t.dept_pro_id')
			->join('pro_cate c on p.cate=c.id')
			->join('pro_cate tc on c.fid=tc.id')
			->where($map)
			->findAll();
		
		return $res;
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
	 * 逻辑删除
	 * @return 
	 */
	public function delete(){
		return $this->updateStatus();
	}


	/***  **********************************************/

	/**
	 * 盘库损益单详细写入、并更新仓库商品库存
	 * @param $order_id
	 * @param $data
	 * @param $inventory_id
	 * @throws CException
	 */
	public function addNew($order_id,$data,$inventory_id){
		foreach($data as $item){
			$model = new LossDetail;
			$model->order_id = $order_id;
			$model->product_id = $item['product_id'];
			$model->dept_pro_id = $item['dept_pro_id'];
			$model->before_num = $item['curr_num'];
			$model->num = $item['balance_num'];
			$model->after_num = $item['check_num'];
			if(!$model->save()) throw new CException("损益单详单开单失败！");
			$this->updateDept($inventory_id,$item);
		}
	}

	/**
	 * 盘库更新仓库商品库存
	 * @param $inventory_id
	 * @param $item
	 * @throws CException
	 */
	public function updateDept($inventory_id,$item){
		$model_dept = Dept::model()->findByPK($item['dept_pro_id']);
		$model_dept->num = $item['check_num'];
		$model_inventory = Inventory::model()->findByPK($inventory_id);
		if($model_inventory->is_check_ext==1){
			$model_inventory_detail = InventoryDetail::model()->find("inventory_id={$inventory_id} and product_id={$item['product_id']}");
			$model_dept->extra_num = $model_inventory_detail['check_ext_num'];
		}
		if(!$model_dept->save()) throw new CException("更新仓库商品失败！");
	}

	/**
	 * 更新盘库单和盘库单详细
	 * @param $inventory_id
	 * @throws CException
	 */
	public function updateInventoryStatus($inventory_id){
		$model_inventory = Inventory::model()->findByPK($inventory_id);
		$model_inventory->status = 1;
		if(!$model_inventory->save()) throw new CException("更新盘库单状态失败！");
		$where = array('inventory_id'=>$inventory_id);
		$data = array('is_done'=>1);
		$result = InventoryDetail::model()->updateData('invo_inventory_detail',$where,$data);
		if($result===false) throw new CException("更新盘库单详细状态失败！");
	}
}