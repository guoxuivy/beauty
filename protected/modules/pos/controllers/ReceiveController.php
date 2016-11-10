<?php
namespace pos;
use Ivy\core\CException;
/**
 * 寄存领取
 */
class ReceiveController extends BaseController
{

	/**
	 * 订单列表
	 * @param [type] $id [description]
	 */
	public function indexAction(){
        $timeType=isset($_GET['stime'])?$_GET['stime']:"1";
        $stime = $this->sTime($timeType);
        if($stime){
            $map['cpr.create_time']   =   array(array('gt',$stime['begin']),array('lt',$stime['end']));
        }
		
        if($this->is_store){
            $map['cpr.dept_id']	= 	array('eq',\Ivy::app()->user->dept_id);
        }else{
            if($_GET['md_id']) $map['cpr.dept_id']  =   array('eq',(int)$_GET['md_id']);
        }
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
        if(isset($search['cu_name'])){
            $map['cu.name']	= 	array('like',"%".$search['cu_name']."%");
            unset($search['cu_name']);
        }
        if(isset($search['product_name'])){
            $map['pdi.name']	= 	array('like',"%".$search['product_name']."%");
            unset($search['product_name']);
        }
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \CustomerProdReceiveDetail::model()
		->field(array("cpr.sn as sn,cpr.create_time as create_time,cu.name as cu_name,pdi.name as product_name,t.before_num as before_num,
		    0 as storage_num,t.num as receive_num,t.after_num as after_num,cpr.status as status,cpr.re_status as re_status,
		    e.netname as made_name,cpr.id as id")
        )
        ->join('customer_prod_receive cpr on t.order_id=cpr.id')
		->join('customer_info cu on cu.id=cpr.cu_id')
		->join('product_info pdi on t.product_id=pdi.id')
		->join('employ_user e on e.id=cpr.create_user')
		->where($map)
		->order('create_time desc')
		->group('t.id')
		->getPagener();
		//var_dump($pager);

		$this->view->assign(array('pager'=>$pager,'timeType'=>$timeType,"light_nav"=>$this->url('pos/sale/index'),))->display();

	}


    /**
     * 领取单
     * @param [type] $id [description]
     * @throws CException
     */
	public function addAction($id=null){
		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id);
        $storageProducts = $this->getStorageProducts($id,false);
        $detail_nums = array();
        if(!empty($_GET['rid'])){
            $details = \CustomerProdReceive::model()->getDetails($_GET['rid']);
            foreach($details as $detail){
                $detail_nums[$detail['storage_detail_id']] = $detail['num'];
            }
        }
		$this->view
            ->assign(
                array(
                    "cu_info"=>$cu_info,							//客户信息渲染
                    "detail_nums"=>$detail_nums,
                    "storageProducts"=>$storageProducts,
                    "light_nav"=>$this->url('pos/sale/index'),				    //需要点亮的导航
                )
            )
            ->display('edit');
	}

    public function getStorageProducts($cu_id,$is_view){
        $map['t.cu_id']	= 	array('eq',$cu_id);
        if(!$is_view){
            $map['t.remain_num']	= 	array('gt',0);
        }
        $storageProducts = \CustomerProdStorageDetail::model()
            ->field("t.*,cps.order_id as order_id")
            ->join("customer_prod_storage as cps on t.storage_id=cps.id")
            ->where($map)
            ->findAll();
        return $storageProducts;
    }

	/**
	 * 新建寄存领取单提交（非编辑提交）
	 */
	public function addSaveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = \CustomerProdReceive::model()->edit($_POST);

		if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}

    /**
     * 编辑订单(如果已经领取 则进入查看模式)
     */
    public function editAction($id=null){
        if(is_null($id))
            throw new CException('无效订单id');
        $model = \CustomerProdReceive::model()->findByPk($id);

        if($model->re_status==1){
            $view="view";
            $is_view=true;
        }else{
            $view="edit";
            $is_view=false;
        }
        if($model->status==0){
            $view="view";
            $is_view=true;
        }

        $storageProducts = $this->getStorageProducts($model->cu_id,$is_view);
        $detail_nums = array();
        $details = \CustomerProdReceive::model()->getDetails($id);
        foreach($details as $detail){
            $detail_nums[$detail['storage_detail_id']]['before_num'] = $detail['before_num'];
            $detail_nums[$detail['storage_detail_id']]['num'] = $detail['num'];
            $detail_nums[$detail['storage_detail_id']]['after_num'] = $detail['after_num'];
        }
        $this->view
            ->assign(
                array(
                    "cu_info"=>$this->getCuInfo($model->cu_id,$is_view),							//客户信息渲染
                    "detail_nums"=>$detail_nums,
                    "model"=>$model,
                    "storageProducts"=>$storageProducts,
                    "light_nav"=>$this->url('pos/sale/index'),				    //需要点亮的导航
                )
            )
            ->display($view);
    }

    /**
     * 编辑寄存领取单提交（编辑提交）
     */
    public function editSaveAction($id=null){
        if(is_null($id))
            $this->ajaxReturn(400,"无效订单id");
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $res = \CustomerProdReceive::model()->edit($_POST,$id);

        if(!$res) $this->ajaxReturn(400,SaleModel::model()->popErr());

        $this->ajaxReturn(200,"保存成功！",$res);

    }

    /**
     * 寄存领取单确认界面
     * @param [type] $id 订单id
     * @throws CException
     */
	public function payAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = \CustomerProdReceive::model()->findByPk($id);
		$cu_info = $this->getCuInfo($model->cu_id,false);
        $details = $model->getDetails($id);
		$this->view->assign(array(
			"model"=>$model,	
			"cu_info"=>$cu_info,							//客户信息渲染
			"details"=>$details,
			"light_nav"=>$this->url('pos/sale/index'),				//需要点亮的导航
		))->display('pay');
	}

    /**
     * 寄存领取单确认领取提交
     * @param [type] $id 订单id
     * @throws CException
     */
    public function paySaveAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $res = \CustomerProdReceive::model()->updateStorage($id);
        if(!$res) $this->ajaxReturn(400,\CustomerProdReceive::model()->popErr());

        $this->ajaxReturn(200,"保存成功！",$res);
    }

    public function cancelAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $model = \CustomerProdReceive::model()->findByPk($id);
        $model->status = 0;
        if(!$model->save()) $this->ajaxReturn(400,SaleModel::model()->popErr());

        $this->ajaxReturn(200,"废弃订单成功！",$model->cu_id);
    }

}
