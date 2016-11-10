<?php
namespace invo;
/**
 * @Author: K
 * @Date:   2015-10-12 16:07:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-13 18:07:01
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class OutDetail extends ActiveRecord
{
	public function tableName() {
		return 'invo_out_detail';
	}

	public function getByProj($id=null){
		$map['order_id']=array('eq',$id);
		$res = $this
			->field(array('t.*','tc.name AS top_name,c.name AS c_name,p.name AS p_name,p.code p_code,p.specs p_specs,p.unit p_unit,ind.num max_num'))
			->join('product_info p on p.id=t.product_id')
			->join('invo_dept ind on ind.id=t.dept_pro_id')
			->join('pro_cate c on p.cate=c.id')
			->join('pro_cate tc on c.fid=tc.id')
			->where($map)
			->findAll();
		
		return $res;
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
	 * 逻辑删除
	 * @return 
	 */
	public function delete(){
		return $this->updateStatus();
	}



}