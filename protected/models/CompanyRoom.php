<?php
/**
 * 房间管理
 */
use Ivy\core\ActiveRecord;
class CompanyRoom extends ActiveRecord
{
	/**
	 * 获取部门列表
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public static function getList($where=' AND status=1 AND type=2')
	{
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		$_arr= self::model()->findAll("comp_id={$company} {$where}");
		$list=array();
		if($_arr)
			foreach ($_arr as $key => $value) {
				$list[$value['id']]=$value['dept_name'];
			}
		return $list;
	}
	public function tableName() {
		return 'company_room';
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$this->attributes=$data;
			$this->save();
			//床位处理
			if(isset($data['room_name']))
				\CompanyRoomBed::model()->upbed($this->id,$data['room_name']);

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $this->id;
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
	 * 获取数组 
	 * @return array 格式array(id=>name)
	 */
	public static function getArray()
	{
		$company_info=\ivy::app()->user->getState('company_info');
		$comp_id=$company_info['id'];
		$Arr=array();
		$model=self::model()->findAll('comp_id='.(int)$comp_id.' AND status=1');
		if ($model) {
			foreach ($model as $val) {
				$Arr[$val['id']]=$val['dept_name'];
			}
		}
		return $Arr;
	}
}	