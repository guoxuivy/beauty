<?php
/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-08-07 10:57:17
 */
//组织结构管理 房间管理
namespace admin;
use Ivy\core\CException;
class RoomController extends \SController
{
	
	/**
	 * 房间管理
	 */
	public function indexAction() {
		$map['comp_id'] = array('eq',$this->company->id);
		$map['status'] = array('egt',0);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		if($this->is_store){
			$map['store_id']	= 	array('eq',\Ivy::app()->user->dept_id);
		}
		$pager= \CompanyRoom::model()->where($map)->getPagener();

		//门店列表
		$maps = array('comp_id' => array('eq',$this->company->id),'type' => array('eq',2));
		$store_list= \EmployDept::model()->where($maps)->findAll();

		$this->view->assign(array(
			'pager' => $pager,
			'store_list' => $store_list,
			'is_store' => $this->is_store,
		))->display();
	}
	
	//添加房间
	public function addAction() {
		$_POST['comp_id']=$this->company->id;
		$res = new \CompanyRoom;
		if($res->saveData($_POST)){
			if(!empty($_POST['yd'])){
				$this->redirect ( 'index',array('yd'=>$_POST['yd']) );
			}else{
				$this->redirect ( 'index' );
			}
		}
		throw new CException($res->popErr());
	}
	

	//删除房间
	public function deleteAction($id=null) {
		$model = \CompanyRoom::model ()->findByPk ( $id );
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

	//更改房间状态
	public function changeAction($id=null) {
		$model = \CompanyRoom::model ()->findByPk ( $id );
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

	//编辑房间
	public function editAction($id=null) {
		$id=(int)$id;
		$model = \CompanyRoom::model()->findByPk($id);
		$bed_str = \CompanyRoomBed::model()->_getBedStr($id);
		//门店列表
		$maps = array('comp_id' => array('eq',$this->company->id),'type' => array('eq',2));
		$store_list= \EmployDept::model()->where($maps)->findAll();

		$html=$this->view->assign(array(
				'model'=>$model,
				'bed_str'=>$bed_str,
				'store_list' => $store_list,
		))->render();
		$this->view->tagToken($html);//render 方式需要强制增加token
		echo $html;
	}
}