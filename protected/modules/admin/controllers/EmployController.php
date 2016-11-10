<?php

/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-09-08 14:50:04
 */
// 组织结构管理 员工管理
namespace admin;

use Ivy\core\CException;

class EmployController extends \SController {
	
	/**
	 * 员工列表
	 */
	public function indexAction() {
		$map['t.status']	= 	array('egt',0);
		$map['t.position_id']= 	array('neq',10);
		$map['t.comp_id']	= 	array('eq',$this->company->id);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager = \EmployUser::model()->field (array(
				"t.*",
				"p.position_name as pname",
				"d.dept_name as dname" ))
			->join( 'employ_position p on t.position_id=p.id' )
			->join ( 'employ_dept d on t.dept_id=d.id' )
			->where($map)->getPagener ();
		$this->view->assign ( array (
				'pager' => $pager,
				'comp_id' => $this->company->id 
		) )->display ();
	}
	
	/**
	 * 添加员工
	 *
	 * @throws CException
	 */
	public function addAction() {
		if($this->getIsAjax()) {
			$user_name = $_POST['user_name'];
			$user_id = $_POST['user_id'];
			$where = '';
			if(!empty($user_id)){
				$where = " and id<>{$user_id}";
			}
			$employUser = \EmployUser::model()->where("user_name={$user_name} and status<>-1 {$where}")->find();
			if(!empty($employUser)){
				$this->ajaxReturn('400', '手机号被占用！');
			}else{
				$this->ajaxReturn('200', '手机号可以使用！');
			}
			return;
		}
		if(isset($_POST['password'])&&$_POST['password']==''){
			unset($_POST['password']);
		}else{
			$_POST['password']=md5($_POST['password']);
		}
		
		$res = new \EmployUser;
		$_POST['create_time']=time();
		if ($res->saveData( $_POST )){
			if(!empty($_POST['yd'])){
				$this->redirect ( 'index',array('yd'=>$_POST['yd']) );
			}else{
				$this->redirect ( 'index' );
			}
		}

		throw new CException ( $res->popErr() );
		
	}
	
	/**
	 * 编辑员工
	 *
	 * @param string $id        	
	 */
	public function editAction($id = null) {
		$id = ( int ) $id;
		
		$model = \EmployUser::model ()->findByPk ( $id );
		/**
		 * 获取员工对应的部门
		 */
		$dname = \EmployDept::model ()->findByPk ( $model->dept_id );
		
		/**
		 * 获取员工对应的职位
		 */
		$pname = \EmployPositionInfo::model()->findByPk( $model->position_id );
		/**
		 * 获取该公司的所有部门 总部部门不分公司
		 */
		$totalDept = \EmployDept::getDeptList();
		//var_dump($totalDept);die;
		
		/**
		 * 获取员工对应的所有职位
		 */
		$totalPosition = \EmployPositionInfo::getPositionList();
		
		$html = $this->view->assign ( array (
				'model' => $model,
				'dname' => $dname,
				'pname' => $pname,
				'totaldept' => $totalDept,
				'totalposition' => $totalPosition 
		) )->render ();
		$this->view->tagToken ( $html ); // render 方式需要强制增加token
		echo $html;
	}
	
	// 更改员工状态
	public function changeAction($id = null) {
		$model = \EmployUser::model ()->findByPk( $id );
		if($model->position_id=='1'){
			$this->ajaxReturn ( 400, "总经理不可修改");
		}

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
	
	// 删除员工
	public function deleteAction($id = null) {
		$model = \EmployUser::model ()->findByPk( $id );
		if($model->position_id=='1'){
			$this->ajaxReturn ( 400, "总经理不可删除");
		}
		$model->delete();
		
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