<?php
/**
 * @author ivy <guoxuivy@gmail.com>
 * @copyright Copyright &copy; 2013-2017 Ivy Software LLC
 * @license https://github.com/guoxuivy/ivy/
 * @package framework
 * @since 1.0
 */
use \Ivy\core\Controller;
use \Ivy\core\CException;
use \Ivy\core\lib\Image;
/**
 * 系统登录注册控制器
 */
class IndexController extends Controller {
	
	//布局文件
	public $layout=false;

	/**
	 * 获取登录后转跳URL 8为前台
	 * @return [type] [description]
	 */
	private function getLoninUrl(){
		switch (Ivy::app()->user->position_id) {
			case '1'://公司总经理
				$url="boss/index/index";
				if(Ivy::app()->user->login_num==0){
					\Ivy::app()->user->setState('show_position',10);
					$url="admin/admin/index";
				}
				break;
			case '5'://门店经理
				$url="manager/index/index";
				break;
			case '8'://门店前台
				$url="pos/index/index";
				break;
			case '10'://公司管理员
				$url="admin/admin/index";
				break;
			case '11'://平台管理员
				$url="plat/index/index";
				break;
			default:
				$this->logout();
				throw new CException("该版本暂不支持该职位登录！");
				break;
		}
		return $url;
	}

	/**
	 * 默认页面，跳转到登录界面
	 */
	public function indexAction() {
		if(Ivy::app()->user->getIsGuest()) {
			$this->redirect('login');
		} else {
			$return_url = Ivy::app()->user->getReturnUrl();
			if($return_url)
				$this->redirect($return_url);
			$this->redirect($this->getLoninUrl());
		}
	}
	/**
	 * 图形码验证
	 * @return [type] [description]
	 */
	public function checkdecodeAction()
	{
		if(isset($_POST) && !empty($_POST)) {
			$verify = \Ivy::app()->user->getState('verify');
			$verifyCode = isset($_POST['verifyCode']) ? trim($_POST['verifyCode']) : '';
			if($verify != md5($verifyCode)) 
				$this->ajaxReturn('400', '图形验证码填写错误！');
			else
				$this->ajaxReturn('200', '验证码通过！');
		}
	}
	/**
	 * 短信验证码生成
	 */
	public function phoneCode2Action() {
		if (\Ivy::isAjax()) {
			$id=\Ivy::app()->user->getState('fuserid');
			$model=\EmployUser::model()->findByPk($id);
			if(empty($model))
				$this->ajaxReturn('400','非法访问!');
			$phone=$model->user_name;
			$code = substr(microtime(),-2).sprintf("%02d",substr(rand(),-2));
			\Ivy::app()->user->setState('fphoneCode', md5($code));//session存储
			\SmsApi::send($phone,array($phone,$code,'二十'));
			$this->ajaxReturn('200');
		}
		$this->ajaxReturn('400','非法访问!');
		//var_dump(\Ivy::app()->user->getState('phoneCode'));
	}
	/**
	 * 忘记密码1
	 * @return [type] [description]
	 */
	public function forgetpw1Action()
	{
		if(isset($_POST) && !empty($_POST)) {
			$phone=trim($_POST['phone']);
			$verify = \Ivy::app()->user->getState('verify');
			$verifyCode = isset($_POST['verifyCode']) ? trim($_POST['verifyCode']) : '';
			
			if($verify != md5($verifyCode)) {
				$this->ajaxReturn('400', '图形验证码已经使用过一次，请重新生成！');
			}else{
				$verify = \Ivy::app()->user->setState('verify',null);
			}
			$model = \EmployUser::model()->find("user_name='{$phone}'");
			if($model) {
				\Ivy::app()->user->setState('fuserid',$model->id);
				$this->ajaxReturn('200', $this->url('forgetpw2'));
			}else{
				$this->ajaxReturn('400', '账号不存在！');
			}
		}
		$this->view->display('forgetpw1');
	}
	/**
	 * 忘记密码2
	 * @return [type] [description]
	 */
	public function forgetpw2Action()
	{
		$id=\Ivy::app()->user->getState('fuserid');
		if (empty($id)) throw new CException("非法访问！请返回首页！");
		$model=\EmployUser::model()->findByPk($id);
		if (empty($model)) throw new CException("非法访问！账号不存在！");
		if(isset($_POST) && !empty($_POST)) {
			$phoneCode=$_POST['phoneCode'];
			$md5phoneCode = \Ivy::app()->user->getState('fphoneCode');
			if(md5($phoneCode) != $md5phoneCode)
				$this->ajaxReturn('400', '短信验证码错误！');
			else
				$this->ajaxReturn('200', $this->url('forgetpw3'));
			
		}
		$this->view->assign(array('model'=>$model))->display('forgetpw2');
	}
	/**
	 * 忘记密码3
	 * @return [type] [description]
	 */
	public function forgetpw3Action()
	{
		$id=\Ivy::app()->user->getState('fuserid');
		if (empty($id)) throw new CException("非法访问！请返回首页！");
		$md5phoneCode = \Ivy::app()->user->getState('fphoneCode');
		if (empty($md5phoneCode)) $this->redirect('forgetpw1');
		$model=\EmployUser::model()->findByPk($id);
		if (empty($model)) throw new CException("非法访问！账号不存在！");
		if(isset($_POST) && !empty($_POST)) {
			$model->password=md5($_POST['password']);
			if(!$model->save())
				$this->ajaxReturn('400', '修改密码错误，稍后再试！');
			else
				$this->ajaxReturn('200', $this->url('forgetpw4'));
			
		}
		$this->view->assign(array('model'=>$model))->display('forgetpw3');
	}
	/**
	 * 忘记密码4
	 * @return [type] [description]
	 */
	public function forgetpw4Action()
	{
		$md5phoneCode = \Ivy::app()->user->getState('fphoneCode');
		if (empty($md5phoneCode)) $this->redirect('login');
		$id=\Ivy::app()->user->getState('fuserid');
		if (empty($id)) throw new CException("非法访问！请返回首页！");
		$model=\EmployUser::model()->findByPk($id);
		if (empty($model)) throw new CException("非法访问！账号不存在！");
		\Ivy::app()->user->setState('fuserid',null);
		\Ivy::app()->user->setState('fphoneCode',null);
		$this->view->assign(array('model'=>$model))->display('forgetpw4');
	}
	/**
	 * 修改自己登入密码
	 */
	public function cpasswordAction() {
		if(Ivy::app()->user->getIsGuest()) {
			$this->redirect('login');
		} else {
			$verify = \Ivy::app()->user->getState('verify');
			$old_pw = trim($_POST['old_pw']);
			$new_pw = trim($_POST['new_pw']);
			$verifyCode = trim($_POST['v_code']);
			
			if($verify != md5($verifyCode)) {
				$this->ajaxReturn('400', '验证码不正确！');
			}
			if(\Ivy::app()->user->password != md5($old_pw)) {
				$this->ajaxReturn('400', '原始密码不正确！');
			}
			$userModel = \EmployUser::model()->findByPk(\Ivy::app()->user->id);
			$userModel->password = md5($new_pw);
			$userModel->save();
			\Ivy::app()->user->logout();
			\Ivy::app()->user->login($userModel);
			$this->ajaxReturn('200', 'OK');
		}
	}


