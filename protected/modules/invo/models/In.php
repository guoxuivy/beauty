<?php
namespace invo;
/**
 * 商品入库
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class In extends ActiveRecord
{

	public function tableName() {
		return 'invo_in';
	}
	/**
	 * 验证规则
	 * @return boolen
	 */
	public function rules()
	{
		return array(
			array('from_id, comp_id, dept_id, create_user', 'require'),
			array('from_id, comp_id, dept_id, create_user, type', 'number'),
		);
	}
	

	/**
	 * 数据保存 入库 用于调拨入库1、分仓退货2
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$m = new In;
			$m->sn 		= $data['sn']?$data['sn']:$this->buildSN();
			$m->type 	= $data['type'];
			$m->from_id = $data['from_id'];
			$m->comp_id = \Ivy::app()->user->comp_id;
			$m->dept_id = \Ivy::app()->user->dept_id;
			$m->create_user = \Ivy::app()->user->id;
			$m->in_status = '1';//0、未入库 1、已入库
			$m->in_time = time();
			$m->note = $data['note'];
			if(!$m->save()) throw new CException("入库失败！");
			//入库单详细处理
			if($data['in_num']){
				if(array_sum($data['in_num'])==0) throw new CException("入库数量为0！");
				foreach ($data['in_num'] as $k=>$in_num) {
					if(empty($in_num)) continue;
					$d_id=$data['d_id'][$k];
					$t_d=TransferDetail::model()->findByPk($d_id);
					//更新调拨单出库数量
					$t_d->in_num += $in_num;
					if($t_d->in_num > $t_d->num || !$t_d->save()){
						throw new CException("入库数量大于调拨数量！");
					}
					$d_m = new InDetail;
					$d_m->detail_id=$d_id;
					$d_m->order_id=$m->id;
					$d_m->product_id=$t_d->product_id;

					//检测分仓是否存在此商品，不存在则新建
					$in_dept_model=Dept::model()->getProDept($t_d->product_id);//分仓此商品情况
					$d_m->dept_pro_id=$in_dept_model->id;
					$d_m->before_num=$in_dept_model->num;
					$d_m->in_num=$in_num;
					$d_m->after_num=$in_num+$in_dept_model->num;
					if(!$d_m->save()) throw new CException("入库失败！");
					//更新库存
					$in_dept_model->num=$d_m->after_num;
					if(!$in_dept_model->save()){
						//throw new CException($in_dept_model->popErr());
						throw new CException("库存更新失败！");
					} 
				}
			}
			//更新调拨单属性
			$this->checkT($data['from_id']);
			
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $m->id;
	}

	/**
	 * 检测调拨单是否全部入库 并更新调拨单属性
	 * 1已出库 2已全部入库 3.已部分入库  1、3 =>2
	 * @return [type] [description]  
	 */
	protected function checkT($tid){
		$m = Transfer::model()->findByPk($tid);
		if($m->state=='1'||$m->state=='3'){
			$res = TransferDetail::model()->where("order_id={$tid} AND num<>in_num")->count();
			if($res==0)
				$m->state=2;
			else
				$m->state=3;
			$m->in_time=time();
			if(!$m->save()) throw new CException("调拨单更新失败！");
		}
	}

	/**
	 * 数据保存 入库  用于采购入库4、购买退回5
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function savenData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			//先生成调拨单
			$t = new Transfer;
			$t_data['type']=$data['type'];
			$t_data['comp_id']=\Ivy::app()->user->comp_id;
			$t_data['create_user']=\Ivy::app()->user->id;
			$t_data['create_time']=time();
			$t_data['from_id']=$data['from_id'];
			$t_data['to_id']=\Ivy::app()->user->dept_id;
			$t_data['state']=2;
			$t_data['note'] = $data['note'];
			if(!$t->saveData($t_data)) throw new CException("入库失败1！");

			//生成入库单
			$m = new In;
			$m->sn 		= $data['sn']?$data['sn']:$this->buildSN();
			$m->type 	= $data['type'];
			$m->from_id = $t->id;	//刚生成的调拨单ID
			$m->comp_id = \Ivy::app()->user->comp_id;
			$m->dept_id = \Ivy::app()->user->dept_id;
			$m->create_user = \Ivy::app()->user->id;
			$m->in_status = '1';//0、未入库 1、已入库
			$m->in_time = time();
			$m->note = $data['note'];
			
			$m->save();
			//入库单详细、调拨单详细 处理
			if($data['in_num']){
				if(array_sum($data['in_num'])==0) throw new CException("入库数量为0！");
				foreach ($data['in_num'] as $k=>$in_num) {
					if(empty($in_num)) continue;
					$d_id=$data['d_id'][$k]; //此时为商品信息表对应的id

					//检测入库分仓是否存在此商品，不存在则新建
					$in_dept_model=Dept::model()->getProDept($d_id);//分仓此商品情况
					//生成调拨单详单
					$t_d = new TransferDetail;
					$t_d_data['order_id']=$t->id;
					$t_d_data['product_id']=$d_id;
					$t_d_data['dept_pro_id']=$in_dept_model->id;
					$t_d_data['num']=$in_num;
					$t_d_data['in_num']=$in_num;
					if(!$t_d->saveData($t_d_data)) throw new CException("入库失败3！");

				
					// 生成入库单详单
					$d_m = new InDetail;
					$d_m->detail_id=$t_d->id; 
					$d_m->order_id=$m->id;
					$d_m->product_id=$t_d->product_id;
					$d_m->dept_pro_id=$in_dept_model->id;
					$d_m->before_num=$in_dept_model->num;
					$d_m->in_num=$in_num;
					$d_m->after_num=$in_num+$in_dept_model->num;
					$d_m->save();
					//更新库存
					$in_dept_model->num=$d_m->after_num;
					$in_dept_model->save();
					
				}
			}else{
				throw new CException("未选择入库商品！");
			}
			
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $m->id;
	}


	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN(){
		$sn_model = \CompanySn::model()->nowNum('in');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='in'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}

	/**
	 * 获取入库类型 与 调拨单保持一致
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'1'		=> '调拨入库',
			'2'		=> '分仓退货',
			'4'		=> '采购入库',
			'5'		=> '购买退回',
		);
		if($Pk === null){
			return $Array;
		}else{
			if(array_key_exists($Pk, $Array)){
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}

	/**
	 * 获取入库状态 0、未入库 1、已入库
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'0'		=> '等待入库',
			'1'		=> '入库成功',
		);
		if($Pk === null){
			return $Array;
		}else{
			if(array_key_exists($Pk, $Array)){
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}

	/**
	 * 获取入库单 发出单位
	 * @param  [type] $type [description]
	 * @param  [type] $id   本记录id
	 * @return [type]       [description]
	 */
	public static function getFromDept($id){
		$res=self::model()
            ->field("t.type,it.from_id")
            ->join('invo_transfer it ON it.id=t.from_id')
            ->findByPk($id);
		if($res){
			if($res->type==4){
				return \SupplierInfo::model()->findByPk($res->from_id)->name;
			}elseif($res->type==5){
				return \CustomerInfo::model()->findByPk($res->from_id)->name;
			}else{
				return \EmployDept::model()->findByPk($res->from_id)->dept_name;
			}
		}
		return "";
	} 
}	