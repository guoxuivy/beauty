<?php
/**
 * @Author: sam
 * @Date:   2015-04-21 11:17:19
 *
 * 卡券管理
 */
namespace admin;
use Ivy\core\CException;
class VoucherController extends \SController
{

	public function indexAction() {
		$map['t.comp_id']	= 	array('eq',$this->company['id']);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}

		$pager= \ConfigVoucher::model()
		->where($map)
		->order('t.id')
		->group('t.id')
		->getPagener();
		//\Ivy::log(\ProjectInfo::model()->lastSql);
		$this->view->assign(array('pager'=>$pager))->display();
	}

	//编辑、添加项目
	public function editAction($id=null){
		if($this->isPost){
			\Ivy::app()->user->checkToken();
			$data=$_POST;
			$data['stime']=strtotime($data['stime']);
			$data['etime']=strtotime($data['etime']);
			$res = \ConfigVoucher::model()->saveData($data);
			if($res)
				$this->redirect('index');
			throw new CException(\ConfigVoucher::model()->popErr());
		}else{
			$model = \ConfigVoucher::model()->getEditModel($id);
			$this->view->assign(array(
				'model'=>$model,
				'light_nav'=>$this->url('admin/voucher/index'),//强制点亮导航
			))->display();
		}

	}

	/**
	 * 查看卡券
	 * @param null $id
	 */
	public function viewAction($id=null){
		$model = \ConfigVoucher::model()->getEditModel($id);
		$this->view->assign(array(
			'model'=>$model,
			'light_nav'=>$this->url('admin/voucher/index'),//强制点亮导航
		))->display();
	}

	public function changeAction($id=null) {
		$model = \ConfigVoucher::model ()->findByPk ( $id );
		$model->updateUseStatus();
		
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






}