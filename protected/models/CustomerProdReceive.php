<?php
/**
 * 
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class CustomerProdReceive extends ActiveRecord
{
	public function tableName() {
		return 'customer_prod_receive';
	}


    /**
     * 添加编辑寄存领取单
     * @param $data
     * @return null
     * @throws CException
     */
    public function edit($data,$id=null){
        if(is_null($id)){
            $model = new \CustomerProdReceive();//$this;
            $model->sn=$this->buildSN();
            $model->dept_id = \Ivy::app()->user->dept_id;
            $model->cu_id = $data['cu_id'];
            $model->create_user = \Ivy::app()->user->id;
        }else{
            $model = \CustomerProdReceive::model()->findByPk($id);
            $data['receive_id'] = $id;
        }
        $model->create_time = date('Y-m-d H:i:s');
        $model->remark = $data['remark'];
        if(!$model->save()) throw new CException("寄存领取单创建失败！");
        \CustomerProdReceiveDetail::model()->edit($data,$model->id);
        return $model->id;
    }

    /**
     * 新建一个sn号
     * @return string [type] [description]
     */
    public function buildSN(){
        $sn_model = \CompanySn::model()->nowNum();
        $new_count=sprintf("%05d", $sn_model->sn_count+1);//前导补0
        //获取单号计数器
        $sn='jc'.date("Ymd").$new_count;
        $sn_model->sn_count = $new_count;
        $sn_model->save();
        return $sn;
    }

    /**
     * 获取领取单详细
     * @param $id
     * @return mixed
     */
    public function getDetails($id){
        $details = \CustomerProdReceiveDetail::model()
            ->field('t.*,pi.name as name,pi.unit as unit')
            ->join("product_info as pi on pi.id=t.product_id")
            ->where("t.order_id={$id}" )
            ->findAll();
        return $details;
    }

    /**
     * 确认领取时，更新领取状态和寄存数量
     * @param $id
     * @return mixed
     * @throws CException
     */
    public function updateStorage($id){
        try{
            //开启事务处理
            $this->db->beginT();
            $receiveDetails = $this->getDetails($id);
            $model = \CustomerProdReceive::model()->findByPk($id);
            $model->re_status = 1;
            $model->receive_time=time();
            //有进销存功能的领取时生成出库单.
            $company_info=\Ivy::app()->user->getState('company_info');
            if ($company_info['has_invo']=='Y') {//有进销存功能
                $m = new \invo\Out;
                $m->sn= $m->buildSN();
                $m->comp_id = \Ivy::app()->user->comp_id;
                $m->dept_id = \Ivy::app()->user->dept_id;
                $m->create_user = \Ivy::app()->user->id;
                $m->type    = 3;
                $m->out_status = '1';//0、未入库 1、已入库
                $m->note=$model->remark;
                $m->from_id = $model->id;//单据id
                $m->to_id=$model->cu_id;
                $m->out_time = time();
                if(!$m->save()) throw new CException("出库失败！");
            }
            foreach($receiveDetails as $receiveDetail){
                $storegeDetail = \CustomerProdStorageDetail::model()->findByPk($receiveDetail['storage_detail_id']);
                $before_num = $storegeDetail->remain_num;
                $storegeDetail->remain_num = $before_num-$receiveDetail['num'];
                $storegeDetail->last_time = date('Y-m-d H:i:s');
                if(!$storegeDetail->save()) throw new CException("寄存更新失败！");
                $order_sale_detail = \OrderSaleDetail::model()->findByPk($storegeDetail['detail_id']);
                $order_sale_detail->var_price = $order_sale_detail['var_price']-$order_sale_detail['pay_price']/$order_sale_detail['num']*$receiveDetail['num'];
                if(!$order_sale_detail->save()) throw new CException("订单详单更新失败！");
                if ($company_info['has_invo']=='Y') {//有进销存功能
                    $d_m = new \invo\OutDetail;
                    $d_m->order_id=$m->id;
                    $d_m->product_id=$storegeDetail->product_id;
                    $pro_m=\invo\Dept::model()->find("comp_id={$m->comp_id} AND dept_id={$m->dept_id} AND product_id={$d_m->product_id} AND status=1");
                    if(empty($pro_m))
                    {
                        throw new CException("分仓无此商品！");
                    }
                    $d_m->dept_pro_id=$pro_m->id;
                    $d_m->before_num=$pro_m->num;
                    $d_m->out_num=$receiveDetail['num'];
                    $d_m->after_num=$d_m->before_num-$d_m->out_num;
                    if($d_m->after_num<0)throw new CException("分仓商品不足！");
                    $d_m->detail_id=$receiveDetail['id'];//详单
                    if($d_m->save()){
                        $pro_m->num=$d_m->after_num;
                        $pro_m->storage_num=$pro_m->storage_num-$d_m->out_num;
                        $pro_m->storage_num=$pro_m->storage_num<0?0:$pro_m->storage_num;
                        if(!$pro_m->save()) throw new CException("分仓商品更新失败!");
                    }else{
                        throw new CException("出库详情保存失败!");
                    }
                }
            }
            if(!$model->save()) throw new CException("领取单领取状态更新失败！");
            $this->db->commitT();
        }catch(CException $e){
            $this->_error[]=$e->getMessage();
            $this->db->rollbackT();
            return false;
        }
        return $model->cu_id;
    }

    public static function  getStatus($Pk=null,$css=false){
        $Array = array(
            '1'		=> '正常',
            '0'		=> '删除',
        );
        if($Pk === null) {
            return $Array;
        }else {
            if(array_key_exists($Pk, $Array)) {
                if($css&&$Pk=='-1')
                    return '<span class="khgl_04">'.$Array[$Pk].'</span>';
                if($css&&$Pk=='1')
                    return '<span class="khgl_05">'.$Array[$Pk].'</span>';
                return $Array[$Pk];
            }else {
                return false;
            }
        }
    }

    public static function  getPayStatus($Pk=null,$css=false){
        $Array = array(
            '1'		=> '已领取',
            '0'		=> '未领取',
        );
        if($Pk === null) {
            return $Array;
        }else {
            if(array_key_exists($Pk, $Array)) {
                if($css&&$Pk)
                    return '<span class="khgl_05">'.$Array[$Pk].'</span>';
                return $Array[$Pk];
            }else {
                return false;
            }
        }
    }



}	