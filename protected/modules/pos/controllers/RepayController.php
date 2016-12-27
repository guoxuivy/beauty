<?php
namespace pos;
use Ivy\core\CException;
/**
 * 订单--还款
 */
class RepayController extends BaseController
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
            ->where('t.type=0')
            ->order('create_time desc')
            ->group('t.id')
            ->getPagener();
        //var_dump($pager);

        $this->view->assign(array('pager'=>$pager,'timeType'=>$timeType,"light_nav"=>$this->url('pos/sale/index'),))->display();

    }


    /**
     * 还款
     * @param [type] $id [description]
     * @throws CException
     */
	public function addAction($id=null){

		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id);
        $cu_capitals = SaleModel::model()->getCuCapitalDetails($id);
        $arrears_orders = SaleModel::model()->getArrearsOrder($id);
        $arrears_order_detail = SaleModel::model()->getArrearsOrderDetail($id);
        foreach($arrears_order_detail as $key=>$detail){
            $pro_info = SaleModel::model()->getProInfo($detail['pro_id'],$detail['pro_type']);
            $arrears_order_detail[$key]['pro_name'] = $pro_info['name'];
        }
		$this->view
            ->assign(
                array(
                    "cu_info"=>$cu_info,							//客户信息渲染
                    "effect"=>$this->getEffect(null),				//业绩比例渲染
                    "light_nav"=>$this->url('add'),				    //需要点亮的导航
                    "arrears_order_detail"=>$arrears_order_detail,
                    "cu_capitals"=>$cu_capitals,
                )
            )
            ->display('edit');
	}

    public function capitals_viewAction($id=null){
        $_cu_capitals = SaleModel::model()->getCuCapitalDetails($id);
        $params = explode('|',$_POST['param']);
        $pro_info = SaleModel::model()->getProInfo($params[0],$params[1]);
        $pro_name = $pro_info['name'].'('.$pro_info['num'].'疗次/'.$pro_info['price']*$pro_info['num'].'元)';
        $order_detail = \OrderSaleDetail::model()->findByPk($params[2]);
        $order = \OrderSale::model()->findByPk($order_detail['order_id']);
        $cu_capital_list = \CustomerCapital::model()
            ->field("t.capital_id,t.balance")
            ->join("company_capital cc on cc.id=t.capital_id")
            ->where("t.cu_id={$_POST['cu_id']} and cc.type=1")
            ->findAll();
        $cu_capitals = array();
        foreach($cu_capital_list as $cu_capital){
            $cu_capitals[$cu_capital['capital_id']] = $cu_capital['balance'];
        }
        $_capitals = explode(',',$_POST['capitals']);
        foreach($_capitals as $_capital){
            $_capital_ = explode('|',$_capital);
            $capitals_arr[$_capital_[0]] = $_capital_[1];
        }

        if(!empty($id)){
            $capitals = $this->getCapitalsView($params[2],$id);
            $data = array(
                'pro_name'=>$pro_name,
                'arrears'=>$order_detail->arrears,
                'sn'=>$order->sn,
                'detail_id'=>$params[2],
                'capitals'=>$capitals,
                'cu_capitals'=>$cu_capitals,
                'capitals_arr'=>$capitals_arr,
            );
        }else{
            $data = array(
                'pro_name'=>$pro_name,
                'arrears'=>$order_detail->arrears,
                'sn'=>$order->sn,
                'detail_id'=>$params[2],
                'cu_capitals'=>$cu_capitals,
                'capitals_arr'=>$capitals_arr,
            );
        }
        echo $this->view->assign(
            array(
                "cu_capitals"=>$_cu_capitals,
                "data"=>$data,
                )
        )->render();
    }

    /**
     * 现金卡扣弹出
     */
    public function capitalsAction($id=null){
        $params = explode('|',$_POST['param']);
        $pro_info = SaleModel::model()->getProInfo($params[0],$params[1]);
        $pro_name = $pro_info['name'].'('.$pro_info['num'].'疗次/'.$pro_info['price']*$pro_info['num'].'元)';
        $order_detail = \OrderSaleDetail::model()->findByPk($params[2]);
        $order = \OrderSale::model()->findByPk($order_detail['order_id']);
        $cu_capital_list = \CustomerCapital::model()
            ->field("t.capital_id,t.balance")
            ->join("company_capital cc on cc.id=t.capital_id")
            ->where("t.cu_id={$_POST['cu_id']} and cc.type=1")
            ->findAll();
        $cu_capitals = array();
        foreach($cu_capital_list as $cu_capital){
            $cu_capitals[$cu_capital['capital_id']] = $cu_capital['balance'];
        }
        if(!empty($id)){
            $capitals = $this->getCapitalsView($params[2],$id);
            $data = array(
                'pro_name'=>$pro_name,
                'arrears'=>$order_detail->arrears,
                'sn'=>$order->sn,
                'detail_id'=>$params[2],
                'capitals'=>$capitals,
                'cu_capitals'=>$cu_capitals,
            );
        }else{
            $data = array(
                'pro_name'=>$pro_name,
                'arrears'=>$order_detail->arrears,
                'sn'=>$order->sn,
                'detail_id'=>$params[2],
                'cu_capitals'=>$cu_capitals,
            );
        }
        echo json_encode($data);
    }

    /**
     * 获取欠款商品、项目名称
     * @param $pro_id
     * @param $pro_type
     * @return mixed
     */
    public function getProName($pro_id,$pro_type){
        // 项目
        if($pro_type==1){
            $project_info = \ProjectInfo::model()
                ->field("t.name")
                ->where("t.id={$pro_id}")
                ->findAll();
            return $project_info[0]['name'];
        }
        // 产品
        if($pro_type==2){
            $product_info = \ProductInfo::model()
                ->field("t.name")
                ->where("t.id={$pro_id}")
                ->findAll();
            return $product_info[0]['name'];
        }
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

    /**
     * 编辑订单(如果已经付款 这进入查看模式)
     */
    public function editAction($id=null){
        if(is_null($id))
            throw new CException('无效订单id');
        $model = SaleModel::model()->getOrderModel($id);

        if($model->o_s->pay_status==1){
            $view="view";
            $is_view=true;
        }else{
            $view="edit";
            $is_view=false;
        }
        if($model->o_s->status==-1){
            $view="view";
            $is_view=true;
        }

        $cu_capitals = SaleModel::model()->getCuCapitalDetails($model->o_s->cu_id);
        if(is_null($id)||$model->o_s->pay_status!=1){
            $arrears_order_detail = SaleModel::model()->getArrearsOrderDetail($model->o_s->cu_id);
            foreach($arrears_order_detail as $key=>$detail){
                $pro_info = SaleModel::model()->getProInfo($detail['pro_id'],$detail['pro_type']);
                $arrears_order_detail[$key]['pro_name'] = $pro_info['name'];
                $arrears_order_detail[$key]['repay_cash'] = $this->getDetailCash($detail['id'],$id);
                $arrears_order_detail[$key]['repay_capitals'] = $this->getDetailCapitals($detail['id'],$id);
            }
        }else{
            $repay_detail = \OrderRepayDetail::model()
                ->field("t.*")
                ->where("t.order_id={$id}")
                ->findAll();
            $order_ids = array();
            foreach($repay_detail as $key=>$detail){
                if(!in_array($detail['order_detail_id'],$order_ids)){
                    $order_ids[] = $detail['order_detail_id'];
                    $_detail = \OrderSaleDetail::model()->findByPK($detail['order_detail_id']);
                    $pro_info = SaleModel::model()->getProInfo($_detail['pro_id'],$_detail['pro_type']);
                    $arrears_order_detail[$detail['order_detail_id']]['pro_name'] = $pro_info['name'];
                    $arrears_order_detail[$detail['order_detail_id']]['create_time'] = $detail['create_time'];
                    $arrears_order_detail[$detail['order_detail_id']]['pro_id'] = $_detail['pro_id'];
                    $arrears_order_detail[$detail['order_detail_id']]['pro_type'] = $_detail['pro_type'];
                    $arrears_order_detail[$detail['order_detail_id']]['id'] = $_detail['id'];
                    $arrears_order_detail[$detail['order_detail_id']]['repay_cash'] = $this->getDetailCash($_detail['id'],$id);
                    $arrears_order_detail[$detail['order_detail_id']]['repay_capitals'] = $this->getDetailCapitals($_detail['id'],$id);
                    $cpm = $arrears_order_detail[$detail['order_detail_id']]['repay_capitals']['money'];
                    $arrears_order_detail[$detail['order_detail_id']]['arrears'] = $detail['before_money'];
                    $arrears_order_detail[$detail['order_detail_id']]['after_arrears'] = $detail['before_money']-$cpm-$arrears_order_detail[$detail['order_detail_id']]['repay_cash'];
                }

            }
        }
        $this->view->assign(array(
            "cu_info"=>$this->getCuInfo($model->o_s->cu_id),	//客户信息渲染
            "effect"=>$this->getEffect($id,$is_view),					//业绩比例渲染
            "gift"=>$this->getGift($id,$is_view),						//业绩比例渲染
            "model"=>$model,											//订单货物模型
            "light_nav"=>$this->url('pos/index/index'),					//需要点亮的导航
            "arrears_order_detail"=>$arrears_order_detail,
            "cu_capitals"=>$cu_capitals,
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
     * 获取订单详细的现金还款
     * @param $order_detail_id
     * @return mixed
     */
    public function getDetailCash($order_detail_id,$order_id){
        $cash = \OrderRepayDetail::model()
            ->where("t.order_detail_id={$order_detail_id} and t.order_id={$order_id} and t.type=1")
            ->find();
        return $cash->money;
    }

    /**
     * 获取订单详细的现金卡扣还款(编辑订单)
     * @param $order_detail_id
     * @return array
     */
    public function getDetailCapitals($order_detail_id,$order_id){
        $repay_details = \OrderRepayDetail::model()
            ->field('t.cu_capital_id,t.money,cc.capital_id as capital_id')
            ->join("customer_capital as cc on cc.id=t.cu_capital_id")
            ->where("t.order_detail_id={$order_detail_id} and t.order_id={$order_id} and t.type=2")
            ->findAll();
        $capitals = array();$money = null;
        foreach($repay_details as $repay_detail){
            $capitals[] = $repay_detail['capital_id'].'|'.$repay_detail['money'];
            $money += $repay_detail['money'];
        }
        $data = array('capital'=>implode(',',$capitals),'money'=>$money);
        return $data;
    }

    /**
     * 获取订单详细的现金卡扣还款(查看订单)
     * @param $order_detail_id
     * @return array
     */
    public function getCapitalsView($order_detail_id,$order_id){
        $repay_details = \OrderRepayDetail::model()
            ->field('t.cu_capital_id,t.money,cc.capital_id as capital_id')
            ->join("customer_capital as cc on cc.id=t.cu_capital_id")
            ->where("t.order_detail_id={$order_detail_id} and t.order_id={$order_id} and t.type=2")
            ->findAll();
        $capitals = array();
        foreach($repay_details as $repay_detail){
            $capitals[$repay_detail['capital_id']] = $repay_detail['money'];
        }
        return $capitals;
    }

    /**
     * 充值单付款界面
     * @param [type] $id 订单id
     * @throws CException
     */
	public function orderAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = SaleModel::model()->getOrderModel($id);
		$cu_info = $this->getCuInfo($model->o_s->cu_id);
        $effect_list = SaleModel::model()->getUserEffect($id);
        $order_sale_detail = \OrderSaleDetail::model()
            ->field("t.*")
            ->join("order_repay_detail as ord on t.id=ord.order_detail_id")
            ->where("ord.order_id={$id}")
            ->group("t.id")
            ->findAll();
        foreach($order_sale_detail as $key=>$detail){
            $pro_info = SaleModel::model()->getProInfo($detail['pro_id'],$detail['pro_type']);
            $order_sale_detail[$key]['pro_name'] = $pro_info['name'];
            $order_sale_detail[$key]['repay'] = \OrderRepayDetail::model()->getRepayMoney($id,$detail['id']);
        }

		$this->view->assign(array(
			"model"=>$model,	
			"cu_info"=>$cu_info,							//客户信息渲染
            "effect"=>$this->getEffect($id,true),
            "effect_list"=>$effect_list,
			"order_sale_detail"=>$order_sale_detail,
			"light_nav"=>$this->url('index'),				//需要点亮的导航
		))->display('order');
	}

    public function payAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        $model = SaleModel::model()->getOrderModel($id);
        $cu_info = $this->getCuInfo($model->o_s->cu_id);
        $cu_capitals = SaleModel::model()->getCuCapitalDetails($model->o_s->cu_id);
        $repay_detail = \OrderRepayDetail::model()
            ->field("t.*")
            ->where("t.order_id={$id}")
            ->findAll();
        $order_ids = array();
        foreach($repay_detail as $key=>$detail){
            if(!in_array($detail['order_detail_id'],$order_ids)){
                $order_ids[] = $detail['order_detail_id'];
                $_detail = \OrderSaleDetail::model()->findByPK($detail['order_detail_id']);
                $pro_info = SaleModel::model()->getProInfo($_detail['pro_id'],$_detail['pro_type']);
                $arrears_order_detail[$detail['order_detail_id']]['pro_name'] = $pro_info['name'];
                $arrears_order_detail[$detail['order_detail_id']]['create_time'] = $detail['create_time'];
                $arrears_order_detail[$detail['order_detail_id']]['pro_id'] = $_detail['pro_id'];
                $arrears_order_detail[$detail['order_detail_id']]['pro_type'] = $_detail['pro_type'];
                $arrears_order_detail[$detail['order_detail_id']]['id'] = $_detail['id'];
                $arrears_order_detail[$detail['order_detail_id']]['repay_cash'] = $this->getDetailCash($_detail['id'],$id);
                $arrears_order_detail[$detail['order_detail_id']]['repay_capitals'] = $this->getDetailCapitals($_detail['id'],$id);
                $cpm = $arrears_order_detail[$detail['order_detail_id']]['repay_capitals']['money'];
                $arrears_order_detail[$detail['order_detail_id']]['arrears'] = $detail['before_money'];
                $arrears_order_detail[$detail['order_detail_id']]['after_arrears'] = $detail['before_money']-$cpm-$arrears_order_detail[$detail['order_detail_id']]['repay_cash'];
            }

        }

        $order_sale_detail = \OrderSaleDetail::model()
            ->field("t.*")
            ->join("order_repay_detail as ord on t.id=ord.order_detail_id")
            ->where("ord.order_id={$id}")
            ->group("t.id")
            ->findAll();
        foreach($order_sale_detail as $key=>$detail){
            $pro_info = SaleModel::model()->getProInfo($detail['pro_id'],$detail['pro_type']);
            $order_sale_detail[$key]['pro_name'] = $pro_info['name'];
            $order_sale_detail[$key]['repay'] = \OrderRepayDetail::model()->getRepayMoney($id,$detail['id']);
        }

        $this->view->assign(array(
            "model"=>$model,
            "cu_info"=>$cu_info,							//客户信息渲染
            "arrears_order_detail"=>$arrears_order_detail,
            "cu_capitals"=>$cu_capitals,
            "order_sale_detail"=>$order_sale_detail,
            "light_nav"=>$this->url('/pos/index/nav'),				//需要点亮的导航
        ))->display('pay');
    }

    /**
     * 还款单付款提交
     * @param [type] $id 订单id
     * @throws CException
     */
    public function paySaveAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");
        $res = \OrderRepayDetail::model()->updateArrears($_POST);
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

}
