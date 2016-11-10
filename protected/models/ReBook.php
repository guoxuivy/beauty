<?php
/**
 * 邀约本
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class ReBook extends ActiveRecord
{
	public function tableName(){
		return 're_book';
	}

	/**
	 * 配置预约时间段
	 * @return array
	 */
	public function getTimes(){
		return array(
			1=>'09:00',
			2=>'09:30',
			3=>'10:00',
			4=>'10:30',
			5=>'11:00',
			6=>'11:30',
			7=>'12:00',
			8=>'12:30',
			9=>'13:00',
			10=>'13:30',
			11=>'14:00',
			12=>'14:30',
			13=>'15:00',
			14=>'15:30',
			15=>'16:00',
			16=>'16:30',
			17=>'17:00',
			18=>'17:30',
			19=>'18:00',
			20=>'18:30',
			21=>'19:00',
			22=>'19:30',
			23=>'20:00',
			24=>'20:30',
			25=>'21:00',
			26=>'21:30',
			27=>'22:00',
			28=>'22:30',
			29=>'23:00',
			30=>'23:30',
			31=>'24:00',
		);
	}

	/**
	 * 获取房间状态
	 * @return array
	 */
	public function getStatus(){
		return array(
			1=>'预约',
			2=>'占用',
			3=>'空闲',
		);
	}

	/**
	 * 添加预约
	 * @param $data
	 * @return bool
	 * @throws CException
	 */
	public function addNew($data){

		$dept_id = $data['dept_id'];
		$company_id = \Ivy::app()->user->comp_id;
		$times = $this->getTimes();
		$time = date('Y-m-d');
		$time_arr = array();
		for($i=$data['start_time'];$i<$data['end_time'];$i++){
			$time_arr[] = $i;
		}
		$ymd = date('Ymd',strtotime($data['ymd']));
		if(!empty($data['rebook_id'])){
			$model = \ReBook::model()->findByPk($data['rebook_id']);
		}else{
			$model = new ReBook;
		}
		$model->ymd = $ymd;
		$model->cu_id = $data['cu_id'];
		$model->company_id = $company_id;
		$model->dept_id = $dept_id;
		$model->room_id = $data['room_id'];
		$model->bed_id = $data['bed_id'];
		$model->operator_ids = $data['operators'];
		$model->times = implode(',',$time_arr);
		$model->starttime = strtotime($time.$times[$data['start_time']].':00');
		$model->endtime = strtotime($time.$times[$data['end_time']].':00');
		$model->status = $data['status'];
		$model->create_time = date('Y-m-d H:i:s');
		$model->update_time = date('Y-m-d H:i:s');

		if(!$model->save()) throw new CException("添加预约失败！");
		return true;
	}

	/**
	 * 获取门店不同状态下的预约
	 * @param $where
	 * @return mixed
	 */
	public function getRebookList($where){
		$list = self::model()
			->field("t.*,cu.name as cu_name,cr.name as room_name")
			->join('customer_info cu on cu.id=t.cu_id')
			->join('company_room cr on cr.id=t.room_id')
			->where($where)
			->findAll();
		return $list;
	}

	/**
	 * 通过room_id和time
	 * @param $where
	 * @return array
	 */
	public function getCuRebook($where){
		$lists = $this->getRebookList($where);
		$data = array();
		foreach($lists as $list){
			$cu_name = \CustomerInfo::model()->findByPk($list['cu_id'])->name;
			foreach(explode(',',$list['times']) as $time){
				if(array_key_exists($list['room_id'].':'.$time,$data)){
					$data[$list['room_id'].':'.$time]['cu_name'] .= ';'.$cu_name;
				}else{
					$data[$list['room_id'].':'.$time]['cu_name'] = $cu_name;
				}
				$data[$list['room_id'].':'.$time]['rebook_id'] = $list['id'];
				$data[$list['room_id'].':'.$time]['status'] = $list['status'];
			}
		}
		return $data;
	}

	/**
	 * 获取预约客户名
	 * @param $room_id
	 * @param $time_id
	 * @param $time
	 * @return mixed
	 */
	public function getCuName($room_id,$time_id,$time){
		$dept_id = \Ivy::app()->user->dept_id;
		$cu = \ReBook::model()
			->field("cu.name")
			->join('customer_info cu on cu.id=t.cu_id')
			->where("t.room_id={$room_id} and find_in_set({$time_id},t.times) and t.ymd={$time} and t.dept_id={$dept_id}")
			->find();
		return $cu['name'];
	}

	/**
	 * 获取当天的实操单列表
	 * @param $time
	 * @param $status
	 * @return mixed
	 */
	public function getPractice($time,$status,$dept_id){
		$list = \PracticeOrder::model()
			->field("t.*,cu.name as cu_name")
			->join('customer_info cu on cu.id=t.cu_id')
			->where("t.status in ({$status}) and FROM_UNIXTIME(UNIX_TIMESTAMP(t.last_time),'%Y%m%d')={$time} and t.md_id={$dept_id}")
			//->group("t.cu_id")
			->findAll();
		return $list;
	}

	/**
	 * 获取当天的实操单列表(按客户去重)
	 * @param $time
	 * @param $status
	 * @return mixed
	 */
	public function _getPractice($time,$status,$dept_id){
		$list = \PracticeOrder::model()
			->field("t.*,cu.name as cu_name")
			->join('customer_info cu on cu.id=t.cu_id')
			->where("t.status in ({$status}) and FROM_UNIXTIME(UNIX_TIMESTAMP(t.last_time),'%Y%m%d')={$time} and t.md_id={$dept_id}")
			->group("t.cu_id")
			->findAll();
		return $list;
	}

	/**
	 * 获取美容师名称
	 * @param $operator_ids
	 * @return array
	 */
	public function getOperatorNames($operator_ids){
		$operator_names = array();
		foreach(explode(',',$operator_ids) as $operator_id){
			$operator = \EmployUser::model()->findByPk($operator_id);
			$operator_names[$operator_id] = $operator->netname;
		}
		return $operator_names;
	}

	/**
	 * 获取实操美容师名称
	 * @param $practice_id
	 * @return array
	 */
	public function getPraOperName($practice_id){
		$item = \PracticeOrderDetail::model()
			->where("por_id={$practice_id}")->find();
		return $this->getOperatorNames($item['operators']);
	}

	/**
	 * 获取实操项目名称
	 * @param $practice_id
	 * @return string
	 */
	public function getPraProNames($practice_id){
		$result = \PracticeOrderDetail::model()
			->field("t.*,pro.name as project_name")
			->join('project_info pro on pro.id=t.project_id')
			->where("t.por_id={$practice_id}")
			->findAll();
		$names = array();
		foreach($result as $_result){
			if(!in_array($_result['project_name'],$names)){
				$names[] = $_result['project_name'];
			}
		}
		return implode('；',$names);
	}

}	