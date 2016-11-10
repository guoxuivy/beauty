<?php
/**
 * @Author: K
 * @Date:   2015-03-30 15:39:03
 * @Last Modified by:   K
 * @Last Modified time: 2015-09-29 15:51:53
 */
/**
 * 项目基本信息
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class ProjectInfo extends ActiveRecord
{
	public function tableName() {
		return 'project_info';
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
			$map['name']=array('eq',$data['name']);
			$map['cate']=array('eq',$data['cate']);
			if($data['id'])
				$map['id']=array('neq',$data['id']);
			$d_m = $this->where($map)->find();//重名检测
			if($d_m){
				throw new CException("项目重名！");
			}
			//项目编码重复检测
			unset($map);
			if(isset($data['code'])&&!empty($data['code'])){
				$map['code']=array('eq',$data['code']);
				$map['status']=array('neq','-1');
				$map['comp_id']=array('eq',$company_info['id']);
				if($data['id'])
					$map['id']=array('neq',$data['id']);
				$d_m = $this->where($map)->find();
				if($d_m){
					throw new CException("项目编码重复！");
				}
			}

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

	/**
	 * 批量插入数据 模版插入
	 * @return 
	 */
	public static function insertFile($file_name) {
		$data = self::readExcel($file_name);
		array_shift($data);
		$count = count($data);
		$i = 0;
		//插入数据时出错的行
		$errorLineArr = array();
		@set_time_limit(0);
		foreach($data as $key => $val) {

			$io = $val[0];
			$name = $val[1];
			$top_cate = $val[2];
			$cate = $val[3];
			$price = empty($val[4]) ? 0 : $val[4];
			$num = empty($val[5]) ? 1 : $val[5];
			$practice_price = empty($val[6]) ? 0 : $val[6];
			$zs_practice_price = empty($val[7]) ? 0 : $val[7];
			$code = $val[8];



			if(empty($name) || empty($top_cate) || empty($cate)){
				$errorLineArr[] = $io;
				continue;
			}
			$projectModel = new \ProjectInfo();
			$projectModel->comp_id = \Ivy::app()->user->comp_id;
			$projectModel->supp_id = 0;
			$projectModel->name = $name;

			$TopProCate=\ProCate::model()->find("comp_id={$projectModel->comp_id}  AND status=1  AND name='{$top_cate}' AND type=1 AND level=1");
			if(empty($TopProCate)) {
				$TopProCate=new \ProCate;
				$TopProCate->fid=0;
				$TopProCate->comp_id=$projectModel->comp_id;
				$TopProCate->name=$top_cate;
				$TopProCate->level=1;
				$TopProCate->type=1;
				$TopProCate->save();
			}
			$ProCate=\ProCate::model()->find("comp_id={$projectModel->comp_id} AND fid={$TopProCate->id} AND name='{$cate}'  AND status=1 AND type=1 AND level=2");
			if (empty($ProCate)) {
				$ProCate=new \ProCate;
				$ProCate->fid=$TopProCate->id;
				$ProCate->comp_id=$projectModel->comp_id;
				$ProCate->name=$cate;
				$ProCate->level=2;
				$ProCate->type=1;
				$ProCate->save();
			}
			$projectModel->cate = $ProCate->id;
			$projectModel->code = $code;
			$projectModel->num = $num;
			$projectModel->price = $price;
			if ($practice_price) {
				$projectModel->pra_type =3;
				$projectModel->pra_money = $practice_price;
			}
			
			$projectModel->pra_type_z =3;
			$projectModel->pra_money_z = (int)$zs_practice_price;
			
			if($projectModel->save()) {
				$i++;
			} else {
				$errorLineArr[] = $val[0];
			}
		}
		$arr = array(
			'total' => $count,
			'success' => $i,
			'error_num'=>$count-$i,
			'error' => $errorLineArr,
		);
		return $arr;
	}
	/**
	 * 读取Excel，分析数据
	 * @param string $file_name 导入数据用的Excel文件位置
	 * @return array|boolean
	 */
	public static function readExcel($file_name) {
		if(!isset($file_name)) {
			return false;
		}
		\Ivy::importExt("excel/Spreadsheet_Excel_Reader");
		$data = new \Spreadsheet_Excel_Reader();
		// Set output Encoding.
		$data->setOutputEncoding('UTF-8');
		$data->read($file_name);
		
		$excel_data = array();
		for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
			$row_data = array();
			for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
				$row_data[] = trim($data->sheets[0]['cells'][$i][$j]);
			}
			$excel_data[] = $row_data;
		}
		return $excel_data;
	}

	/**
	 * 批量更新供应商
	 * @return [type] [description]
	 */
	public function cleanSupplier($map){
		$num = $this->updateData($this->tableName(),$map,array('supp_id'=>0));
		if($this->_cache)
			$this->getBehavior('ARcache')->flush();
		$this->lastSql=$this->db->lastSql;
		return $num;
	}


}