<?php
namespace pos;
use Ivy\core\CException;
/**
 * 订单-卡券兑换
 */
class VoucherController extends BaseController
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
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
        if(isset($search['cu_name'])){
            $map['cu.name']	= 	array('like',"%".$search['cu_name']."%");
            unset($search['cu_name']);
        }
        if(isset($search['voucher_name'])){
            $map['cv.name']	= 	array('like',"%".$search['voucher_name']."%");
            unset($search['voucher_name']);
        }
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \OrderVoucher::model()
		->field(array("t.*,FROM_UNIXTIME(t.pay_time) as pay_time,cu.name as cu_name,e.netname as made_name,cv.name as voucher_name"))
		->join('customer_info cu on cu.id=t.cu_id')
		->join('customer_voucher cuv ON cuv.id=t.vo_id')
		->join('config_voucher cv on cv.id=cuv.voucher_id')
		->join('employ_user e on e.id=t.made_id')
		->where($map)
		->order('create_time desc')
		->group('t.id')
		->getPagener();
		//var_dump($pager);

		$this->view->assign(array('pager'=>$pager,'timeType'=>$timeType,"light_nav"=>$this->url('pos/sale/index'),))->display();

	}
	/**
	 * 显示页面
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function viewAction($id)
	{
		$model = SaleModel::model()->getVoucherModel($id,true);
		if(empty($model))
			throw new CException('无效订单');
		$cu_info=$this->getCuInfo($model->o_s->cu_id);
		$CustomerVoucher=\CustomerVoucher::model()->field('cv.*,t.*')->join("config_voucher cv on t.voucher_id=cv.id")->findByPk($model->o_s->vo_id);
		$this->view->assign(array(
			"id"              =>$id,
			'model'           =>$model,
			"cu_info"         =>$cu_info,		//客户信息渲染
			"CustomerVoucher" =>$CustomerVoucher,//可用卡券
		))->display();
	}
	/**
	 * 获取卡券剩余页面渲染
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function  getVoucherAction($id,$order_id=0){
		if($order_id>0)
			$model = SaleModel::model()->getVoucherModel($order_id,true);
		else
			$model=null;
		$CustomerVoucher=\CustomerVoucher::model()->join("config_voucher cv on t.voucher_id=cv.id")->findByPk($id);
		$lastMoney=\CustomerVoucher::getLastMoney($id);
		$lastMoneyZS=\CustomerVoucher::getLastMoneyZS($id);
		$lastPro=\CustomerVoucher::getLastPro($id);
		echo $this->view->assign(array(
			'id'              =>$id,
			'order_id'        =>$order_id,
			'lastMoney'       =>$lastMoney,
			'lastMoneyZS'       =>$lastMoneyZS,
			'lastPro'         =>$lastPro,
			'model'           =>$model,
			'CustomerVoucher' =>$CustomerVoucher,
			))->render();
	}

	/**
	 * 创建新订单
	 * @param [type] $id [description]
	 */
	public function addAction($id=null){
		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id);
		$CustomerVoucher=\CustomerVoucher::getList($id);
		$this->view->assign(array(
			"id"			  =>$id,
			"cu_info"         =>$cu_info,		//客户信息渲染
			"CustomerVoucher" =>$CustomerVoucher,//可用卡券
		))->display('edit');
	}


	/**
	 * 新建订单提交（非编辑提交）
	 */
	public function addSaveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = SaleModel::model()->saveVoucherNew($_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}


	/**
	 * 编辑订单(未付款的订单)
	 */
	public function editAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');
		$model = SaleModel::model()->getVoucherModel($id,true);
        if($model->o_s->status==1||$model->o_s->status==-1){
            if(empty($model))
                throw new CException('无效订单');
            $cu_info=$this->getCuInfo($model->o_s->cu_id);
            $CustomerVoucher=\CustomerVoucher::model()
	            ->field("t.*,cv.*,t.id,osd.buy_type")
	            ->join("config_voucher cv on t.voucher_id=cv.id")
	            ->join('order_sale_detail osd on t.detail_id=osd.id')
	            ->findByPk($model->o_s->vo_id);
            $this->view->assign(array(
                "id"              =>$id,
                'model'           =>$model,
                "cu_info"         =>$cu_info,		//客户信息渲染
                "CustomerVoucher" =>$CustomerVoucher,//可用卡券
            ))->display('view');
        }else{
            $cu_info=$this->getCuInfo($model->o_s->cu_id);
            $CustomerVoucher=\CustomerVoucher::getList($model->o_s->cu_id);
            $this->view->assign(array(
                "cu_info"         =>$cu_info,		//客户信息渲染
                "model"           =>$model,			//订单货物模型
                "CustomerVoucher" =>$CustomerVoucher,//可用卡券
            ))->display('edit');
        }


		

	}
	/**
	 * 编辑订单提交（编辑提交）
	 */
	public function editSaveAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = SaleModel::model()->editVoucherNew($id,$_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

		$this->ajaxReturn(200,"保存成功！",$res);

	}


	/**
	 * 订单付款界面
	 * @param [type] $id 订单id
	 */
	public function payAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = SaleModel::model()->getVoucherModel($id,true);
		$cu_info=$this->getCuInfo($model->o_s->cu_id);
		$CustomerVoucher=\CustomerVoucher::getList($model->o_s->cu_id);

		$this->view->assign(array(
			"cu_info"         =>$cu_info,		//客户信息渲染
			"model"           =>$model,			//订单货物模型
			"CustomerVoucher" =>$CustomerVoucher,//可用卡券
		))->display('pay');
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

		$res = SaleModel::model()->editVoucherNew($id,$_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

		$this->ajaxReturn(200,"保存成功！",$res);
	}

}
