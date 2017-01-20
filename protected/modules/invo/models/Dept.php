<?php
namespace invo;
/**
 * 仓库
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class Dept extends ActiveRecord
{

	public function tableName() {
		return 'invo_dept';
	}

	/**
	 * 获取分仓商品id 不存在则创建此商品
	 * @return [type] [description]
	 */
	public function getProDept($proId){
		$map['comp_id']=array('eq',\Ivy::app()->user->comp_id);
		$map['dept_id']=array('eq',\Ivy::app()->user->dept_id);
		$map['status']=array('eq',1);
		$map['product_id']=array('eq',$proId);
		$m = $this->find($map);
		if($m){
			return $m;
		}else{
			$model = new Dept;
			$model->comp_id=\Ivy::app()->user->comp_id;
			$model->dept_id=\Ivy::app()->user->dept_id;
			$model->product_id=$proId;
			$model->num=0;
			$JC_m=\CustomerProdStorageDetail::model()
						->field('sum(t.`remain_num`) remain_num')
						->join('customer_prod_storage cps on t.storage_id=cps.id')
						->find("cps.dept_id={$model->dept_id} AND cps.status=1 AND t.product_id={$model->product_id}");
			if ($JC_m->remain_num>0) {
				$model->storage_num=$JC_m->remain_num;
			}
			if(!$model->save()) throw new CException("商品库存初始化失败！");
			return $model;
		}
	}

	/**
	 * 仓库中的商品状态
	 * @param null $Pk
	 * @return array|bool
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '正常',
			'0'		=> '停用',
			'-1'	=> '删除',
			'-2'	=> '未开帐',
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
	 * 获取商品列表操作
	 * @param $id
	 * @param $status
	 * @return string
	 */
	public function getAction($id,$status){
		$action = '';
		if($status!=-2){
			if($status==0){
				$action .= '<a class="btn default btn-xs blue-stripe change" href="javascript:" rel="'.$id.'">启用</a>';
			}else{
				$action .= '<a class="btn default btn-xs yellow-stripe change" href="javascript:" rel="'.$id.'">停用</a>';
			}
		}
		$action .= '<a rel="'.$id.'" href="javascript:;" class="btn default btn-xs red-stripe delete">删除</a>';
		return $action;
	}

	/**
	 * 写入期初盘库单和盘库单详细
	 * @param $data
	 * @return bool|null
	 */
	public function addInventory(){
		try{
			//开启事务处理
			$this->db->beginT();
			$comp_id = \Ivy::app()->user->comp_id;
			$dept_id = \Ivy::app()->user->dept_id;

			$where = array(
				'comp_id'=>\Ivy::app()->user->comp_id,
				'dept_id'=>\Ivy::app()->user->dept_id,
				'status'=>-2,
			);
			$data = array(
				'status'=>1,
				'last_time'=>date('Y-m-d H:i:s'),
			);
			Dept::model()->updateData('invo_dept',$where,$data);
			$data = Dept::model()->where("comp_id={$comp_id} and dept_id={$dept_id}")->findAll();
			$model = new Inventory;
			$model->comp_id = \Ivy::app()->user->comp_id;
			$model->dept_id = \Ivy::app()->user->dept_id;
			$model->start_time = time();
			$model->end_time = time();
			$model->type = 0;
			$model->is_check_ext = 1;
			$model->create_user = \Ivy::app()->user->id;
			$model->create_time = date('Y-m-d H:i:s');
			$model->status = 1;
			if(!$model->save()) throw new CException("盘库失败！");
			//盘库单详细处理
			foreach($data as $item){
				$model_detail = new InventoryDetail;
				$model_detail->inventory_id = $model->id;
				$model_detail->product_id = $item['product_id'];
				$model_detail->dept_pro_id = $item['id'];
				$model_detail->curr_num = $item['num'];
				$model_detail->curr_storage_num = $item['storage_num'];
				$model_detail->curr_ext_num = $item['extra_num'];
				$model_detail->check_num = $item['num'];
				$model_detail->check_ext_num = $item['extra_num'];
				$model_detail->is_done = 1;
				$model_detail->is_auto = 1;
				if(!$model_detail->save()) throw new CException("盘库单详细写入失败！");
				//统计寄存数量
				$JC_m=\CustomerProdStorageDetail::model()
						->field('sum(t.`remain_num`) remain_num')
						->join('customer_prod_storage cps on t.storage_id=cps.id')
						->find("cps.dept_id={$dept_id} AND cps.status=1 AND t.product_id={$item['product_id']}");
				if ($JC_m->remain_num>0) {
					$pro_m=Dept::model()->findByPk($item['id']);
					$pro_m->storage_num=$JC_m->remain_num;
					if(!$pro_m->save()) throw new CException("分仓商品更新失败!");
				}
			}
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
		}
		return true;
	}
}	