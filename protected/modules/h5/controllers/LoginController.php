<?php
namespace h5;
use Ivy\core\Controller;
use Ivy\core\CException;
use \Ivy\core\lib\Image;
/**
 * 移动端登录验证类
 */
class LoginController extends Controller
{
	//布局文件
	public $layout = null;

	public function init() {
		\Ivy::app()->C('errorHandler',array(
			'errorAction'=>'h5/error/index',
		));
	}

	/**
	 * 获取登录后转跳URL 8为前台
	 * @return [type] [description]
	 */
	private function getLoninUrl(){
		switch (\Ivy::app()->user->position_id) {
			case '1'://公司总经理
				$url="h5/index/index";
				break;
			default:
				$this->logout();
				throw new CException("该版本暂不支持该职位登录！");
				break;
		}
		return $url;
	}

	/**
	 * 登出
	 */
	public function logoutAction() {
		$this->logout();
		$this->redirect('login');
	}
	//登出处理
	protected function logout(){
		\Ivy::app()->user->logout();
		@setcookie('auth', 'DELETED!', time());
	}

	/**
	 * 登录
	 */
	public function loginAction() {
		if(isset($_POST) && !empty($_POST)) {
			\Ivy::app()->user->checkToken();
			$verify = \Ivy::app()->user->getState('verify');
			$user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
			$password = isset($_POST['password']) ? trim($_POST['password']) : '';
			$verifyCode = isset($_POST['verifyCode']) ? trim($_POST['verifyCode']) : '';
			$md5password = md5($password);
			
			$userModel = \EmployUser::model()->where("user_name='$user_name' AND password='$md5password' AND status=1")->find();

			if($userModel){
				\Ivy::app()->user->login($userModel);
				if(isset($_POST['remember_me'])&&$_POST['remember_me']=='1'){
					$this->rememberLogin($userModel);   
				}
				$userModel->last_login_time = time();
				$userModel->login_num = $userModel->login_num + 1;
				$userModel->save();
				$return_url = \Ivy::app()->user->getReturnUrl();
				if($return_url) {
					$this->redirect($return_url);
				}else{
					$this->redirect($this->getLoninUrl());
				}
			}
			
		}else{
			$this->view->display('login');
		}
	}

	/**
	 * 登录验证
	 * @throws CException
	 */
	public function validateAction() {
		if(!$this->getIsAjax()) {
			throw new CException('非法访问');
		}
		$verify = \Ivy::app()->user->getState('verify');
		$user_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : '';
		$password = isset($_POST['password']) ? trim($_POST['password']) : '';
		$verifyCode = isset($_POST['verifyCode']) ? trim($_POST['verifyCode']) : '';
		
		if($verify != md5($verifyCode)) {
			$this->ajaxReturn('400', '验证码不正确');
		}
		$md5password = md5($password);

		try {
			$userModel = \EmployUser::model()
			->field("t.*,ci.status as comp_status")
			->join("company_info as ci ON ci.id=t.comp_id")
			->where("t.user_name='$user_name' AND t.password='$md5password' AND t.status=1")
			->find();
		} catch (\Exception $e) {
			$this->ajaxReturn('400', '连接失败！');
		}
		if(empty($userModel)) {
			$this->ajaxReturn('400', '账户名或密码不正确');
		}elseif($userModel['position_id']=="11"){ //平台管理员
			$this->ajaxReturn('200', '验证成功');
		}elseif((int)$userModel['comp_status'] < 1){
			$this->ajaxReturn('400', '公司不可用，请联系好哇管理员。');
		}else{
			$this->ajaxReturn('200', '验证成功');
		}
		
	}
	
	public function verifyAction(){
		$w = 50;
		$h = 24;
		Image::buildImageVerify(4, 1, 'png', $w, $h);
	}



}
