<?php
namespace invo;
/**
 * 调拨单/采购单/退货单
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class Transfer extends ActiveRecord
{

	public function tableName() {
		return 'invo_transfer';
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
			$this->sn=$this->sn?$this->sn:$this->buildSN($this->type);
			$this->save();
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $this->id;
	}

	/**
     * 拒绝调拨单 
     * 原调拨单 state 改为 9
     * 复制调拨单数据 返回到原仓库
     * @return [type] [description]
     */
	public function refuse($id)
	{
		try{
			//开启事务处理  
			$this->db->beginT();
			$old=$this->findByPk($id);
			if($old->state==9) return false;
			$old->state=9;
			$old->save();
			//生成返回的调拨单
			$back = new Transfer;
			$back->sn=$this->buildSN(2);
			$back->comp_id=$old->comp_id;
			$back->create_user=\Ivy::app()->user->id;
			$back->create_time=time();
			$back->from_id=$old->to_id;
			$back->to_id=$old->from_id;
			$back->state=1;
			$back->type=2;
			$back->fid=$old->id;
			$back->save();
			//具体数据拷贝
			$list = TransferDetail::model()->findAll('order_id='.$old->id);
			
			foreach ($list as $v) {
				$t_d = new TransferDetail;
				$t_d->order_id=$back->id;
				$t_d->product_id=$v['product_id'];
				$t_d->dept_pro_id=0;
				$t_d->num=$v['num'];
				$t_d->in_num=$v['in_num'];
				$t_d->save();
			}

			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $back->id;
	}

	/**
	 * 新建一个sn号
	 * @return [type] [description]
	 */
	public function buildSN($type=1){
		switch ($type) {//类型(1分仓调拨 2分仓退货 3采购退货 4采购入库 5购买退货)
			case 1:
				$SNS='DB';
				break;
			case 2:
				$SNS='FCTH';
				break;
			case 3:
				$SNS='CGTH';
				break;
			case 4:
				$SNS='CG';
				break;
			case 5:
				$SNS='GMTH';
				break;
			default:
				$SNS='';
				break;
		}
		$sn_model = \CompanySn::model()->nowNum($SNS);
		$new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
		//获取单号计数器
		$sn=$SNS.date("Ymd").$new_count;
		$sn_model->sn_count = $new_count;
		$sn_model->save();
		return $sn;
	}
	/**
	 * 获取入库类型
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getType($Pk=null){
		$Array = array(
			'1'		=> '采购入库',
			'2'		=> '调拨入库',
			'3'		=> '购买退回',
			'4'		=> '分仓退货',

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


}	