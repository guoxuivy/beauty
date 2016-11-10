<?php
use Ivy\core\ActiveRecord;
class EmployUser extends ActiveRecord {
	protected $_cache = false;
	/**
	 * 获取人员列表 默认顾问6 ()
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public static function getList($where=' AND position_id=6')
	{
		$where.=' AND status=1';
		$company=\Ivy::app()->user->comp_id;
		switch (\Ivy::app()->user->position_id) {
			case '1'://公司总经理
				break;
			case '5'://门店经理
				$where.=" AND dept_id=".\Ivy::app()->user->dept_id;
				break;
			case '8'://门店前台
				$where.=" AND dept_id=".\Ivy::app()->user->dept_id;
				break;
			case '10'://公司管理员
				break;
			case '11'://平台管理员
				break;
			default:
				break;
		}
		$_arr= self::model()->findAll("comp_id={$company} {$where}");
		$list=array();
		if($_arr)
			foreach ($_arr as $key => $value) {
				$list[$value['id']]=$value['netname'];
			}
		return $list;
	}
	/**
	 * 获取多个人员姓名
	 * @param  [type] $ids [description]
	 * @return [type]      [description]
	 */
	public static function getUserNames($ids)
	{
		if ($ids) {
			if (is_string($ids))
				$ids=explode(',',$ids);
			$_count=count($ids);
			if (is_array($ids)) 
				$ids=implode(',',$ids);
			if ($_count==1) {
				$model=self::model()->field(array("netname"))->findByPk($ids);
				return $model->netname;
			}else
			{
				$_arr=array();
				$model=self::model()->field(array("netname"))->findAll("id in ({$ids})");
				if($model)
				foreach ($model as $key => $value) {
					$_arr[]=$value['netname'];
				}
				return @implode(',',$_arr);
			}
		}
	}
	public function tableName() {
		return 'employ_user';
	}
	
	/**
	 * 数据保存
	 * 
	 * @param array $Data
	 *        	[description]
	 * @param object $model        	
	 * @return
	 *
	 */
	public function saveData($data) {
		$this->attributes = $data;
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
	public static function  getSex($Pk=null){
		$Array = array(
			'男'		=> '男',
			'女'		=> '女',
			'未填写'	=> '未填写',
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
}