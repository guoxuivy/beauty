<?php

namespace invo;
use Ivy\core\CException;
/**
 * invo 仓库管理
 */
class InventoryController extends \SController{

    /**
     * 库存盘点记录
     */
    public function indexAction() {
        $map['t.comp_id']	= 	array('eq',$this->company['id']);
        $map['t.dept_id']	= 	array('eq',\Ivy::app()->user->dept_id);
        $map['t.status']	=	array('egt',0);
        $list = Inventory::model()
            ->field(array("t.*","FROM_UNIXTIME(UNIX_TIMESTAMP(t.create_time),'%Y-%m-%d') as create_time"))
            ->where($map)
            ->findAll();
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if($value!=''){
                if(strpos($value,'%')===false)
                    $map[$key]	= 	array('eq',$value);
                else
                    $map[$key]	= 	array('like',$value);
            }
        }

        $pager= Inventory::model()
            ->field(array("t.*","FROM_UNIXTIME(UNIX_TIMESTAMP(t.create_time),'%Y-%m-%d') as create_time","eu.netname as made_name",
                "sum(iid.curr_num) as curr_num","sum(iid.check_num) as check_num","count(iid.product_id) as pro_num"))
            ->join('invo_inventory_detail iid on t.id=iid.inventory_id')
            ->join('employ_user eu on t.create_user=eu.id')
            ->where($map)
            ->group('t.id')
            ->order('UNIX_TIMESTAMP(t.create_time) desc')
            ->getPagener();

        $date = array();
        foreach($list as $item){
            if(!in_array($item['create_time'],$date)){
                $date[$item['create_time'].'%'] = $item['create_time'];
            }
        }

        $this->view->assign(
            array(
                'pager'=>$pager,
                'date'=>$date,
            )
        )->display();
    }

    /**
     * 盘库
     */
    public function addAction() {
        $this->view->assign(
            array(
                'light_nav'=>$this->url('index'),
            )
        )->display();
    }

    /**
     * 添加盘库商品
     */
    public function pro_listAction(){
        $ids = $_POST['ids'];
        $comp_id = \Ivy::app()->user->comp_id;
        $dept_id = \Ivy::app()->user->dept_id;
        if(empty($ids)){
            $list = array();
        }else{
            $list = \ProductInfo::model()
                ->field(array("t.*","c.name as cname","c_t.name as fname","i_d.num as num","i_d.storage_num as storage_num",
                    "i_d.extra_num as extra_num","i_d.status as status","i_d.id as id","i_d.product_id as product_id"))
                ->join('pro_cate c on t.cate=c.id')
                ->join('pro_cate c_t on c_t.id=c.fid')
                ->join('invo_dept i_d on t.id=i_d.product_id')
                ->where("i_d.product_id in ({$ids}) and i_d.comp_id={$comp_id} and i_d.dept_id={$dept_id}")
                ->findAll();
        }

        echo $this->view->assign(
            array(
                'list'=>$list,
            )
        )->render();
    }

    /**
     * 盘库损益
     */
    public function editAction() {
        $id = $_GET['id'];
        $comp_id = \Ivy::app()->user->comp_id;
        $dept_id = \Ivy::app()->user->dept_id;
        $inventory_model = Inventory::model()->findByPk($id);
        if($inventory_model->status==0){
            $view = 'edit';
        }else{
            $view = 'view';
        }
        $list = \ProductInfo::model()
            ->field(array("t.*","c.name as cname","c_t.name as fname","i_d.*",))
            ->join('pro_cate c on t.cate=c.id')
            ->join('pro_cate c_t on c_t.id=c.fid')
            ->join('invo_inventory_detail i_d on t.id=i_d.product_id')
            ->where("i_d.inventory_id={$id}")
            ->findAll();

        $this->view->assign(
            array(
                'list'=>$list,
                'inventory_model'=>$inventory_model,
                'light_nav'=>$this->url('index'),
            )
        )->display($view);
    }

    /**
     * 保存盘库
     */
    public function saveAction(){

        try {
            $res = Inventory::model()->addNew($_POST);
            if($res){
                $this->ajaxReturn(200,'保存盘库成功！',$res);
            }
        } catch (CException $e) {
            $this->ajaxReturn(400,$e->getMessage());
        }

    }

    /**
     * 保存盘库损溢并更新库存
     */
    public function save_deptAction(){

        try {
            $re = InventoryDetail::model()->updateDetail($_POST['data']);
            $res = Loss::model()->addNew($_POST);
            if($res===true){
                $this->ajaxReturn(200,'保存盘库损溢成功！',$re);
            }else{
                $this->ajaxReturn(400,$res);
            }
        } catch (CException $e) {

        }

    }


}