<?php
namespace sms;
use Ivy\core\CException;
/**
 * 站内信
 */
class MessageController extends \SController
{

	/**
	 * 收信箱列表 未读+已读
	 * @param [type] $id [description]
	 */
	public function listAction(){
		$map['t.to_uid'] = array('eq',\Ivy::app()->user->id);
		$map['t.type'] = array('eq','inbox');
		$map['t.status'] = array('in',array(0,1));
		$pager = \SmsMessage::model()
			->where($map)
			->order("t.create_time desc")
			->getPagener();
		$this->view->assign(array('pager'=>$pager))->display();
	}

	/**
	 * 收信箱列表
	 * @param [type] $id [description]
	 */
	public function viewAction($id){
		$model = \SmsMessage::model()
			->field("t.*,eu.netname as send_name")
			->join("employ_user eu ON eu.id=t.uid")
			->where("to_uid = ".\Ivy::app()->user->id)
			->findByPk($id);
		if(!$model){
			throw new CException("无权查看！");
		}
		//标记已读
		$model->read();
		$this->view->assign(array('model'=>$model))->display();
	}


	/**
	 * 发件箱
	 * @param [type] $id [description]
	 */
	public function outListAction(){
		$map['t.uid'] = array('eq',\Ivy::app()->user->id);
		$map['t.type'] = array('eq','outbox');
		$pager = \SmsMessage::model()
			->where($map)
			->order("t.create_time desc")
			->getPagener();
		$this->view->assign(array('pager'=>$pager))->display();
	}
	/**
	 * 发件箱 查看
	 * @param [type] $id [description]
	 */
	public function outViewAction($id){
		$model = \SmsMessage::model()
			->field("t.*,eu.netname as send_name")
			->join("employ_user eu ON eu.id=t.uid")
			->where("uid = ".\Ivy::app()->user->id)
			->findByPk($id);
		if(!$model){
			throw new CException("无权查看！");
		}
		$this->view->assign(array('model'=>$model))->display();
	}
	

	/**
	 * 平台管理员 新建、编辑 消息 
	 * @param [type] $id [description]
	 */
	public function editAction($id=NULL){
		if(\Ivy::app()->user->position_id!=11)
			throw new CException("非法访问！");
		if($id){
			$model = \SmsMessage::model()
			->field("t.*,eu.netname as send_name")
			->join("employ_user eu ON eu.id=t.uid")
			->where("to_uid = ".\Ivy::app()->user->id)
			->findByPk($id);
		}else{
			$model = new \SmsMessage;
		}
		$this->view->assign(array('model'=>$model))->display();
	}

	/**
	 * 平台管理员 新建、编辑 消息 
	 * @param [type] $id [description]
	 */
	public function editSaveAction(){
		if(\Ivy::app()->user->position_id!=11)
			throw new CException("非法访问！");

		if($this->isAjax){
			//发送信件
			\SmsMessage::send($_POST);
			$this->ajaxReturn( 200,"发送成功！");
			
		}
		throw new CException("非法提交！");
		

	}

	/**
	 * 公司模糊搜索
	 * @param [type] $id [description]
	 */
	public function compAction(){
		$map['t.comp_name']			=  array('like',"%{$_POST['name']}%");
		$list = \CompanyInfo::model()
        	->field("t.comp_name as name,t.id")
        	->where($map)
        	->findAll();
        if($list){
			$this->ajaxReturn( 200, "", $list);
        }
        die;
	}

	/**
	 * 公司人员加载
	 * @param [type] $id [description]
	 */
	public function compUserAction($id){
		$map['comp_id'] = array('eq',(int)$id);
		$map['status'] = array('eq',1);
		$map['position_id'] = array('in',array(1,5,8));
		$list = \EmployUser::model()->field("id,netname,position_id")->where($map)->findAll();
		if($list){
			$this->ajaxReturn( 200, "", $list);
		}
		$this->ajaxReturn( 400, "", $list);
	}


	/**
	 * 百度编辑器上传处理程序
	 * @param [type] $id [description]
	 */
	public function uploadAction($editorid){

		\Ivy::importExt("umeditor/Uploader.class");
		//上传配置
		$config = array(
		    "savePath" => "upload/" ,             //存储文件夹
		    "maxSize" => 1000 ,                   //允许的文件最大尺寸，单位KB
		    "allowFiles" => array( ".gif" , ".png" , ".jpg" , ".jpeg" , ".bmp" )  //允许的文件格式
		);
		//上传文件目录 前面不能加 “/”  无语，奇怪
		$Path = "upload/umeditor";

		//背景保存在临时目录中
		$config[ "savePath" ] = $Path;
		$up = new \Uploader( "upfile" , $config );
		$type = $_REQUEST['type'];
		$callback=$_GET['callback'];

		$info = $up->getFileInfo();
		/**
		 * 返回数据
		 */
		if($callback) {
		    echo '<script>'.$callback.'('.json_encode($info).')</script>';
		} else {
		    echo json_encode($info);
		}
		//强制关闭性能调试输出
		\Ivy::app()->noProfile=true;
		die;
	}



	

}
