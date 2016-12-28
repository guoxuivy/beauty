<?php
namespace pos;
use Ivy\core\CException;
/**
 * 订单-退款单 （退项目、退产品、退卡券、退账户金额）
 */
class RefundController extends BaseController
{

	/**
	 * 退款单列表
	 * @param [type] $id [description]
	 */
	public function indexAction(){
		$timeType=isset($_GET['stime'])?$_GET['stime']:"1";
		$stime = $this->sTime($timeType);
		if($stime){
			$map['t.create_time']	= 	array(array('gt',$stime['begin']),array('lt',$stime['end']));
		}
		$map['t.comp_id']=array('eq',\Ivy::app()->user->comp_id);
		if($this->is_store){
			$map['t.md_id']	= 	array('eq',\Ivy::app()->user->dept_id);
		}else{
            if($_GET['md_id']) $map['t.md_id']  =   array('eq',(int)$_GET['md_id']);
        }
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		if(isset($search['cu_name'])){
			$map['cu.name']	= 	array('like',"%".$search['cu_name']."%");
			unset($search['cu_name']);
		}
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \OrderRefund::model()
		->field(array("t.*,cu.name as cu_name,e.netname as made_name"))
		->join('customer_info cu on cu.id=t.cu_id')
		->join('employ_user e on e.id=t.made_id')
		->where($map)
		->order('create_time desc')
		->group('t.id')
		->getPagener();
		$this->view->assign(array(
			'pager'=>$pager,
			'timeType'=>$timeType,
			"light_nav"=>$this->url('pos/sale/index'),					//需要点亮的导航
		))->display();
	}

	/**
	 * 订单退款
	 * @param [int] $id [订单id order_sale]
	 * @return [type] [description]
	 */
	public function orderAction($id=113){
		$model=SaleModel::model()->getOrderModel($id,true);
		//var_dump($model);die;
		$capital_list=\CompanyCapital::model()
			->where("type=2 and status =1 and comp_id=".$this->company['id'] )
			->findAll();
		
		$this->view->assign(array(
			"capital_list"=>$capital_list,								//赠送现金账户
			"cu_info"=>$this->getCuInfo($model->o_s->cu_id),			//客户信息渲染
			'model'=>$model,
			"light_nav"=>$this->url('pos/index/index'),					//需要点亮的导航
		))->display();

	}


	/**
	 * 退项目 产品 
	 * @param [int] $id [客户id]
	 * @return [type] [description]
	 */
	public function proAction($id=2){
		$capital_list = \CustomerCapital::getListByUser($id);

		$project_list = \CustomerReProject::getReProList($id);

		$product_list = \CustomerProdStorageDetail::getReProList($id);

		$voucher_list = \CustomerVoucher::getReProList($id);
		//var_dump($voucher_list);die;
		
		$this->view->assign(array(
			"capital_list"=>$capital_list,								//卡内剩余项目
			"project_list"=>$project_list,								//卡内剩余项目
			"product_list"=>$product_list,								//卡内剩余项目
			"voucher_list"=>$voucher_list,								//卡内剩余项目
			"cu_info"=>$this->getCuInfo($id),							//客户信息渲染
			"light_nav"=>$this->url('pos/index/index'),					//需要点亮的导航
		))->display();

	}


	/**
	 * 制单数据保存
	 * @param [int] $id [订单id order_sale]
	 * @return [type] [description]
	 */
	public function saveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = RefundModel::model()->editNew($_POST);

		if(!$res) $this->ajaxReturn(400,RefundModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}

	/**
	 * 退款查看
	 * @param [int] $id [订单id order_sale]
	 * @return [type] [description]
	 */
	public function viewAction($id=null){
		$model = RefundModel::model()->getOrderModel($id);
		if(!$model) throw new CException("无此退款单！");

		if($model->o_s->status==0){
			$this->redirect('check',array('id'=>$id));
		}
		
		$capital_list = \CustomerCapital::getListByUser($model->o_s->cu_id);

		$this->view->assign(array(
			"model"=>$model,		
			"capital_list"=>$capital_list,							
			"cu_info"=>$this->getCuInfo($model->o_s->cu_id),			//客户信息渲染
			"light_nav"=>$this->url('pos/sale/index'),					//需要点亮的导航
		))->display();
	}
	/**
	 * 退款预览检测
	 * @param [int] $id [订单id order_sale]
	 * @return [type] [description]
	 */
	public function checkAction($id=null){
		$model = RefundModel::model()->getOrderModel($id);
		if(!$model) throw new CException("无此退款单！");
		
		$capital_list = \CustomerCapital::getListByUser($model->o_s->cu_id);

		$this->view->assign(array(
			"model"=>$model,		
			"capital_list"=>$capital_list,							
			"cu_info"=>$this->getCuInfo($model->o_s->cu_id),			//客户信息渲染
			"light_nav"=>$this->url('pos/sale/index'),					//需要点亮的导航
		))->display();
	}

	/**
	 * 退款确认 入账
	 * @param [int] $id [订单id order_sale]
	 * @return [type] [description]
	 */
	public function confirmAction($id=null){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");
		$res = RefundModel::model()->confirm($id);

		if(!$res) $this->ajaxReturn(400,RefundModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);
	}

	/**
	 * 获取卡券剩余
	 * @param [int] $id [c_v id]
	 * @return [type] [description]
	 */
	public function voucherReAction($id=4){
		$res = \CustomerVoucher::getLastPro($id);
		if(!$res) $this->ajaxReturn(400,\CustomerVoucher::model()->popErr());

		$reMoney = \CustomerVoucher::getLastMoney($id);
		$proj=$prod=array();
		foreach ($res as $pro) {
			$tmp['name']=$pro->name;
			$tmp['re_num']=$pro->num;
			if($pro->pro_type==1)
				$proj[]=$tmp;
			else
				$prod[]=$tmp;
		}
		$this->ajaxReturn(200,"查询成功！",array('money'=>$reMoney,'xm'=>$proj,'cp'=>$prod));
	}

	/**
	 * 订单废除（未付款可用）
	 * @param [type] $id 订单id
	 */
	public function delAction($id=null){
		$old_order = \OrderRefund::model()->findByPk($id);
		if($old_order->status==1) throw new CException('已经付款，无法废除！');
		if(!$old_order->delete()){
			throw new CException('废除失败！');
		}
		$this->redirect('index');
	}

}