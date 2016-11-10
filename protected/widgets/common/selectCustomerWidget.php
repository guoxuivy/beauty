<?php
namespace common;
use Ivy\core\Widget;
use Ivy\core\CException;
/**
 * 客户模糊选择框
 */
class SelectCustomerWidget extends Widget
{
	public function run($param=array()){
		//默认简单级别显示
        // $v_list = \CustomerInfo::model()
        // ->field("t.name,t.cardid,t.id")
        // ->where(array('t.name'=>array('like',$value)))
        // ->findAll('comp_id ='.\Ivy::app()->user->comp_id);
        return $this->assign($param)->render();
	}


}