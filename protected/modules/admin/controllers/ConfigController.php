<?php
namespace admin;
use Ivy\core\CException;
class ConfigController extends \SController
{

	//单据显示设置
	public function indexAction() {
		$config=\CompanyInfo::getConfig('order_show');
		if ($_POST) {
			if ($model=\CompanyInfo::setConfig('order_show',$_POST['order_show'])){
				$this->ajaxReturn ( 200, "ok", array (
						
				) );
			}else{
				$this->ajaxReturn ( 400, "no", array (
						
				) );
			}
		}
		$this->view->assign(array(
			'config'=>$config,
			))->display();
	}
	//积分设置
	public function scoreAction() {
		$config=\CompanyInfo::getConfig('score_config');
		if ($_POST) {
			if ($model=\CompanyInfo::setConfig('score_config',$_POST['score_config'])){
				$this->ajaxReturn ( 200, "ok", array (
						
				) );
			}else{
				$this->ajaxReturn ( 400, "no", array (
						
				) );
			}
		}
		$this->view->assign(array(
			'config'=>$config,
			))->display();
	}
	//会员账户设置
	public function capitalAction() {
		$map['t.comp_id']	= 	array('eq',$this->company['id']);
		$map['t.status']	=	array('eq',1);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager=\CompanyCapital::model()->where($map)->getPagener ();
		//$config=\CompanyInfo::getConfig('score_config');
		if ($_POST) {
			$id=(int)$_POST['id'];
			if ($id) {
				$model=\CompanyCapital::model()->findByPk($id);
			}else
			{
				$model=new \CompanyCapital;
				unset($_POST['id']);
			}
			$model->attributes=$_POST;
			$model->comp_id=$this->company['id'];
			if($model->save()){
				if(!empty($_POST['yd'])){
					$this->redirect ( '',array('yd'=>$_POST['yd']) );
				}else{
					$this->redirect();
				}
			}

		}
		$this->view->assign(array(
			'pager'=>$pager,
			))->display();
	}
	//会员账户删除
	public function capitaldeleteAction($id)
	{
		$id=(int)$id;
		$model=\CompanyCapital::model()->findByPk($id);
		$model->delete ();
		
		if ($model){
			$this->ajaxReturn ( 200, "ok", array (
					"status" => $model->status
			) );
		}else{
			$this->ajaxReturn ( 400, "修改失败", array (
					"status" => $model->status
			) );
		}
	}
	//会员账户编辑
	public function capitaleditAction($id='')
	{
		$id=(int)$id;
		if ($id) {
			$model=\CompanyCapital::model()->findByPk($id);
		}else
		{
			$model=new \CompanyCapital;
			$model->type=$model->is_project=$model->is_product=$model->is_voucher=$model->status=1;
		}
		$html=$this->view->assign(array(
			'model'=>$model,
			))->render();
		$this->view->tagToken ( $html ); // render 方式需要强制增加token
		echo $html;
	}
	//收款账户设置
	public function payeeAction() {
		$config=\CompanyInfo::getConfig('payee');
		//var_dump($config);
		if ($_POST) {

			unset($_POST['__hash__']);
			foreach ($config as $key => $value) {
				foreach ($value as $key3 => $value3) {
					if (empty($_POST[$key][$key3]) && $key3!='has') {
						$_POST[$key][$key3]=$config[$key][$key3];
					}
				}
			}
			foreach ($_POST as $key => $value) {
				if ($value['has']) {
					foreach ($value['has'] as $key2 => $value2) {
					    $add_pre=(string)$value['pre'].$key2;
						$_POST[$key]['has'][$add_pre]=$value2;
						unset($_POST[$key]['has'][$key2]);
					}
				}
				
			}
			if ($model=\CompanyInfo::setConfig('payee',$_POST)){
				$this->ajaxReturn ( 200, "ok", array (
						
				) );
			}else{
				$this->ajaxReturn ( 400, "no", array (
						
				) );
			}
		}
		$this->view->assign(array(
			'config'=>$config,
			))->display();
	}
	//销售权限设置
	public function saleaccessAction() {
		$config=\CompanyInfo::getConfig('sale_access');
		//var_dump($config);exit;
		if ($_POST) {
			unset($_POST['__hash__']);
			if ($model=\CompanyInfo::setConfig('sale_access',$_POST)){
				$this->ajaxReturn ( 200, "ok", array (
						
				) );
			}else{
				$this->ajaxReturn ( 400, "no", array (
						
				) );
			}
		}
		$this->view->assign(array(
			'config'=>$config,
			))->display();
	}
	//有效会员设置
	public function memberconfigAction() {
		$config=\CompanyInfo::getConfig('member_config');
		if ($_POST) {
			unset($_POST['__hash__']);
			if ($model=\CompanyInfo::setConfig('member_config',$_POST['member_config'])){
				$this->ajaxReturn ( 200, "ok", array (
						
				) );
			}else{
				$this->ajaxReturn ( 400, "no", array (
						
				) );
			}
		}
		$this->view->assign(array(
			'config'=>$config,
			'auto'=>\CompanyInfo::model()->findByPk($this->company->id)->auto_cu_status,
			))->display();
	}
	//有效会员自动更新开关
	public function memberAutoAction($auto) {
		$m = \CompanyInfo::model()->findByPk($this->company->id);
		$m->auto_cu_status=(int)$auto;
		if($m->save())
			$this->ajaxReturn('200',"更新成功！");

		$this->ajaxReturn('400',"更新失败！");

	}
}