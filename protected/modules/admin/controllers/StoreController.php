<?php
/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2016-02-19 16:08:04
 */
//组织结构管理 门店管理
namespace admin;
use Ivy\core\CException;
class StoreController extends \SController
{

	//门店管理
	public function indexAction() {

		$map = array('comp_id' => array('eq',$this->company->id),'type' => array('eq',2),'status' => array('eq',1));
		$list= \EmployDept::model()->where($map)->findAll();
		$map['status'] = array('eq',0);
		$stop_list= \EmployDept::model()->where($map)->findAll();
		$this->view->assign(array(
			'list' => $list,
			'stop_list' => $stop_list,
			'company' => $this->company
		))->display();
	}
	
	//添加门店
	public function addAction() {
		$_POST['type']=2;
		$_POST['comp_id']=$this->company->id;
		$_POST['content']=str_replace("\n","<br />",$_POST['content']);
		$res = new \EmployDept;
		if($res->saveData($_POST)){
			if(!empty($_POST['yd'])){
				$this->redirect('index',array('yd'=>$_POST['yd']));
			}else{
				$this->redirect('index');
			}
		}
		throw new CException($res->popErr());

	}
	

	//删除门店
	public function deleteAction($id=null) {
		$model = \EmployDept::model ()->findByPk ( $id );
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

	//更改门店状态
	public function changeAction($id=null) {
		$model = \EmployDept::model ()->findByPk ( $id );
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

	//编辑门店
	public function editAction($id=null) {
		$id=(int)$id;
		if($id)
			$model = \EmployDept::model()->findByPk($id);
		else
			$model = new \EmployDept;
		$model->content=str_replace("<br />","\n",$model->content);
		$html=$this->view->assign(array(
				'model'=>$model,
		))->render();
		$this->view->tagToken($html);//render 方式需要强制增加token
		echo $html;
	}
	
}