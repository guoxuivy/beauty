<?php
namespace admin;
use Ivy\core\CException;
class AdminController extends \SController
{
	//布局文件
	public $layout='/layouts/main';

	public function indexAction() {
		$comp_id = \Ivy::app()->user->comp_id;
		$company_info = \CompanyInfo::model()->findByPk($comp_id);
		$depts = \EmployDept::model()->where("comp_id={$comp_id} and status<>-1 and id>=100")->find();
		$users_jl = \EmployUser::model()->where("comp_id={$comp_id} and position_id=5 and status<>-1")->find();
		$users_gw = \EmployUser::model()->where("comp_id={$comp_id} and position_id=6 and status<>-1")->find();
		$users_mrs = \EmployUser::model()->where("comp_id={$comp_id} and position_id=7 and status<>-1")->find();
		$users_qt = \EmployUser::model()->where("comp_id={$comp_id} and position_id=8 and status<>-1")->find();
		$rooms = \CompanyRoom::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$configMemberships = \ConfigMembership::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$companyCapital = \CompanyCapital::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$projects = \ProjectInfo::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$products = \ProductInfo::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$vouchers = \ConfigVoucher::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$yinDao = false;
		if(count($depts)<=0&&count($users_jl)<=0&&count($users_gw)<=0&&count($users_mrs)<=0&&count($users_qt)<=0&&count($rooms)<=0
			&&count($configMemberships)<=0&&count($companyCapital)<=0&&count($products)<=0&&count($projects)<=0){
			$yinDao = true;
		}
		$this->view->assign(
			array(
				'company_name'=>$company_info->comp_name,
				'depts'=>$depts,
				'users_jl'=>$users_jl,
				'users_gw'=>$users_gw,
				'users_mrs'=>$users_mrs,
				'users_qt'=>$users_qt,
				'rooms'=>$rooms,
				'configMemberships'=>$configMemberships,
				'companyCapital'=>$companyCapital,
				'projects'=>$projects,
				'products'=>$products,
				'vouchers'=>$vouchers,
				'yinDao'=>$yinDao,
			)
		)->display('index');
	}

	//门店检测 json
	public function mdAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$depts = \EmployDept::model()->where("comp_id={$comp_id} and status<>-1 and id>=100")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($depts)));
	}

	//员工检测 json
	public function ygAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$users_jl = \EmployUser::model()->where("comp_id={$comp_id} and position_id=5 and status<>-1")->find();
		$users_gw = \EmployUser::model()->where("comp_id={$comp_id} and position_id=6 and status<>-1")->find();
		$users_mrs = \EmployUser::model()->where("comp_id={$comp_id} and position_id=7 and status<>-1")->find();
		$users_qt = \EmployUser::model()->where("comp_id={$comp_id} and position_id=8 and status<>-1")->find();
		$data = array();$_data = array();
		if(count($users_jl)>0&&count($users_gw)>0&&count($users_mrs)>0&&count($users_qt)>0){
			$msg = 'true';
		}else{
			$msg = 'false';
			if(count($users_jl)<=0){
				$data['jl'] = '门店经理';
			}else{
				$_data['jl'] = '门店经理';
			}
			if(count($users_gw)<=0){
				$data['gw'] = '美容顾问';
			}else{
				$_data['gw'] = '美容顾问';
			}
			if(count($users_mrs)<=0){
				$data['mrs'] = '美容师';
			}else{
				$_data['mrs'] = '美容师';
			}
			if(count($users_qt)<=0){
				$data['qt'] = '前台';
			}else{
				$_data['qt'] = '前台';
			}
		}
		$this->ajaxReturn('200',$msg,array('wjl'=>implode('、',$data),'yjl'=>implode('、',$_data)));
	}

	//房间检测 json
	public function fjAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$rooms = \CompanyRoom::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($rooms)));
	}

	//会员卡检测 json
	public function hykAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$configMemberships = \ConfigMembership::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($configMemberships)));
	}

	//会员账户检测 json
	public function hyzhAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$companyCapital = \CompanyCapital::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($companyCapital)));
	}

	//项目分类检测 json
	public function pcaAction($type=1){
		$comp_id = \Ivy::app()->user->comp_id;
		$proCate = \ProCate::model()->where("comp_id={$comp_id} and type={$type} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($proCate)));
	}

	//项目检测 json
	public function pjAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$projects = \ProjectInfo::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($projects)));
	}

	//产品检测 json
	public function pdAction(){
		$comp_id = \Ivy::app()->user->comp_id;
		$products = \ProductInfo::model()->where("comp_id={$comp_id} and status<>-1")->find();
		$this->ajaxReturn('200','OK',array('num'=>count($products)));
	}

	//配方检测 json
	public function pfAction(){
		$no_num = \ProjectFormula::getNoNum();
		$this->ajaxReturn('200','OK',$no_num);
	}

	//设置检测 json
	public function szAction(){
		$this->ajaxReturn('200','OK');
	}

	


	
}