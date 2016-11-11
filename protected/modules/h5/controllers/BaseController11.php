<?php
namespace api;
use Ivy\core\Controller;
use Ivy\core\CException;
/**
 * 移动端REST基类
 *
 * 可共用原子操作放于此类
 */
class BaseController extends Controller
{
	//布局文件
	public $layout = null;

	public function actionBefore() {
		if ($this->validateAuth()){
			
		}else{
			throw new CException('授权验证失败！',400);
		}

	}

	/**
	 * 验证用户信息，基于 HTTP  验证
	 * @return boolean
	 */
	protected function validateAuth() {
        if(!isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['HTTP_HTTP_AUTHORIZATION'])) return false;
		$http_authorization = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : $_SERVER['HTTP_HTTP_AUTHORIZATION'];
		 
        $arr = explode(':', substr(base64_decode($http_authorization),6,-6));
        if(count($arr)!=2) return false;
        list($username, $password) = $arr;


        try {
        	$md5password = md5($password);
			$userModel = \EmployUser::model()
			->field("t.*,ci.status as comp_status")
			->join("company_info as ci ON ci.id=t.comp_id")
			->where("t.user_name='$username' AND t.password='$md5password' AND t.status=1")
			->find();
		} catch (\Exception $e) {
			throw new CException('数据异常！',400);
		}

		if(empty($userModel)) {
			throw new CException('账户名或密码不正确！',400);
		}elseif($userModel['position_id']=="11"){ //平台管理员
			throw new CException('平台管理员！',400);
			//return true;
		}elseif((int)$userModel['comp_status'] < 1){
			throw new CException('公司不可用，请联系美慧管理员。',400);
		}else{
			//return true;
		}

		\Ivy::app()->user->login($userModel);

		return true;

      
	}

}
