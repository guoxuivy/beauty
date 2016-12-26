<?php
namespace pos;
use Ivy\core\CException;
/**
 * 期初订单
 */
class InitController extends BaseController
{
	/**
	 * 订单列表
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
		$map['t.is_init']	= 	array('eq',1);
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
		$pager= \OrderSale::model()
		->field(array("t.*,cu.name as cu_name,e.netname as made_name, IFNULL( SUM(t_d.card_gift), 0) as card_gift "))
		->join('customer_info cu on cu.id=t.cu_id')
		->join('order_sale_detail t_d on t_d.order_id=t.id')
		->join('employ_user e on e.id=t.made_id')
		->where($map)
		->order('create_time desc')
		->group('t.id')
		->getPagener();
		//var_dump($pager);

		$this->view->assign(
			array(
				'pager'=>$pager,
				'timeType'=>$timeType,
				"light_nav"=>$this->url('pos/sale/index'),
			)
		)->display();

	}
	

	/**
	 * 创建新订单
	 * @param [type] $id [description]
	 */
	public function addAction($id=null,$tag=null){
		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id);
		$capital_list=\CompanyCapital::model()
			->where("status =1 and comp_id=".$this->company['id'] )
			->findAll();
		$this->view->assign(array(
			"cu_info"=>$cu_info,							//客户信息渲染
			"gift"=>$this->getGift(null),					//业绩比例渲染
			"tag"=>$tag,									//入会标签
			"capital_list"=>$capital_list,
			"light_nav"=>$this->url('customer/init/index'),		//需要点亮的导航
		))->display('edit');
	}


	/**
	 * 新建订单提交（非编辑提交）
	 */
	public function addSaveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = SaleModel::model()->saveNew($_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}


	/**
	 * 编辑订单(如果已经付款 这进入查看模式) 
	 */
	public function editAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');
		$model = SaleModel::model()->getOrderModel($id,true);
		
		if($model->o_s->pay_status==1){
			$view="view";
			$is_view=true;
		}else{
			$view="edit";
			$is_view=false;
		}
		$capital_list=\CompanyCapital::model()
			->where("status =1 and comp_id=".$this->company['id'] )
			->findAll();
		$this->view->assign(array(
			"cu_info"=>$this->getCuInfo($model->o_s->cu_id),	//客户信息渲染
			"gift"=>$this->getGift($id,$is_view),						//业绩比例渲染
			"model"=>$model,											//订单货物模型
			"capital_list"=>$capital_list,
			"light_nav"=>$this->url('customer/init/index'),					//需要点亮的导航				

		))->display($view);
	}
	/**
	 * 编辑订单提交（编辑提交）
	 */
	public function editSaveAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = SaleModel::model()->editNew($id,$_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

		$this->ajaxReturn(200,"保存成功！",$res);

	}

	/**
	 * 订单废除（未付款可用）
	 * @param [type] $id 订单id
	 */
	public function delAction($id=null){
		$old_order = \OrderSale::model()->findByPk($id);
		if($old_order->pay_status==1) throw new CException('已经付款，无法废除！');
		if(!$old_order->orderDelete()){
			throw new CException('废除失败！');
		}
		$CustomerInfo=\CustomerInfo::model()->findByPk($old_order->cu_id);
		$CustomerInfo->cart_init=0;
		$CustomerInfo->save();
		$this->redirect('index');
	}


	/**
	 * 订单付款界面
	 * @param [type] $id 订单id
	 */
	public function payAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = SaleModel::model()->getOrderModel($id,true);
		$cu_info=$this->getCuInfo($model->o_s->cu_id);

		$capital_list=\CompanyCapital::model()
			->where("status =1 and comp_id=".$this->company['id'] )
			->findAll();
		$this->view->assign(array(
			"cu_info"=>$this->getCuInfo($model->o_s->cu_id),	//客户信息渲染
			"gift"=>$this->getGift($id,$is_view),						//业绩比例渲染
			"model"=>$model,											//订单货物模型
			"capital_list"=>$capital_list,
			"light_nav"=>$this->url('customer/init/index'),					//需要点亮的导航				

		))->display("pay");
	}
	/**
	 * 订单付款提交
	 * @param [type] $id 订单id
	 */
	public function paySaveAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = SaleModel::model()->payForInit($id);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

		$this->ajaxReturn(200,"开账成功！",$res);
	}

	/**
	 * 入会购买
	 * @param [type] $id 客户id
	 * @param [type] $type 单类型 （购买sale ,充值recharge)
	 * @param [type] $level 会员等级（入会后会员等级ID）
	 */
	public function rhAction($id=null,$type){
		switch ($type) {
			case 'sale':
				$this->redirect('add',array('id'=>$id,'tag'=>"rh"));
				break;
			case 'recharge':
				$this->redirect('pos/recharge/add',array('id'=>$id,'tag'=>"rh"));
				break;
		}
		
	}



}
