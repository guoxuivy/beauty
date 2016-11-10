<?php

/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-08-07 10:46:49
 */
// 组织结构管理 会员卡管理
namespace admin;

use Ivy\core\CException;

class MembershipController extends \SController {
	
	/**
	 * 会员卡列表
	 */
	public function indexAction() {
		$map['t.comp_id']	= 	array('eq',$this->company['id']);
		$map['t.status']	= 	array('egt',0);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager = \ConfigMembership::model ()->where($map)->getPagener ();
		
		$this->view->assign ( array (
				'pager' => $pager 
		) )->display ();
	}
	
	/**
	 * 添加会员卡
	 * @throws CException
	 */
	public function addAction() {
		$res = \ConfigMembership::model ()->saveData( $_POST );
		if ($res){
			if(!empty($_POST['yd'])){
				$this->redirect ( 'index',array('yd'=>$_POST['yd']) );
			}else{
				$this->redirect ( 'index' );
			}
		}
		throw new CException ( \ConfigMembership::model()->popErr() );
	}

	/**
	 * 检测会员卡名称
	 */
	public function check_nameAction(){
		$name = $_POST['level_name'];
		$company_id = \Ivy::app()->user->comp_id;
		$id = $_POST['id'];$where = '';
		if(!empty($id)){
			$where = "and id<>{$id}";
		}
		$membership = \ConfigMembership::model()->find("comp_id={$company_id} and level_name='{$name}' and status<>-1 $where");
		if(!empty($membership)) {
			$this->ajaxReturn('400', '会员卡名称已创建');
		}else{
			$this->ajaxReturn('200', 'OK');
		}
		return;
	}
	
	/**
	 * 编辑会员卡
	 * @param string $id
	 */
	public function editAction($id = null) {
		$id = ( int ) $id;
		$model = \ConfigMembership::model ()->findByPk ( $id );
		$html = $this->view->assign ( array (
				'model' => $model 
		) )->render ();
		$this->view->tagToken ( $html ); // render 方式需要强制增加token
		echo $html;
	}
	
	//更改会员卡状态
	public function changeAction($id=null) {
		$model = \ConfigMembership::model ()->findByPk ( $id );
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
	
	//删除会员卡
	public function deleteAction($id=null) {
		$model = \ConfigMembership::model ()->findByPk ( $id );
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