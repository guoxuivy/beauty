<?php
namespace pos;
use Ivy\core\CException;
/**
 * 订单--充值
 */
class RechargeController extends BaseController
{

	/**
	 * 订单列表
	 * @param [type] $id [description]
	 */
	public function indexAction(){
        $timeType=isset($_GET['stime'])?$_GET['stime']:"1";
        $stime = $this->sTime($timeType);
        if($stime){
            $map['t.create_time']   =   array(array('gt',$stime['begin']),array('lt',$stime['end']));
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
        $pager= \OrderSale::model()
            ->field(array("t.*,cu.name as cu_name,e.netname as made_name, IFNULL( SUM(t_d.card_gift), 0) as card_gift "))
            ->join('customer_info cu on cu.id=t.cu_id')
            ->join('order_sale_detail t_d on t_d.order_id=t.id')
            ->join('employ_user e on e.id=t.made_id')
            ->where($map)
            ->where('t.type=2')
            ->order('create_time desc')
            ->group('t.id')
            ->getPagener();
        //var_dump($pager);

        $this->view->assign(array('pager'=>$pager,'timeType'=>$timeType,"light_nav"=>$this->url('pos/sale/index'),))->display();

	}


    /**
     * 创建新充值单
     * @param [type] $id [description]
     * @throws CException
     */
	public function addAction($id=null,$tag=null){

		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id);
        $cu_capitals = SaleModel::model()->getCuCapitals($id);
		$this->view->assign(array(
                "cu_info"=>$cu_info,							//客户信息渲染
                "effect"=>$this->getEffect(null),				//业绩比例渲染
                "gift"=>$this->getGift(null),					//业绩比例渲染
                "tag"=>$tag,                                    //入会标签
                "light_nav"=>$this->url('add'),				    //需要点亮的导航
                "cu_capitals"=>$cu_capitals,
        ))->display('edit');
	}


	/**
	 * 新建充值单提交（非编辑提交）
	 */
	public function addSaveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");
		$res = SaleModel::model()->saveNew($_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}

    public function orderAction($id=null){
        if(is_null($id))
            throw new CException('无效客户id');
        $cu_capitals = SaleModel::model()->getCzCuCapitals($id);
        $model = SaleModel::model()->getOrderModel($id);
        $cu_info=$this->getCuInfo($model->o_s->cu_id);
        $gift_view = $this->getGift($id,true);
        $effect_list = SaleModel::model()->getUserEffect($id);
        $this->view->assign(array(
            "model"=>$model,
            "cu_info"=>$cu_info,						//客户信息渲染
            "cu_capitals"=>$cu_capitals,				//客户充值账号和金额
            "gift_view"=>$gift_view,				//客户充值账号和金额
            "effect_list"=>$effect_list,				//客户充值账号和金额

            "effect"=>$this->getEffect($id,true),                   //业绩比例渲染
            "light_nav"=>$this->url('add'),				//需要点亮的导航
        ))->display('order');
    }

    /**
     * 编辑订单(如果已经付款 这进入查看模式)
     */
    public function editAction($id=null,$is_view=false){
        if(is_null($id))
            throw new CException('无效订单id');
        $model = SaleModel::model()->getOrderModel($id);

        if($model->o_s->pay_status==1 ||$is_view){
            $view="view";
            $is_view=true;
            $cu_capitals = SaleModel::model()->getCzCuCapitals($id);
        }else{
            $view="edit";
            $is_view=false;
            $cu_capitals = SaleModel::model()->getCuCapitals($model->o_s->cu_id);
        }
        if($model->o_s->status==-1){
            $view="view";
            $is_view=true;
            $cu_capitals = SaleModel::model()->getCzCuCapitals($id);
        }

        $order_detail = SaleModel::model()->getOrderSaleDetail($id);
        foreach($order_detail as $detail){
            $details[$detail['pro_id']] = $detail['price'];
        }
        $this->view->assign(array(
            "cu_info"=>$this->getCuInfo($model->o_s->cu_id),	//客户信息渲染
            "effect"=>$this->getEffect($id,$is_view),					//业绩比例渲染
            "gift"=>$this->getGift($id,$is_view),						//业绩比例渲染
            "model"=>$model,											//订单货物模型
            "light_nav"=>$this->url('pos/index/index'),					//需要点亮的导航
            "cu_capitals"=>$cu_capitals,
            "details"=>$details,
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
     * 废弃订单
     * @param null $id
     * @throws CException
     */
    public function cancelAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $model = \OrderSale::model()->findByPk($id);
        $model->status = -1;
        if(!$model->save()) $this->ajaxReturn(400,SaleModel::model()->popErr());

        $this->ajaxReturn(200,"废弃订单成功！",$model->cu_id);
    }


    /**
     * 充值单付款界面
     * @param [type] $id 订单id
     * @throws CException
     */
	public function payAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = SaleModel::model()->getOrderModel($id);
		$cu_info = $this->getCuInfo($model->o_s->cu_id);
        $order_sale_detail = SaleModel::model()->getOrderSaleDetail($id);

		$this->view->assign(array(
			"model"=>$model,	
			"cu_info"=>$cu_info,							//客户信息渲染
			"order_sale_detail"=>$order_sale_detail,							//客户信息渲染
			"light_nav"=>$this->url('index'),				//需要点亮的导航
		))->display('pay');
	}

    /**
     * 充值单付款提交
     * @param [type] $id 订单id
     * @throws CException
     */
    public function paySaveAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $res = SaleModel::model()->pay($id,$_POST);

        if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

        $this->ajaxReturn(200,"保存成功！",$res);
    }

}
