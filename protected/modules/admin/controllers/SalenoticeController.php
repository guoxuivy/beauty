<?php

/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-08-07 11:14:38
 */
// 活动管理
namespace admin;

use Ivy\core\CException;

class SalenoticeController extends \SController {
	
	/**
	 * 活动列表
	 */
	public function indexAction() {
		
		$map['status'] = array('neq','-1');
		$map['comp_id'] = array('eq',\Ivy::app()->user->comp_id);
		$pager= \SaleNoticeInfo::model()->where($map)->getPagener();
		
		$this->view->assign(array(
				'pager' => $pager
		))->display();
	}
	
	/**
	 * 添加活动
	 *
	 * @throws CException
	 */
	public function addAction() {
		$company_info=\ivy::app()->user->getState('company_info');
		$comp_id=$company_info['id'];
		
		$_POST['comp_id'] = $comp_id;
		$res = \SaleNoticeInfo::model ()->saveData ( $_POST );
		if ($res)
			$this->redirect ( 'index' );
		throw new CException ( \SaleNoticeInfo::model ()->popErr () );
	}
	
	/**
	 * 编辑活动
	 *
	 * @param string $id        	
	 */
	public function editAction($id = null) {
		$model = \SaleNoticeInfo::model ()->findByPk ( $id );
		$html = $this->view->assign ( array (
				'model' => $model,
		) )->render ();
		$this->view->tagToken ( $html ); // render 方式需要强制增加token
		echo $html;
	}
	
	// 更改活动状态
	public function changeAction($id = null) {
		$model = \SaleNoticeInfo::model ()->findByPk ( $id );
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
	
	// 删除活动
	public function deleteAction($id = null) {
		$model = \SaleNoticeInfo::model ()->findByPk ( $id );
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
}