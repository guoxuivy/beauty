<?php
namespace sms;
use Ivy\core\CException;
/**
 * 续费升级
 */
class UpgradeController extends \SController{

	public function indexAction(){
		$this->view->assign(array('model'=>$this->company))->display();
	}

	/**
	 * 收信箱列表 未读+已读
	 * @param [type] $id [description]
	 */
	public function listAction(){
		$pager = \SmsMessage::model()
			->field("t.*,eu.netname as send_name, smo.open_type,smo.time as open_time")
			->join("employ_user eu ON eu.id=t.uid")
			->join("sms_message_open smo ON smo.mid=t.id AND smo.uid=t.to_uid")
			->where("t.to_uid = ".\Ivy::app()->user->id)
			->where("(smo.`open_type` IS NULL OR smo.`open_type`=1)")
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
			->field("t.*,eu.netname as send_name, smo.open_type,smo.time as open_time")
			->join("employ_user eu ON eu.id=t.uid")
			->join("sms_message_open smo ON smo.mid=t.id AND smo.uid=t.to_uid")
			->where("to_uid = ".\Ivy::app()->user->id)
			->findByPk($id);
		if(!$model){
			throw new CException("无权查看！");
		}

		$model->read();
		$this->view->assign(array('model'=>$model))->display();
	}
	

	

}
