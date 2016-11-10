<?php
/**
 * 房间管理
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class CompanyRoomBed extends ActiveRecord
{

	public function tableName() {
		return 'company_room_bed';
	}
	/**
	 * 验证规则
	 * @return boolen
	 */
	public function rules()
	{
		return array(
			array('room_id, name', 'require'),
			array('room_id', 'number'),
		);
	}
	

	/**
	 * 更新床位名称 自动激活禁用床
	 * @param  [type] $room_id [房间id]
	 * @param  string $name    [床位名称 | 分割]
	 * @return [type]          [description]
	 */
	public function upbed($room_id,$name="") {
		$list=$this->findAll("room_id=".$room_id);//原来的

		$n_names = explode("@", $name);//新的
		
		$len=count($n_names);

		if(count($list)>$len) $len=count($list);

		for ($i=0; $i < $len; $i++) {
			$bed= new CompanyRoomBed;
			if(isset($list[$i])){
				$bed->attributes=$list[$i];
			}

			$bed->room_id=$room_id;

			if(count($n_names)>0){
				$bed->name=array_pop($n_names);
				$bed->status=1;
			}else{
				$bed->status=-1;
			}
			if(!$bed->save()){
				throw new CException($bed->popErr());
			}
		}

	}

	public function getBedStr($room_id){
		$str=array();
		$list = $this->findAll("status =1 AND room_id=".$room_id);
		foreach ($list as  $value) {
			$str[]=$value['name'];
		}
		return implode("|", $str);
	}

	public function _getBedStr($room_id){
		$str=array();
		$list = $this->findAll("status =1 AND room_id=".$room_id);
		foreach ($list as  $value) {
			$str[]=$value['name'];
		}
		return implode("@", $str);
	}

	public function getBedNum($room_id){
		$count = $this->findBySql("select count(*) as count from company_room_bed where status =1 AND room_id=".$room_id );
		return $count['count'];
	}

	

	
}	