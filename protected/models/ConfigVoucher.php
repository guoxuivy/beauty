<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 卡券设置
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class ConfigVoucher extends ActiveRecord
{
	public function tableName() {
		return 'config_voucher';
	}

	/**
	 * 项目数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		$this->attributes=$data;
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
		try{
			//开启事务处理  
			$this->db->beginT();
			$map['name']=array('eq',$data['name']);
			$map['comp_id']=array('eq',$company_info['id']);
			if($data['id'])
				$map['id']=array('neq',$data['id']);
			$d_m = $this->where($map)->find();//重名检测
			if($d_m){
				throw new CException("项目重名！");
			}
			$this->save();
			//重新添加配方组成
			ConfigVoucherCompose::model()->deleteAll("voucher_id=".$this->id);
			if (isset($data['prod_list'])) {//产品 待写入
				foreach ($data['prod_list'] as $value) {
					$comp_model=new ConfigVoucherCompose;
					$comp_model->voucher_id=$this->id;
					$comp_model->pro_id=(int)$value['id'];
					$comp_model->pro_type=2;
					$comp_model->num=(int)$value['num'];
					$comp_model->save();
				}
			}
			if (isset($data['proj_list'])) {//产品 待写入
				foreach ($data['proj_list'] as $value) {
					$comp_model=new ConfigVoucherCompose;
					$comp_model->voucher_id=$this->id;
					$comp_model->pro_id=(int)$value['id'];
					$comp_model->pro_type=1;
					$comp_model->num=(int)$value['num'];
					$comp_model->save();
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
	 *  添加、编辑页面需要的模型
	 * @param  integer $status 
	 * @return 
	 */
	public function getEditModel($id=null){
		$model = new \ConfigVoucher;
		if($id)
			$model->findByPk((int)$id);
		if($model->isNewRecord){
			//添加
		}else{
			//编辑
			$pro_list=ConfigVoucherCompose::model()->getPros($model->id);
			$model->proj_list=$pro_list['proj_list']; //项目
			$model->prod_list=$pro_list['prod_list']; //产品
		}
		
		return $model;
	}

	/**
	 * 获取
	 * @param $vid
	 * @param string $status
	 * @return int
	 */
	public function getVoucherNum($vid,$status='1,2'){
		$list = \CustomerVoucher::model()->where("t.voucher_id={$vid} and status in ({$status})")->findAll();
		return count($list);
	}

	/**
	 * 获取卡券的售卡累计业绩
	 * @param $vid
	 * @return int|string
	 */
	public function getVoucherTotal($vid){
		$list = \OrderSaleDetail::model()->where("pro_id={$vid} and pro_type=3 and buy_type=1")->findAll();
		$price = 0;
		foreach($list as $_list){
			$price += $_list['price']*$_list['rebate']/100;
		}
		return $price==0?0:sprintf('%.2f',$price);
	}


}