    /**
	 * 记住登录
	 */
	private function rememberLogin($user){
		$salt = 'AUTOLOGIN';
		$identifier = md5($salt . md5($user->user_name . $salt));
		$token = md5(uniqid(rand(), TRUE));
		$timeout = time() + 60 * 60 * 24 * 7;//7天有效
		setcookie('auth', "$identifier:$token", $timeout);
		$user->identifier=$identifier;
		$user->token=$token;
		$user->timeout=$timeout;
		$user->save();
	}
    /**
	 * 自动登录 判断
	 */
	private function autoLogin(){
		$salt = 'AUTOLOGIN';
		$clean = array();
		$now = time();
		if(!isset($_COOKIE['auth'])) return false;
		list($identifier, $token) = explode(':', $_COOKIE['auth']);
		
		if (ctype_alnum($identifier) && ctype_alnum($token))
		{
			$clean['identifier'] = $identifier;
			$clean['token'] = $token;
		}else{
			return false;
		}

		$user = \EmployUser::model()->field("t.*,ci.id as comp_id")
		->join("company_info as ci ON ci.id=t.comp_id")
		->where("t.identifier = '{$clean['identifier']}'")
		->where("ci.status > 0")
		->find();
		if($user&&$user->token==$clean['token']&&$now < $user->timeout){
			\Ivy::app()->user->login($user);
		}else{
			return false;
		}
		$user->last_login_time= time();
		$user->save();
		return true;
	}

	public function login1Action() {
		$this->view->display('login1');
	}

