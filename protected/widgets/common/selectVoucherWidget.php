<?php
namespace common;
use Ivy\core\Widget;
use Ivy\core\CException;
/**
 * 卡券选择弹出框部件封装
 */
class SelectVoucherWidget extends Widget
{
	public function run($param="simple"){
		//默认简单级别显示
        $where = '';
        if(!empty($param['cu_id'])){
            $cu_info = \CustomerInfo::model()->findByPk($param['cu_id']);
            if($cu_info->membership_id!=0){
                $membership = \ConfigMembership::model()->findByPk($cu_info->membership_id);
                if($membership->is_member==1){
                    $where = ' and scope<>2';
                }else{
                    $where = ' and scope<>1';
                }
            }else{
                $where = ' and scope<>1';
            }
        }
        $v_list = \ConfigVoucher::model()->findAll('(num=-1 or num>0) and comp_id ='.\Ivy::app()->user->comp_id.$where);
        return $this->assign(array('v_list'=>$v_list,'level'=>$param))->render();
	}


}