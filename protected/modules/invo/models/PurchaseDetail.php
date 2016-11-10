<?php
namespace invo;
/**
 * 详细
 */
use Ivy\core\CException;
use Ivy\core\ActiveRecord;
class PurchaseDetail extends ActiveRecord
{

    public function tableName() {
        return 'invo_purchase_detail';
    }

    /**
     * 数据保存
     * @param  array $Data  [description]
     * @param  object $model 
     * @return 
     */
    public function saveData($data){
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