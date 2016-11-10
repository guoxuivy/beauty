<?php
/**
 * 
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerProdReceiveDetail extends ActiveRecord
{
	public function tableName() {
		return 'customer_prod_receive_detail';
	}

    /**
     * 添加寄存领取单详细
     * @param $data
     * @throws CException
     */
    public function edit($data,$receive_id){
        foreach($data['receive'] as $receive){
            $model = new CustomerProdReceiveDetail();//$this;
            if(!empty($data['receive_id'])){
                $model = $this->find("order_id={$data['receive_id']} AND storage_detail_id={$receive['storage_detail_id']}");
                $model = $model?$model:new CustomerProdReceiveDetail();
            }
            $model->order_id = $receive_id;
            $model->order_detail_id = $receive['order_detail_id'];
            $model->storage_detail_id = $receive['storage_detail_id'];
            $model->product_id = $receive['product_id'];
            $model->before_num = $receive['remain_num'];
            $model->num = $receive['num'];
            $model->after_num = $receive['remain_num']-$receive['num'];
            if(!$model->save()) throw new CException("添加寄存领取单详细失败！");
        }
    }



}	