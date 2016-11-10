<?php
/**
 * @Author: K
 * @Date:   2015-03-31 12:01:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-08-10 11:25:07
 */
use Ivy\core\ActiveRecord;
class PracticeOrder extends ActiveRecord
{
	public function tableName() {
		return 'practice_order';
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
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
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
			'1'  => '已耗卡',
			'0'  => '等待耗卡',
			'-1' => '已废除',
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
     * 获取美容师姓名
     * @param null $operators
     * @return string
     */
    public static function  getOperators($operators=null){
        $operator_ids = explode(',',$operators);
        $operator_names = array();
        foreach($operator_ids as $operator_id){
            $operator = \EmployUser::model()->findByPk($operator_id);
            $operator_names[$operator_id] = $operator->netname;
        }
        return implode(';',$operator_names);
    }

	/**
	 * 订单删除  放弃外键关联删除 使用事务约束删除
	 * @param array $data 创建订单需要的数组
	 * @return  int order_id 
	 */
	public function orderDelete(){
		try{
			//开启事务处理  
			$this->db->beginT();

			\PracticeOrderDetail::model()->deleteAll("por_id=".$this->id);

			$this->delete();

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return true;
	}
	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN(){
		$sn_model = \CompanySn::model()->nowNum('sc');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='sc'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}

	/**
	 * 获取订单内容 字符串
	 * @param  $id 
	 * @return 
	 */
	public static function  getBody($id=null){
		$str="";
		$model = self::model()->findByPk($id);
		$list = \PracticeOrderDetail::model()
		->field("p.name,t.use_num")
		->join("project_info p ON p.id=t.project_id")
		->findAll("por_id=".$id);
		foreach ($list as $v) {
			$str.="实操".$v['name']."(".$v['use_num']."次)";
		}
		return $str;
	}


}