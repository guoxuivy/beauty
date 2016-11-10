<?php
namespace invo;
/**
 * @Author: K
 * @Date:   2015-10-12 16:07:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-11-12 14:21:56
 */
/**
 * 项目基本信息
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class Out extends ActiveRecord
{
	public function tableName() {
		return 'invo_out';
	}

	/**
	 * 项目数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$m = new Out;
			$m->sn 		= $data['sn']?$data['sn']:$this->buildSN();
			$m->comp_id = \Ivy::app()->user->comp_id;
			$m->dept_id = \Ivy::app()->user->dept_id;
			$m->create_user = \Ivy::app()->user->id;
			$m->type 	= $data['type'];
			$m->out_status = '1';//0、未入库 1、已入库
			$m->note=$data['note'];
			$data['comp_id']=$m->comp_id;
			$data['dept_id']=$m->dept_id;
			$data['create_user']=$m->create_user;
			$data['create_time']=time();
			$data['from_id']=$data['from_id']?$data['from_id']:\Ivy::app()->user->dept_id;
			$data['out_time']=time();
			$data['state']=$m->out_status;
			$data['note']=$m->note;
			switch ($m->type) {//1、调拨出库 2、领用出库 3、领取出库  4.采购退货
				case 1:
					$data['type']=1;
					$data['from_id']=Transfer::model()->saveData($data);

	                //推送消息
					$map['dept_id']=array('eq',$data['to_id']);
					$map['position_id']=array('eq',8); //门店前台
					$map['status']=array('eq',1);
					$list = \EmployUser::model()->field('id')->where($map)->findAll();
					$to_uids = array();
					foreach ($list as $value) {
					    $to_uids[]=$value['id'];
					}
					$sn_str = Transfer::model()->findByPk($data['from_id'])->sn;
					$dept_str = \EmployDept::model()->findByPk($m->dept_id)->dept_name;
					//
					$arr=array(
					    'to_uids'=>$to_uids,
					    'title'=>'【出库啦】',
					    'content'=>'调拨单<a href="'.\Ivy::app()->_route->url('invo/in/index').'">'.$sn_str.'</a>已与'.date("Y-m-d H:i",time()).' 从【'.$dept_str.'店】出库, 正在来的路上。请注意收货。（点击调拨单号， 可进入看调拨单据详情）',
					);
					\SmsMessage::send($arr);
					break;
				case 2://领用出库
					$data['from_id']=Recipients::model()->saveData($data);
					break;
				case 3://领取出库(单独逻辑)
					
					break;
				case 4:
					$data['type']=3;
					$data['from_id']=Transfer::model()->saveData($data);
					break;
				default:
					# code...
					break;
			}

			$m->from_id = $data['from_id'];//单据id

			$m->to_id=$data['to_id'];
			if($m->type==2)//领用单
				$m->to_id=\Ivy::app()->user->id;
			
			
			$m->out_time = time();
			
			if(!$m->save()) throw new CException("出库失败！");
			//出库单详细处理
			if($data['detail']){
				foreach ($data['detail'] as $v) {
					if(empty($v)) continue;				
					$d_m = new OutDetail;
					$d_m->order_id=$m->id;
					
					$d_m->product_id=$v['pro_id'];
					$pro_m=Dept::model()->find("comp_id={$m->comp_id} AND dept_id={$m->dept_id} AND product_id={$d_m->product_id} AND status=1");
					if(empty($pro_m))
					{
						throw new CException("分仓无此商品！");
					}
					$d_m->dept_pro_id=$pro_m->id;
					$d_m->before_num=$pro_m->num;
					$d_m->out_num=$v['num'];
					$d_m->after_num=$d_m->before_num-$d_m->out_num;
					if($d_m->after_num<0)throw new CException("分仓商品不足！");
					if($m->type==2)//领用
					{

						$td_m=new RecipientsDetail;
						$td_m->order_id=$m->from_id;
						$td_m->product_id=$d_m->product_id;
						$td_m->dept_pro_id=$d_m->dept_pro_id;
						$td_m->num=$d_m->out_num;
						$td_m->before_extra_num=$pro_m->extra_num;
						$ProductInfo=\ProductInfo::model()->findByPk($td_m->product_id);
						$td_m->to_extra_num=$ProductInfo->num*$td_m->num;
						$td_m->aftert_extra_num=$td_m->before_extra_num+$td_m->to_extra_num;
						$pro_m->extra_num=$td_m->aftert_extra_num;
					}else{
						$td_m=new TransferDetail;
						$td_m->order_id=$m->from_id;
						$td_m->product_id=$d_m->product_id;
						$td_m->dept_pro_id=$d_m->dept_pro_id;
						$td_m->num=$d_m->out_num;
						
					}
					
					if(!$td_m->save())throw new CException("关联单据保存失败!");

					$d_m->detail_id=$td_m->id;//详单
					if($d_m->save()){
						$pro_m->num=$d_m->after_num;
						$pro_m->save();
					}else{
						throw new CException("出库详情保存失败!");
					}
				}
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
		$sn_model = \CompanySn::model()->nowNum('Out');
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn='Out'.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
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
	 * 获取出库类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null,$ForEdit=false){
		$Array = array(
			'1'		=> '调拨出库',
			'2'		=> '领用出库',
			'3'		=> '领取出库',
			'4'		=> '采购退货',
		);
		if($Pk === null) {
			if ($ForEdit) {
				unset($Array[3]);
			}
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
		$model = new Out;
		if($id)
			$model->findByPk((int)$id);
		if($model->isNewRecord){
			//添加
			$model->detail=array();
		}else{
			//编辑
			$model->detail=OutDetail::model()->getByProj($model->id);
		}
		
		return $model;
	}

	/**
	 * 获取出库单 发出单位
	 * @param  [type] $id   本记录id
	 * @return [type]       [description]
	 */
	public static function getFromDept($id){
		$res=self::model()
            ->field("t.type,t.from_id")
            ->findByPk($id);
        switch ($res->type) {
        	case '1':
        		$name=\EmployDept::model()
        				 ->join('invo_transfer it ON it.from_id=t.id')
        				 ->find("it.id={$res->from_id}")->dept_name;
        		break;
        	case '2':
        		$name=\EmployUser::model()
        				 ->join('invo_recipients it ON it.create_user=t.id')
        				 ->find("it.id={$res->from_id}")->netname;
        		break;
        	case '3':
        		$name=\CustomerInfo::model()
        				 ->join('customer_prod_receive it ON it.cu_id=t.id')
        				 ->find("it.id={$res->from_id}")->name;
        		break;
        	case '4':
        		$name=\SupplierInfo::model()
        				 ->join('invo_transfer it ON it.from_id=t.id')
        				 ->find("it.id={$res->from_id}")->name;
        		break;
        	default:
        		$name='';
        		break;
        }

		return $name;
	} 


}