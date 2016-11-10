<?php
namespace plat;
use Ivy\core\CException;
/**
 * 平台管理员管理公司入住申请和付费服务
 */
class CompanyController extends \SController
{
	public function actionBefore() {
		parent::actionBefore();
		if(\Ivy::app()->user->position_id!=11)
			throw new CException("非法访问！");
	}

	// 列表
	public function indexAction() {
		$map=array();
		
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \CompanyInfo::model()
		->field(array("t.*"))
		->where($map)
		->order("t.id desc")
		->getPagener();
		
		$this->view->assign(array('pager'=>$pager))->display();
	}

	public function viewAction($id){
		$company_info = \CompanyInfo::model()->findByPk($id);
		$this->view->assign(array('model'=>$company_info))->display();
	}

	/**
	 * 注册审核动作
	 * @return [type] [description]
	 */
	public function checkAction(){
		$id = $_POST['id'];
		$company_info = \CompanyInfo::model()->findByPk($id);
		$company_info->status=9; //进入试用期
		$company_info->check_time=time();
		
		$t = substr(date("Ymd",strtotime("+2 month")),0,6)."01";
		$company_info->server_time=strtotime($t);

		$ifUserExists = \EmployUser::model()->find("user_name='".$company_info->phone."'");
		if(!empty($ifUserExists)) {
			$this->ajaxReturn('400', '对不起，手机号重复!');
		}
		$smsConfig=\Ivy::app()->C('smsConfig');
		if($company_info->save()){
			switch ($_POST['status']) {
				case '1':
					$user = new \EmployUser;
					$user->comp_id=$company_info->id;
					$user->netname=$company_info->boss_name;
					$user->user_name=$company_info->phone;
					$user->password=$company_info->password;
					$user->position_id=1;
					$user->dept_id=1;
					$user->status=1;
					$user->save();
					//发短信微信通知客户重新注册
					\SmsApi::send($company_info->phone,array($company_info->phone),$smsConfig['TempId3']);
					break;
				default:
					\WemallApi::send('DelComAdmin',array('com_id'=>$company_info->id));//微信解绑
					//物理删除
					$company_info->delete();
					//发短信微信通知客户重新注册
					\SmsApi::send($company_info->phone,array($company_info->phone),$smsConfig['TempId2']);
					break;
			}
			$this->ajaxReturn(200,"保存成功！");
		}
		$this->ajaxReturn(400,"保存失败！");
	}

	//续费
	public function addServerAction(){
		$id = $_POST['id'];
		$time = $_POST['time'];
		$model = \CompanyInfo::model()->findByPk($id);
		$model->server_time=strtotime($time);
		$model->status=1;
		if($model->save()){
			$this->ajaxReturn(200,"续费成功！");
		}
		$this->ajaxReturn(400,"续费失败！");

	}

	//关闭一个公司
	public function closeServerAction(){
		$id = $_POST['id'];
		$model = \CompanyInfo::model()->findByPk($id);
		$model->status=-1;
		if($model->save()){
			$this->ajaxReturn(200,"关闭成功！");
		}
		$this->ajaxReturn(400,"关闭失败！");
	}
	//开启一个公司
	public function openServerAction(){
		$id = $_POST['id'];
		$model = \CompanyInfo::model()->findByPk($id);
		$model->status=1;
		if($model->save()){
			$this->ajaxReturn(200,"开启成功！");
		}
		$this->ajaxReturn(400,"开启失败！");
	}

	//催费
	public function msmServerAction(){
		$id = $_POST['id'];
		$model = \CompanyInfo::model()->findByPk($id);

		$smsConfig=\Ivy::app()->C('smsConfig');
		\SmsApi::send($model->phone,array("客户"),$smsConfig['TempId5']);
		if($model->save()){
			$log = new \CompanyInfoCfLog;
			$log->comp_id=$id;
			$log->uid=\Ivy::app()->user->id;
			$log->save();
			$this->ajaxReturn(200,"催费成功！");
		}
	}
	//增加门店数
	public function addStoreNumAction(){
		$id = $_POST['id'];
		$num = (int)$_POST['num'];
		$model = \CompanyInfo::model()->findByPk($id);
		$model->max_store_num=$model->max_store_num+$num;

		if($model->save()){
			$this->ajaxReturn(200,"增加成功！");
		}
	}

	//催费记录
	public function msmListAction(){
		$id = $_POST['id'];
		$list = \CompanyInfoCfLog::model()->field("t.*,eu.netname")->join("employ_user eu ON eu.id = t.uid")->where("t.comp_id = ".$id)->findAll();
		if($list){
			$this->ajaxReturn(200,"",$list);
		}
		$this->ajaxReturn(400,"无记录！");
	}
	
}