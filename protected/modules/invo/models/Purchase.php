<?php
namespace invo;

use Ivy\core\CException;
use Ivy\core\ActiveRecord;

/**
* 采购单
*/
class Purchase extends ActiveRecord
{
    
    public function tableName() {
        return 'invo_purchase';
    }

    public function saveData($data) {
        try {
            $this->db->beginT();
            $this->attributes=$data;
            $this->save();

            $product = array(-1);
            foreach ($data['detail'] as $val) {
                // 采购单详情
                $detail = new PurchaseDetail();

                $detail->order_id = $this->id;
                $detail->product_id = $val['pro_id'];
                $detail->num = $val['num'];
                $product[]= $val['pro_id'];
                $detail->save();
            }

            // 发送站内信
            $sms = new \SmsSupplier();
            $product_info = \ProductInfo::model()
                    ->field("t.id,t.specs,t.unit,t.name,pc.name as cname,tpc.name as tcname")
                    ->join('pro_cate pc ON pc.id=t.cate')
                    ->join('pro_cate tpc ON tpc.id=pc.fid')
                    ->findAll(array('t.id'=>array("in","(".implode(",", $product).")")));

            $data['product_info'] = $product_info;
            $sms->send($data);
            $this->db->commitT();
        } catch(CException $e) {
            $this->_error[]=$e->getMessage();
            $this->db->rollbackT();
            return false;
        }

        return $this->id;
    }
}