	/**
	 * 登录
	 */
	public function loginAction() {
		if(isset($_POST) && !empty($_POST)) {
			\Ivy::app()->user->checkToken();
			$verify = Ivy::app()->user->getState('verify');
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
			//自动登录检测
			if($this->autoLogin()){
				if(\Ivy::app()->user->getReturnUrl()){
					$this->redirect(\Ivy::app()->user->getReturnUrl());
				}else{
					$this->redirect($this->getLoninUrl());
				}
			}else{
				$this->view->display('login');die;
			}
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
	 * 用户填写信息成功，给管理员发送邮件
	 * @param $model
	 * @throws CException
	 */
	public function sendEmail($model){
		$to_mails = array(
			0 => 'magq@howa.com.cn',
			1 => 'kangcj@howa.com.cn',
			2 => 'chenj@howa.com.cn',
		);
		$title = 'HOWA平台新用户注册信息通知';
		$content = '
            <div class="table" style="width: 80%">
            <style>
                .table table {
                    *border-collapse: collapse; /* IE7 and lower */
                    border-spacing: 0;
                }
                .bordered {
                    border: solid #ccc 1px;
                    -moz-border-radius: 6px;
                    -webkit-border-radius: 6px;
                    border-radius: 6px;
                    -webkit-box-shadow: 0 1px 1px #ccc;
                    -moz-box-shadow: 0 1px 1px #ccc;
                    box-shadow: 0 1px 1px #ccc;
                    width: 500px;
                    margin-left: 100px;
                }
                .bordered tr:hover {
                    background: #fbf8e9;
                    -o-transition: all 0.1s ease-in-out;
                    -webkit-transition: all 0.1s ease-in-out;
                    -moz-transition: all 0.1s ease-in-out;
                    -ms-transition: all 0.1s ease-in-out;
                    transition: all 0.1s ease-in-out;
                }
                .bordered td{
                    border-left: 1px solid #ccc;
                    border-top: 1px solid #ccc;
                    padding: 10px;
                    text-align: left;
                }
                .bordered td:first-child{
                    border-left: none;
                }
                .bordered tr:last-child td:first-child {
                    -moz-border-radius: 0 0 0 6px;
                    -webkit-border-radius: 0 0 0 6px;
                    border-radius: 0 0 0 6px;
                }
                .bordered tr:last-child td:last-child {
                    -moz-border-radius: 0 0 6px 0;
                    -webkit-border-radius: 0 0 6px 0;
                    border-radius: 0 0 6px 0;
                }
            </style>
            <h2>
                您有一个新的用户提交了注册申请，请以管理员身份登录平台及时审批处理。&nbsp;&nbsp;<a href="'.$this->getHostInfo().$this->url('plat/company/index').'">点击进行登录</a>
            </h2>';
		$content .='
            <h2>以下为用户注册信息：</h2>
            <table class="bordered">
                <tr>
                    <td> 公司名称：</td>
                    <td>'.$model->comp_name.'</td>
                </tr>
                <tr>
                    <td> 公司地址：</td>
                    <td>'.$model->pca_province.' '.$model->pca_city.' '.$model->address.'</td>
                </tr>
                <tr>
                    <td> 联系人：</td>
                    <td>'.$model->boss_name.'</td>
                </tr>
                <tr>
                    <td> 联系电话：</td>
                    <td>'.$model->phone.'</td>
                </tr>
                <tr>
                    <td> 职务：</td>
                    <td>老板</td>
                </tr>
                <tr>
                    <td> 是否有进销存：</td>
                    <td>'.$model->has_invo.'（Y有、N无）</td>
                </tr>
                <tr>
                    <td> 注册时间：</td>
                    <td>'.date('Y-m-d H:i:s').'</td>
                </tr>
            </table>
            </div>
        ';
		\Ivy::importExt("phpmailer/SendMail");
		$send = new \SendMail();
		foreach($to_mails as $to_mail){
			$send->SmtpSendMail($to_mail,$title,$content);
		}
	}

	/**
	 * 注册协议
	 */
	public function tiaokuanAction() {
		$this->view->assign()->display('tiaokuan');
	}

	/**
	 * 短信验证码生成
	 */
	public function phoneCodeAction() {
		$verify = \Ivy::app()->user->getState('verify');
		$verifyCode = isset($_POST['verifyCode']) ? trim($_POST['verifyCode']) : '';
		
		if($verify != md5($verifyCode)) {
			$this->ajaxReturn('400', '图形验证码错误，请刷新验证码重试！');
		}else{
			$verify = \Ivy::app()->user->setState('verify',null);
		}

		$phone=$_POST['phone'];
		$code = substr(microtime(),-2).sprintf("%02d",substr(rand(),-2));
		\Ivy::app()->user->setState('phoneCode', md5($code));//session存储
		\SmsApi::send($phone,array($phone,$code,'二十'));
		$this->ajaxReturn('200');
		//var_dump(\Ivy::app()->user->getState('phoneCode'));
	}
	
	/**
	 * 注册第一步验证
	 */
	public function validate1Action() {
		if(!$this->getIsAjax()) {
			throw new CException('非法访问');
		}
		$phone=$_POST['phone'];
		$password=$_POST['password'];
		$phoneCode=$_POST['phoneCode'];
		$xieYi=$_POST['xie_yi'];
		if(!$xieYi)
			$this->ajaxReturn('400', '请勾选同意《好哇平台注册协议》');

		$ifCompanyExists = \CompanyInfo::model()->find("phone='$phone'");
		$ifUserExists = \EmployUser::model()->find("user_name='$phone'");

		if(!empty($ifCompanyExists)||!empty($ifUserExists)) {
			$this->ajaxReturn('400', '对不起，您输入的手机号已经在使用，请换一个手机号重试');
		}

		$md5phoneCode = \Ivy::app()->user->getState('phoneCode');
		if(md5($phoneCode) != $md5phoneCode && \Ivy::app()->user->position_id!=11)
			$this->ajaxReturn('400', '短信验证码错误！');

		
		$this->ajaxReturn('200', '验证成功');
	}

	//注册选择 美容院 or 供应商
	public function register0Action() {
		$this->view->assign()->display('register0');
	}
	
	/**
	 * 注册第一步
	 */
	public function register1Action() {
		if(isset($_POST) && !empty($_POST)) {
			$model = new CompanyInfo();
			$model->phone = trim($_POST['phone']);
			$model->password = md5(trim($_POST['password']));
			$model->create_time = time();
			$model->step = 2;
			$model->status = 0;	//未开通状态
			if($model->save()) {
				\Ivy::app()->user->setState('comp_id',$model->id);
				$this->redirect('register2');
			}
		}
		$this->view->assign()->display('register1');
	}
	
	/**
	 * 注册第二步
	 */
	public function register2Action() {
		$comp_id = \Ivy::app()->user->getState('comp_id');
		$model =  \CompanyInfo::model()->findByPk($comp_id);
		if(!$model) {
			throw new CException('非法访问');
		}
		$this->view->assign()->display('register2');
	}


	/**
	 * 注册第三步
	 */
	public function register3Action() {
		$comp_id = \Ivy::app()->user->getState('comp_id');
		$model =  \CompanyInfo::model()->findByPk($comp_id);
		if(!$model) {
			throw new CException('非法访问');
		}

		if(isset($_POST) && !empty($_POST)) {
			$model->boss_name=$_POST['boss_name'];
			$model->comp_name=$_POST['comp_name'];
			$model->pca_province=$_POST['pca_province'];
			$model->pca_city=$_POST['pca_city'];
			$model->address=$_POST['address'];
			$model->store_num=$_POST['store_num'];
			$model->has_invo=$_POST['has_invo'];
			$model->avatar=$_POST['avatar'];
			$model->wx_server_sn=$_POST['wx_server_sn'];
			$model->wx_server_pass=$_POST['wx_server_pass'];
			$model->step = 4;
			if($model->save()) {
				$this->sendEmail($model);
				$this->redirect('register4');
			}
		}
		$has_invo = $_GET['has_invo'];
		$this->view->assign(array('has_invo'=>$has_invo))->display('register3');
	}

	//营业执照更新
	public function avatarAction(){
		$id=(int)$_POST['id'];//公司id
		$model = \CompanyInfo::model()->findByPk($id);
		if ($model) {
			$model->avatar = $_POST['avatar'];
			if($model->save())
				$this->ajaxReturn(200,'保存成功！');
		}
		$this->ajaxReturn(400,'注册信息不存在！');
	}


	/**
	 * 注册第四步
	 */
	public function register4Action() {
		$comp_id = \Ivy::app()->user->getState('comp_id');
		$model =  \CompanyInfo::model()->findByPk($comp_id);
		if(!$model) {
			throw new CException('非法访问');
		}
		$this->view->assign()->display('register4');
	}
	public function showqrAction()
	{
		$comp_id = \Ivy::app()->user->getState('comp_id');
		if(empty($comp_id)) 
			throw new CException('非法访问');
		$wx_url=\WemallApi::send('Getqr',array('com_id'=>$comp_id));
		$wx_url=$wx_url['url'];
		Image::showImg($wx_url,'public/images/wx_center.png',166,166,100);
	}


	
}