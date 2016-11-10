<?php
/**
 * @Author: K
 * @Date:   2015-10-12 16:07:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-13 10:53:51
 */
/**
 * 项目基本信息
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class Out extends ActiveRecord
{
	public function tableName() {
		return 'invo_out';
	}

	/**
	 * 项目数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		$this->attributes=$data;
		//var_dump($this);die;
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
		try{
			//开启事务处理  
			$this->db->beginT();
			$this->save();
			if (isset($data['formula'])) {//配方保存
				foreach ($data['formula'] as $f_k=>$value) {
					if(empty($value['list']))
						continue;
					//更新添加原有配方
					$form_model=new ProjectFormula;
					$form_model->attributes=$value;
					$form_model->formula_name="配方".($f_k+1);
					$form_model->comp_id=$company_info['id'];
					$form_model->project_id=$this->id;
					$form_model->save();

					//重新添加配方组成
					ProjectFormulaCompose::model()->deleteAll("formula_id=".$form_model->id);
					if(isset($value['list'])){
						foreach ($value['list'] as $compose) {
							$compose_model=new ProjectFormulaCompose;
							$compose_model->formula_id=$form_model->id;
							$compose_model->rel_id=$compose['prod_id'];
							$compose_model->num=$compose['num'];
							$compose_model->rel_type=1;
							$compose_model->save();
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
		return true;
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
	 * 获取出库类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null,$ForEdit=false){
		$Array = array(
			'1'		=> '领用出库',
			'2'		=> '领取出库',
			'3'		=> '调拨出库',
			'4'		=> '采购退货',
		);
		if($Pk === null) {
			if ($ForEdit) {
				unset($Array[2]);
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
		$model = new \ProjectInfo;
		if($id)
			$model->findByPk((int)$id);
		if($model->isNewRecord){
			//添加
		}else{
			//编辑
			$model->formulas=ProjectFormula::model()->getByProj($model->id);//配方
		}
		
		return $model;
	}


}