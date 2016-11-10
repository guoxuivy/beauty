<?php
/**
 * @Author: 远程 供应商表
 * @Date:   2015-03-30 18:31:18
 * @Last Modified time: 2015-10-14 14:14:09
 */
use Ivy\core\ActiveRecord;
class SupplierInfo extends ActiveRecord
{
	protected $_cache = false;	

	//配置供应商远程数据库
	public function __construct($config=null){
		$this->_config = \Ivy::app()->C('supplier_pdo');
		parent::__construct($config);
	}

	public function tableName() {
		return 'company_info';
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		$this->attributes=$data;
		return $this->save();
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


	//获取本公司供应商列表 
	public static function getList() {
		$my = \CompanySupplier::model()->findAll('comp_id = '.\Ivy::app()->user->comp_id);
		$my_arr=array();
		if($my){
			foreach ($my as $key => $value) {
				$my_arr[]=$value['supp_id'];
			}
			$map['id'] = array('in',$my_arr);
		}

		$map['beauty_id'] = array('eq',\Ivy::app()->user->comp_id);
		$map['_logic'] = 'OR';
		$_arr= \SupplierInfo::model()->where($map)->findAll();
		$list=array();
		if($_arr)
			foreach ($_arr as $key => $value) {
				$list[$value['id']]=$value['comp_name'];
			}
		return $list;

	}
}	