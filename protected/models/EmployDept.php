<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2016-04-07 17:53:04
 */
/**
 * 员工部门
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class EmployDept extends ActiveRecord
{
	/**
	 * 获取部门列表 默认获取门店数据
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public static function getList($where='status=1 AND type=2',$only=true)
	{
		$company=\Ivy::app()->user->getState('company_info');
		$company=$company['id'];
		if($only)
		switch (\Ivy::app()->user->position_id) {
			case '1'://公司总经理
				break;
			case '5'://门店经理
				$where.=" AND id=".\Ivy::app()->user->dept_id;
				break;
			case '8'://门店前台
				$where.=" AND id=".\Ivy::app()->user->dept_id;
				break;
			case '10'://公司管理员
				break;
			case '11'://平台管理员
				break;
			default:
				break;
		}
		if($where===true)//传入为ture时，为报表使用的删除了的门店将显示 后加已删除
			$where='type=2';
		$_arr= self::model()->findAll("comp_id={$company} AND {$where}");
		$list=array();
		if($_arr)
			foreach ($_arr as $key => $value) {
				$del='';
				if($value['status']==-1){
					$del='(已删除)';
				}elseif ($value['status']==0) {
					$del='(已停用)';
				}
				$list[$value['id']]=$value['dept_name'].$del;
			}
		return $list;
	}
	public function tableName(){
		return 'employ_dept';
	}
	/**
	 * 门店数据保存 同时更新床位数据
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data){
		try{
			//开启事务处理
			$this->db->beginT();
			$this->attributes=$data;
			if(!$this->save()) throw new CException("门店修改失败！");
			if($this->type==2&&!isset($data['id'])){
				for ($i=1; $i <= $this->room_num; $i++) {
					$room = new \CompanyRoom;
					$room->comp_id	=$this->comp_id;
					$room->store_id	=$this->id;
					$room->name 	="房".$i;
					if(!$room->save()) throw new CException("房间创建失败！");
					$bed = new \CompanyRoomBed;
					$bed->room_id	=$room->id;
					$bed->name 	="默认床";
					if(!$bed->save()) throw new CException("床位创建失败！");
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

	public static function getSName($id)
	{
		$s_info=self::model()->findByPk($id);
		
		return $s_info->dept_name;
	}

	/**
	 * 获取该公司的所有可用部门 总公司部门+门店
	 */
	public static function getDeptList(){
		return \EmployDept::model()->findAll('status = 1 and (comp_id ='.\Ivy::app()->user->comp_id.')');//id=1 or 
	}

	/**
	 * 根据ids获取门店信息
	 */
	public static function getMDList($ids=null){
		if($ids===null)
			return \EmployDept::model()->field("id,dept_name")->findAll('status = 1 and comp_id ='.\Ivy::app()->user->comp_id);
		if($ids=='0')
			return array(array('id'=>'0','dept_name'=>'全连锁'));
		if(is_array($ids)){
			$ids = implode(",",$ids);
		}
		return \EmployDept::model()->field("id,dept_name")->findAll("id IN ($ids)");
	}


}	