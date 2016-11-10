<?php
/**
 * @Author: K
 * @Date:   2015-03-30 16:23:42
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-03-30 15:58:07
 */
/**
 * 项目配方
 */
use Ivy\core\ActiveRecord;
class ProjectFormula extends ActiveRecord
{
	public function tableName() {
		return 'project_formula';
	}
	/**
	 * 获取配方名称
	 * @param  string $id [description]
	 * @return [type]     [description]
	 */
	public static function getName($id='')
	{
		if ($id) {
			$id=(int)$id;
			$model=self::model()->findByPk($id);
			return $model->formula_name;
		}
		return false;
	}

	/**
	 * 获取没有配方的项目数量
	 * @param  string $id [description]
	 * @return [type]     [description]
	 */
	public static function getNoNum()
	{
		$count_sql="SELECT COUNT(*) AS num FROM (SELECT t.*,  f.id AS f_id FROM `project_info` t LEFT JOIN `project_formula` f ON f.`project_id`=t.`id` 
            WHERE t.`status` > 0 AND t.`comp_id`=".\Ivy::app()->user->comp_id." GROUP BY t.`id` HAVING f_id IS NULL) c";
		$res=self::model()->findBySql($count_sql);
		return $res['num'];
	}

	


	/**
	 * 根据项目id 获取配方列表及组成
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getByProj($id=null,$status=null){
		$map['project_id']=array('eq',$id);
		if($status) $map['status']=array('eq',$status);
		$res = $this->where($map)->findAll();
		if($res){
			foreach ($res as $k => $v) {
				$c = ProjectFormulaCompose::model()
				->field(array("t.*","p.name as pname",'t.num as t_mun','p.code as code','p.status as p_status'))
				->join('product_info p on p.id=t.rel_id')
				->where('t.formula_id='.$v['id'])
				->findAll();
				$res[$k]['compose']=$c;
			}
		}else{
			$res=array();
		}
		return $res;
	}

	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data){
		$this->attributes=$data;
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
		if ($this->save()) {
			if (isset($data['compose'])) {//配方详情保存
				foreach ($data['compose'] as $value) {
					ProjectFormulaCompose::model()->saveData($value);
				}
			}
			return true;
		}
		return false;
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
	 *  更新默认 
	 * @return 
	 */
	public function updateDefault(){
		if($this->project_id)
		{
			$this->updateAll(array('is_default'=>'false'),"project_id={$this->project_id} AND is_default='true'");
			$this->is_default='true';
			return $this->update();
		}
		return false;
	}
	
}