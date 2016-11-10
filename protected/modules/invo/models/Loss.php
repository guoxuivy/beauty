<?php
namespace invo;
/**
 * @Author: K
 * @Date:   2015-10-12 16:07:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-23 14:31:23
 */
/**
 * 
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class Loss extends ActiveRecord
{
	public function tableName() {
		return 'invo_gain_loss';
	}

	/**
	 * 项目数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$m = new Loss;
			$m->attributes=$data;
			$code='gain';
			if ($m->type==-1) {
				$code='loss';
			}
			$m->sn 		= $data['sn']?$data['sn']:$this->buildSN($code);
			$m->comp_id = \Ivy::app()->user->comp_id;
			$m->dept_id = \Ivy::app()->user->dept_id;
			$m->create_user = \Ivy::app()->user->id;

			if(!$m->save()) throw new CException("损溢单保存失败！");
			//损益单详细处理
			if($data['detail']){
				foreach ($data['detail'] as $v) {
					if(empty($v)) continue;				
					$d_m = new LossDetail;
					$d_m->order_id=$m->id;
					
					$d_m->product_id=$v['pro_id'];
					$pro_m=Dept::model()->find("comp_id={$m->comp_id} AND dept_id={$m->dept_id} AND product_id={$d_m->product_id} AND status=1");
					if(empty($pro_m))
					{
						throw new CException("分仓无此商品！");
					}
					$d_m->dept_pro_id=$pro_m->id;
					$d_m->before_num=$pro_m->num;
					$d_m->num=$v['num'];
					if($m->type==-1)//报损
						$d_m->after_num=$d_m->before_num-$d_m->num;
					else//报溢
						$d_m->after_num=$d_m->before_num+$d_m->num;

					if($d_m->after_num<0)throw new CException("分仓商品不足！");
					if($d_m->save()){
						$pro_m->num=$d_m->after_num;
						$pro_m->save();
					}else{
						throw new CException("损溢详情保存失败!");
					}
				}
			}
			
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $m->id;
	}
	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN($code){
		$sn_model = \CompanySn::model()->nowNum($code);
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn=$code.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
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
	 * 获取类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'-1'	=> '报损',
			'1'		=> '报溢',
		);
		if($Pk === null) {
			if ($ForEdit) {
				unset($Array[3]);
			}
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

	public static function  getRegular($Pk=null){
		$Array = array(
			'1'		=> '日常',
			'0'		=> '盘库',
		);
		if($Pk === null) {
			if ($ForEdit) {
				unset($Array[3]);
			}
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
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '正常',
			'0'		=> '停用',
			'-1'	=> '删除',
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
	 * 逻辑删除
	 * @return 
	 */
	public function delete(){
		return $this->updateStatus();
	}

	/**
	 *  添加、编辑页面需要的模型
	 * @param  integer $status 
	 * @return 
	 */
	public function getEditModel($id=null){
		$model = new Loss;
		if($id)
			$model->findByPk((int)$id);
		if($model->isNewRecord){
			//添加
			$model->detail=array();
		}else{
			//编辑
			$model->detail=LossDetail::model()->getByProj($model->id);
		}
		
		return $model;
	}

	/***********************/

	/**
	 * 写入盘库损益单
	 * @param $_data
	 * @return bool
	 */
	public function addNew($_data){
		try{
			//开启事务处理
			$this->db->beginT();
			$data = $_data['data'];
			$inventory_id = $_data['inventory_id'];
			foreach($data as $key=>$item){
				if(!empty($item['loss_num'])){
					$loss_data[$key] = $item;
				}
				if(!empty($item['gain_num'])){
					$gain_data[$key] = $item;
				}
				if(empty($item['loss_num'])&&empty($item['gain_num'])){
					$ext_num_data[$key] = $item;
				}
			}
			if(!empty($loss_data)){
				$loss_id = $this->_saveData('loss',0,$inventory_id);
				LossDetail::model()->addNew($loss_id,$loss_data,$inventory_id);
			}
			if(!empty($gain_data)){
				$gain_id = $this->_saveData('gain',0,$inventory_id);
				LossDetail::model()->addNew($gain_id,$gain_data,$inventory_id);
			}
			if(!empty($ext_num_data)){
				foreach($ext_num_data as $item){
					LossDetail::model()->updateDept($inventory_id,$item);
				}
			}
			$Inventory = Inventory::model()->findByPk($inventory_id);
			$Inventory->status = 1;
			$Inventory->save();
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return $e->getMessage();
		}
		return true;
	}

	/**
	 * 盘库损益单--开单
	 * @param $code
	 * @param $is_regular
	 * @param $inventory_id
	 * @return null
	 * @throws CException
	 */
	public function _saveData($code,$is_regular,$inventory_id){
		if($code=='loss'){
			$type = -1;
			$note = '盘库报损';
		}else{
			$type = 1;
			$note = '盘库报溢';
		}
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_id = \Ivy::app()->user->dept_id;
		$model = new Loss;
		$model->sn = $this->buildSN($code);
		$model->comp_id = $comp_id;
		$model->dept_id = $dept_id;
		$model->type = $type;
		$model->is_regular = $is_regular;
		$model->inventory_id = $inventory_id;
		$model->create_user = \Ivy::app()->user->id;
		$model->create_time = date('Y-m-d H:i:s');
		$model->audit_status = 1;
		$model->audit_user = \Ivy::app()->user->id;
		$model->audit_time = time();
		$model->note = $note;
		$model->status = 1;
		if(!$model->save()) throw new CException("损益单开单失败！");
		return $model->id;
	}

	/**
	 * 获取出库单 发出单位
	 * @param  [type] $type [description]
	 * @param  [type] $id   [description]
	 * @return [type]       [description]
	 */
	public static function getFromDept($id){
		return '';
	} 


}