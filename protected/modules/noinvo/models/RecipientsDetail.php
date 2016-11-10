<?php
namespace noinvo;
/**
 * 详细
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class RecipientsDetail extends ActiveRecord
{

	public function tableName() {
		return 'no_invo_recipients_detail';
	}
	public function getByProj($id=null){
		$map['order_id']=array('eq',$id);
		$res = $this
			->field(array('t.*','tc.name AS top_name,c.name AS c_name,p.name AS p_name,p.code p_code,p.specs p_specs,p.unit p_unit'))
			->join('product_info p on p.id=t.product_id')
			->join('pro_cate c on p.cate=c.id')
			->join('pro_cate tc on c.fid=tc.id')
			->where($map)
			->findAll();
		
		return $res;
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
			$this->db->commitT();
		}catch(CException $e){
			$this->_error[]=$e->getMessage();
			$this->db->rollbackT();
			return false;
		}
		return $this->id;
	}


}	