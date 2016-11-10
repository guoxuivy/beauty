<?php
namespace customer;
use Ivy\core\CException;
class InitController extends \SController
{

	/**
	 * 待建期初客户列表
	 */
	public function indexAction() {
		$map=array();
		$map['t.company_id']	= 	array('eq',$this->company['id']);
		$map['t.cart_init']	= 	array('eq',0);
		if($this->is_store){
			$map['t.store_id']		=	array('eq',\Ivy::app()->user->dept_id);
		}
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \CustomerInfo::model()
		->field(array("t.*","l.level_name as lname","ceu.netname as cnetname","oeu.netname as onetname","ed.dept_name"))
		->join('config_membership l on t.membership_id=l.id')//会员级别
		->join('employ_user ceu on t.counselor_id=ceu.id')//顾问
		->join('employ_user oeu on t.operator_id=oeu.id')//美疗师
		->join('employ_dept ed on t.store_id=ed.id')//归属部门
		->where($map)
		->group('t.id')
		->getPagener();
		
		$this->view->assign(
			array(
				'pager'=>$pager
			)
		)->display();
	}

	/**
	 * 待建期初客户列表
	 */
	public function billAction() {
		$map=array();
		$map['t.company_id']	= 	array('eq',$this->company['id']);
		$map['t.cart_init']	= 	array('eq',1);
		if($this->is_store){
			$map['t.store_id']		=	array('eq',\Ivy::app()->user->dept_id);
		}
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \CustomerInfo::model()
			->field(array("t.*","l.level_name as lname","ceu.netname as cnetname","oeu.netname as onetname","ed.dept_name"))
			->join('config_membership l on t.membership_id=l.id')//会员级别
			->join('employ_user ceu on t.counselor_id=ceu.id')//顾问
			->join('employ_user oeu on t.operator_id=oeu.id')//美疗师
			->join('employ_dept ed on t.store_id=ed.id')//归属部门
			->where($map)
			->group('t.id')
			->getPagener();

		$this->view->assign(
			array(
				'pager'=>$pager,
				"light_nav"=>$this->url('customer/init/index'),
			)
		)->display();
	}

}