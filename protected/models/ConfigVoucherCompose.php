<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 卡券 组成
 */
use Ivy\core\ActiveRecord;
class ConfigVoucherCompose extends ActiveRecord
{
	public function tableName() {
		return 'config_voucher_compose';
	}

	/**
	 * 根据卡券id 获取项目、产品组成
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getPros($id=null,$type=null){
		$proj_list=$prod_list=array();
		$map['voucher_id']=array('eq',$id);
		if($type)
			$map['pro_type']=array('eq',$type);
		$res = $this->where($map)->findAll();
		if($res){
			foreach ($res as $k => $v) {
				if($v['pro_type']==1){
					$v['pro_name'] = ProjectInfo::model()->field(array("name"))->findByPk($v['pro_id'])->name;
					array_push($proj_list,$v);
				}else{
					$v['pro_name'] = ProductInfo::model()->field(array("name"))->findByPk($v['pro_id'])->name;
					array_push($prod_list,$v);
				}
			}
		}
		$res=array('proj_list'=>$proj_list,'prod_list'=>$prod_list);
		return $res;
	}
	/**
	 * 获取项目、产品组成(格式 XX项目  XX次 或者 XX产品  XX件)
	 * @param  [type] $id   [description]
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	public static function getProsStr($id=null,$type=null)
	{
		$_str='';
		$map['voucher_id']=array('eq',$id);
		if($type)
			$map['pro_type']=array('eq',$type);
		$res = self::model()->where($map)->findAll();
		if($res){
			foreach ($res as $k => $v) {
				if($v['pro_type']==1){
					$v['pro_name'] = ProjectInfo::model()->field(array("name"))->findByPk($v['pro_id'])->name;
					$_str.="{$v['pro_name']} {$v['num']}次,";
				}else{
					$v['pro_name'] = ProductInfo::model()->field(array("name"))->findByPk($v['pro_id'])->name;
					$_str.="{$v['pro_name']} {$v['num']}件,";
				}
			}
			if($_str)
				$_str=substr($_str,0,strlen($_str)-1); 
		}
		return $_str;
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