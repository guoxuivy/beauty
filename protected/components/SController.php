<?php
use Ivy\core\Controller;
use Ivy\core\CException;
/**
 * 系统控制器基类
 * 登录后相关操作使用
 */
class SController extends Controller
{
	//布局文件
	public $layout='/layouts/main_m';
	
	protected $title='美业云平台'; //页面标题

	protected $company=null; //登录用户公司信息

	protected $is_store=false; //是否为门店职位
	protected $store_name=""; //门店名称
	
	public function init() {
		//判断是否登录
		if(\Ivy::app()->user->getIsGuest()) {
			$hostInfo = $this->getHostInfo();
			$return_url = $hostInfo . $this->getRequestUri();
			\Ivy::app()->user->setReturnUrl($return_url);
			$this->redirect('index/login');
		}else{
			// $company=\Ivy::app()->user->getState('company_info');
			// if(empty($company)){
				$company = \CompanyInfo::model()->findByPk(\Ivy::app()->user->comp_id);
				\Ivy::app()->user->setState('company_info', $company);//强制更新公司信息
			// }
			$this->company = $company;

			if(\Ivy::app()->user->position_id==1)
				$this->initBossRole();

			//是否为门店人员
			$d_t = \EmployDept::model()->findByPk(\Ivy::app()->user->dept_id);
			if($d_t->type=='2'){
				$this->is_store=true; 
				$this->store_name=$d_t->dept_name; 
			}
		}
		$route=$this->getRouter();
		$module=$route['module'];
		if (($module=='admin' && !in_array($route['controller'],array('project','product','room'))) && $this->is_store===true ) {
			throw new CException('非法访问！');
		}
		
	}
	/**
	 * 初始化老总的显示角色
	 * @return [type] [description]
	 */
	private function initBossRole(){
		$show_position = \Ivy::app()->user->getState('show_position');
		if(!$show_position){
			\Ivy::app()->user->setState('show_position',1);
		} 
	}

	/**
	 * 获取模版对象
	 * @return [type] [description]
	 */
	public function getView() {
		return new STemplate($this);
	}
	
	/**
	 * 获取当前访问路径
	 * @return string
	 */
	public function getRequestUri()
	{
		$_requestUri=null;
		
		if(isset($_SERVER['HTTP_X_REWRITE_URL'])) // IIS
			$_requestUri=$_SERVER['HTTP_X_REWRITE_URL'];
		elseif(isset($_SERVER['REQUEST_URI']))
		{
			$_requestUri=$_SERVER['REQUEST_URI'];
			if(!empty($_SERVER['HTTP_HOST']))
			{
				if(strpos($_requestUri,$_SERVER['HTTP_HOST'])!==false)
					$_requestUri=preg_replace('/^\w+:\/\/[^\/]+/','',$_requestUri);
			}
			else
				$_requestUri=preg_replace('/^(http|https):\/\/[^\/]+/i','',$_requestUri);
		}
		elseif(isset($_SERVER['ORIG_PATH_INFO']))  // IIS 5.0 CGI
		{
			$_requestUri=$_SERVER['ORIG_PATH_INFO'];
			if(!empty($_SERVER['QUERY_STRING']))
				$_requestUri.='?'.$_SERVER['QUERY_STRING'];
		}
	
		return $_requestUri;
	}
	
	/**
	 * 查询时间处理
	 * @param  string $type [description]
	 * @return [type]       [description]
	 */
	public function sTime($type="1",$format=true){
		//当点9点时间戳
		$day_s = strtotime(date('Ymd'));
		$map=array();
		switch ($type) {
			case '1': //当天时间段
				$map['begin'] = $day_s;
				$map['end'] = $day_s+3600*24;//以当天24点计算
				break;
			case '2'://当周时间段
				$d = 6;		
				$map['begin'] = $day_s - $d*3600*24;	//7天前开始时间
				$map['end'] = $day_s+3600*24;	//结束时间
				break;
			case '3'://当月时间段
				$d = 30;		
				$map['begin'] = $day_s - $d*3600*24;	//30天前开始时间
				$map['end'] = $day_s+3600*24;	//结束时间
				break;
			default:
				break;
		}
		if(empty($map)) return false; 
		if($format){
			$map['begin']=date('Y-m-d H:i:s',$map['begin']);
			$map['end']=date('Y-m-d H:i:s',$map['end']);
		}
		return $map;
	}

	public function actionBefore() {
	}
	
}