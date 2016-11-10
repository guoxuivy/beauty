<?php
namespace customer;
use Ivy\core\CException;
class IndexController extends \SController
{
	public function uploadAction() {
		@set_time_limit(0);
		@ini_set('memory_limit','180M');
		$fn=uniqid("customer_").'.xls';
		$savePath = __ROOT__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;
	    file_put_contents(
	        $savePath  . $fn,
	        file_get_contents('php://input')
	    );
	    $filename= $savePath.$fn;
	    if (file_exists($filename)) {
	    	$result = $this->insertData($filename);
			@unlink($filename);
			if($result) {
				$this->ajaxReturn('200', '上传成功', $result);
			}
	    }
	    $this->ajaxReturn('400', '上传失败');
	}
	/**
	 * 批量插入数据
	 * @param string $file_name 导入数据用的Excel文件位置
	 * @return array
	 */
	protected function insertData($file_name) {
		$data = $this->readExcel($file_name);
		array_shift($data);
		$count = count($data);
		$i = 0;
		//插入数据时出错的行
		$errorLineArr = array();
		$getSex=\CustomerInfo::getSex();
		$getProfessionType=\CustomerInfo::getProfessionType();
		$getMarriage=\CustomerInfo::getMarriage();
		$getCuType=\CustomerInfo::getCuType();
		$getStatus=\CustomerInfo::getStatus();

		
		foreach($data as $key => $val) {
			if(empty($val[2]) || empty($val[3]) || empty($val[8]) || empty($val[12]) ){
				$errorLineArr[] = $val[0].'-0';
				continue;
			}
			$CustomerInfo = new \CustomerInfo();
			$CustomerInfo->company_id = \Ivy::app()->user->comp_id;
			$CustomerInfo->cardid=$val[1];
			$CustomerInfo->name=$val[2];
			//$CustomerInfo->arrears=$val[3]?(float)$val[3]:0;
			$CustomerInfo->phone=$val[3]?$val[3]:'';
			$CustomerInfo->tel=$val[4]?$val[4]:'';
			$CustomerInfo->back_phone=$val[5]?$val[5]:'';
			$CustomerInfo->sex=$val[6]?$val[6]:'男';
			$CustomerInfo->birthday=$val[7]?strtotime($val[7]):0;
			$CustomerInfo->zh_birthday=0;
			$CustomerInfo->profession_type=0;
			$CustomerInfo->marriage=0;
			$CustomerInfo->idcard='';
			$CustomerInfo->weixin='';
			$CustomerInfo->store_id=\Ivy::app()->user->dept_id;
			$CustomerInfo->come_type=7;

			$CustomerInfo->cu_type=($key = array_search($val[8], $getCuType))?$key:'未分类';
			if($val[9])
			{
				$EmployUser=\EmployUser::model()->field('id')->find("comp_id={$CustomerInfo->company_id} AND position_id=6 AND status=1 AND netname='{$val[9]}'");
				if($EmployUser)
				{
					$CustomerInfo->counselor_id=$EmployUser->id;
				}
				unset($EmployUser);
			}
			if($val[10])
			{
				$EmployUser=\EmployUser::model()->field('id')->find("comp_id={$CustomerInfo->company_id} AND position_id=7 AND status=1 AND netname='{$val[10]}'");
				if($EmployUser)
				{
					$CustomerInfo->operator_id=$EmployUser->id;
				}
				unset($EmployUser);
			}
			if ($val[11]) {
				$ConfigMembership=\ConfigMembership::model()->field('id')->find("comp_id={$CustomerInfo->company_id} AND status=1 AND level_name='{$val[11]}'");
				if($ConfigMembership)
				{
					$CustomerInfo->membership_id=$ConfigMembership->id;
				}
				unset($ConfigMembership);
			}
			$CustomerInfo->status=($key = array_search($val[12], $getStatus))?$key:1;
			
			if($CustomerInfo->save()) {
				$i++;
			}
			else {
				$errorLineArr[] = $val[0].'-1';
			}
			unset($CustomerInfo);
		}
		$arr = array(
			'total' => $count,
			'success' => $i,
			'error_num'=>$count-$i,
			'error' => $errorLineArr,
		);
		return $arr;
	}
	/**
	 * 读取Excel，分析数据
	 * @param string $file_name 导入数据用的Excel文件位置
	 * @return array|boolean
	 */
	protected function readExcel($file_name) {
		if(!isset($file_name)) {
			return false;
		}
		\Ivy::importExt("excel/Spreadsheet_Excel_Reader");
		$data = new \Spreadsheet_Excel_Reader();
		// Set output Encoding.
		$data->setOutputEncoding('UTF-8');
		$data->read($file_name);
		
		$excel_data = array();
		for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
			$row_data = array();
			for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
				$row_data[] = trim($data->sheets[0]['cells'][$i][$j]);
			}
			$excel_data[] = $row_data;
		}
		return $excel_data;
	}
	public function qrAction($id)
	{
		\Ivy::importExt("phpqrcode/phpqrcode");
		$id=(int)$id;
		$model = \CustomerInfo::model()->findByPk($id);
		if ($model) {
			\QRcode::png($model->company_id.','.$id);
		}else
		{
			throw new CException("无此客户！");
		}
	}
	/**
	 * [用于客户widget搜索]
	 * @return [type] [description]
	 */
	public function searchCustomerListAction() {
		$map['t.name']			=  array('like',"%{$_POST['name']}%");
		$map['t.company_id']	=  array('eq',\Ivy::app()->user->comp_id);
		if(isset($_POST['dept'])){
			if($_POST['dept']!='all') $map['t.store_id'] = array('in',$_POST['dept']);
		}else{
			$map['t.store_id']	=  array('eq',\Ivy::app()->user->dept_id);
		}
		$list = \CustomerInfo::model()
        	->field("t.name,t.cardid,t.id,ed.dept_name,t.phone")
        	->join("employ_dept ed ON t.store_id=ed.id")
        	->where($map)
        	->findAll();
        //die(\CustomerInfo::model()->lastSql);
        if($list){
        	foreach ($list as $key => $value) {
        		$phone=$value['phone'];
        		$phone[4]='*';
        		$phone[5]='*';
        		$phone[6]='*';
        		$phone[7]='*';
        		$list[$key]['phone']=$phone;
        	}
			$this->ajaxReturn( 200, "", $list);
        }
        die;
        
	}

	// 列表
	public function indexAction() {
		$map=array();
		$map['t.company_id']	= 	array('eq',$this->company['id']);
		if($this->is_store){
			$map['t.store_id']		=	array('eq',\Ivy::app()->user->dept_id);
		}
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \CustomerInfo::model()
		->field(array("t.*","l.level_name as lname","ceu.netname as cnetname","oeu.netname as onetname","ed.dept_name"))
		->join('config_membership l on t.membership_id=l.id')//会员级别
		->join('employ_user ceu on t.counselor_id=ceu.id')//顾问
		->join('employ_user oeu on t.operator_id=oeu.id')//美疗师
		->join('employ_dept ed on t.store_id=ed.id')//归属部门
		->where($map)
		->group('t.id')
		->getPagener();
		
		//\Ivy::log(\CustomerInfo::model()->lastSql);
		$this->view->assign(array('pager'=>$pager))->display();
	}
	//客户个人主页
	public function viewAction($id)
	{
		$id=(int)$id;
		$model = \CustomerInfo::model()
		->field(array("t.*","l.level_name as lname","ceu.netname as cnetname","oeu.netname as onetname","ed.dept_name"))
		->join('config_membership l on t.membership_id=l.id')//会员级别
		->join('employ_user ceu on t.counselor_id=ceu.id')//顾问
		->join('employ_user oeu on t.operator_id=oeu.id')//美疗师
		->join('employ_dept ed on t.store_id=ed.id')//归属部门
		->findByPk($id);
		if(empty($model))
			throw new CException("无此客户！");
			

		$this->view->assign(array(
			'model'=>$model,
			'id'=>$id,
		))->display();
	}
	//修改和添加
	public function editAction($id=null){
		$id=(int)$id;
		$model = \CustomerInfo::model()->findByPk($id);
		if (empty($model)) {
			$model=new \CustomerInfo;
		}
		if ($_POST) {
			\Ivy::app()->user->checkToken();
			$res=$model->saveData($_POST);
			if($res)
				$this->redirect('index');
			else
				throw new CException(\CustomerInfo::model()->popErr());
		}
		
		$this->view->assign(array(
			'model'=>$model,
		))->display();
		
	}

	//头像更新
	public function avatarAction(){
		if ($_POST) {
			$id=$_POST['id'];
			$model = \CustomerInfoExten::model()->find('cu_id='.$id);
			if (empty($model)) {
				$model = new \CustomerInfoExten;
				$model->cu_id=$id;
			}
			$model->avatar = $_POST['avatar'];
			$res=$model->save();
			if($res)
				$this->ajaxReturn(200,'保存成功！');
			else
				$this->ajaxReturn(400,'保存失败！');
		}
		
		
	}
	//账户动态
	public function zhdtAction($id)
	{
		$id=(int)$id;
		$model=\CustomerInfo::model()->findByPk($id);
		$pager= \CustomerCapitalLog::model()
		->field(array("t.id","left(t.`create_time`,16) AS ctime","os.sn","cv.name cvname","t.pos_dir","t.money"))
		->join('customer_capital cc on t.cu_capital_id=cc.id')
		->join('config_voucher cv on cc.capital_id=cv.id')
		->join('order_sale os on t.rel_id=os.id and t.rel_type=1')
		->order('t.`create_time` DESC')
		->where("cc.cu_id={$model->id}")
		->getPagener();
		$this->view->assign(array(
			'id'=>$id,
			'model'=>$model,
			'pager'=>$pager,
		))->display();
	}
	//剩余实操疗次
	public function sysclcAction($id)
	{
		$id=(int)$id;
		$model=\CustomerInfo::model()->findByPk($id);
		$pager= \CustomerReProject::model()
		->field(array("t.id","pi.name piname","IFNULL(os.pay_time,ov.pay_time) pay_time","IFNULL(os.sn,ov.sn) sn","osd.num osd_num","(osd.cash+osd.card_cash) gmje","t.user_num","(t.re_num) sy_num","t.detail_id"))
		->join('project_info pi on t.project_id=pi.id')
		->join('order_sale_detail osd on t.detail_id=osd.id')
		->join('order_sale os on osd.order_id=os.id and osd.buy_type in (1,2)')
		->join('order_voucher ov on osd.order_id=ov.id and osd.buy_type=3')
		->order('(osd.num-t.user_num) DESC')
		->limit('0,20')
		->where("t.cu_id={$model->id}")
		->getPagener();
		$this->view->assign(array(
			'id'=>$id,
			'model'=>$model,
			'pager'=>$pager,
		))->display();
	}
	//积分记录
	public function scoreAction($id)
	{
		$id=(int)$id;
		$model=\CustomerInfo::model()->findByPk($id);
		$pager= \CustomerScoreLog::model()
		->field(array("t.*","os.sn","eu.netname"))
		->join('order_sale os on t.rel_id=os.id')//单据
		->join('employ_user eu on os.made_id=eu.id')
		->where("t.cu_id={$id}")
		->order('t.`create_time` DESC')
		->getPagener();
		$this->view->assign(array(
			'id'=>$id,
			'model'=>$model,
			'pager'=>$pager,
		))->display();
	}
	//卡券管理
	public function kjglAction($id)
	{
		$id=(int)$id;
		$model=\CustomerInfo::model()->findByPk($id);
		$pager= \CustomerVoucher::model()
		->field(array("t.id","cv.name cvname","left(t.create_time,16) ttime","cv.stime","cv.etime","cv.give_money",'t.status'))
		->join('config_voucher cv on t.voucher_id=cv.id')
		->where("t.cu_id={$id} and t.`status`>=0")
		->order('t.`status` ASC')
		->getPagener();
		$this->view->assign(array(
			'id'=>$id,
			'model'=>$model,
			'pager'=>$pager,
		))->display();
	}
	
	//修改状态 
	public function changeAction($id=null){
		$model = \CustomerInfo::model ()->findByPk ( $id );
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
	
	
	//分配客户
	public function allotAction() {
		$fpkh_user=(int)$_POST['fpkh_user'];
		$user_arr=@explode(',',$_POST['user_ids']);
		try {
			$EmployUser=\EmployUser::model()->findByPk($fpkh_user);
			foreach ((array)$user_arr as $key => $value) {
				$model=\CustomerInfo::model()->findByPk($value);
				if ($model) {
					if ($EmployUser->position_id==6) {//顾问
						$model->saveData(array('counselor_id'=>$fpkh_user));
					}else
					{
						$model->saveData(array('operator_id'=>$fpkh_user));
					}
				}
				
			}
		} catch (CException $e) {
			$this->ajaxReturn(400,$e->getMessage());
		}
		
		$this->ajaxReturn(200,"ok");
	}

	
	//删除
	public function deleteAction($id=null) {
		
		$model = \CustomerInfo::model ()->findByPk ( $id );
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

	/**
	 * 验证客户会员卡号是否已被使用
	 */
	public function checkCardAction(){
		$cu_id = $_POST['cu_id'];
		$card_id = $_POST['card_id'];
		$dept_id = \Ivy::app()->user->dept_id;
		$company_id = \Ivy::app()->user->comp_id;
		$cu_list = \CustomerInfo::model()->where("company_id={$company_id}")->findAll();
		$cu_lists = array();
		foreach($cu_list as $cu_info){
			if($cu_info['id']!=$cu_id){
				$cu_lists[$cu_info['id']] = $cu_info['cardid'];
			}
		}
		if(in_array($card_id ,$cu_lists)){
			echo json_encode(array('type'=>'false','msg'=>'此会员卡号已被使用！'));
			return;
		}
		echo json_encode(array('type'=>'true','msg'=>'此会员卡号可以使用！'));
		return;
	}

	//流失客户列表（30天内未实操客户）
	public function outlistAction($store_id=null,$cu_type=null) {
		$map=array();
		$map['t.company_id']	= 	array('eq',$this->company['id']);
		if($store_id){
			$map['t.store_id']		=	array('eq',$store_id);
		}
		if($cu_type){
			$map['t.cu_type']		=	array('eq',$cu_type);
		}
	
		$map['t.last_time'] = array('lt',strtotime("-30 day"));
		
		$pager= \CustomerInfo::model()
		->field(array("t.*","l.level_name as lname","ceu.netname as cnetname","oeu.netname as onetname","ed.dept_name"))
		->join('config_membership l on t.membership_id=l.id')//会员级别
		->join('employ_user ceu on t.counselor_id=ceu.id')//顾问
		->join('employ_user oeu on t.operator_id=oeu.id')//美疗师
		->join('employ_dept ed on t.store_id=ed.id')//归属部门
		->where($map)
		->group('t.id')
		->getPagener();
		$this->view->assign(array('pager'=>$pager))->display();
	}

}