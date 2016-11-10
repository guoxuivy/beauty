<?php

namespace invo;
use Ivy\core\CException;
/**
 * invo 仓库管理
 */
class IndexController extends \SController{

    /**
     * 列表
     */
    public function indexAction() {
        $map['t.comp_id']	= 	array('eq',$this->company['id']);
        $map['t.status']	=	array('egt',0);
        $map['i_d.status']	=	array('neq',-1);
        $map['i_d.dept_id']	=	array('eq',\Ivy::app()->user->dept_id);
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]	= 	array('eq',$value);
                else
                    $map[$key]	= 	array('like',$value);
            }
        }
        $list = \ProductInfo::model()->findAll("comp_id={$this->company['id']} and status>0");
        $dept_id = \Ivy::app()->user->dept_id;
        $invo_list = \ProductInfo::model()
            ->field(array("t.*"))
            ->join('invo_dept i_d on t.id=i_d.product_id')
            ->where("t.comp_id={$this->company['id']} and i_d.status=-2 and i_d.dept_id={$dept_id}")
            ->findAll();
        $pager= \ProductInfo::model()
            ->field(array("t.*","c.name as cname","c_t.name as fname","i_d.num as num","i_d.storage_num as storage_num",
                "i_d.extra_num as extra_num","i_d.status as status","i_d.id as id"))
            ->join('pro_cate c on t.cate=c.id')
            ->join('pro_cate c_t on c_t.id=c.fid')
            ->join('invo_dept i_d on t.id=i_d.product_id')
            ->where($map)
            ->group('t.id')
            ->getPagener();

        $this->view->assign(
            array(
                'pager'=>$pager,
                'list'=>$list,
                'invo_list'=>$invo_list,
            )
        )->display();
    }

    /**
     * 期初商品库存列表
     */
    public function begin_proAction(){
        $page = $_POST['page'];
        $search = $_POST['search'];
        $where = "";
        $per_page = 10;
        if(!empty($search)){
            $where = " and (t.name like '%{$search}%' or t.code like '%{$search}%')";
        }
        $list = \ProductInfo::model()
            ->field(array("t.*","c.name as cname","c_t.name as fname"))
            ->join('pro_cate c on t.cate=c.id')
            ->join('pro_cate c_t on c_t.id=c.fid')
            ->where("t.comp_id={$this->company['id']} and t.status>0".$where)
            ->limit(($page-1)*$per_page,$per_page)
            ->findAll();
        $_list = \ProductInfo::model()
            ->field(array("t.*","c.name as cname","c_t.name as fname"))
            ->join('pro_cate c on t.cate=c.id')
            ->join('pro_cate c_t on c_t.id=c.fid')
            ->where("t.comp_id={$this->company['id']} and t.status>0".$where)
            ->findAll();
        $dept_id = \Ivy::app()->user->dept_id;
        $invo_list = \ProductInfo::model()
            ->field(array("t.*","i_d.num as num","i_d.extra_num as extra_num"))
            ->join('invo_dept i_d on t.id=i_d.product_id')
            ->where("t.comp_id={$this->company['id']} and t.status>0 and i_d.status<>-1 and i_d.dept_id={$dept_id}")
            ->findAll();
        $invo_num = array();
        foreach($invo_list as $_invo_list){
            $invo_num[$_invo_list['id']] = $_invo_list;
        }
        echo $this->view->assign(
            array(
                'list'=>$list,
                '_list'=>$_list,
                'page'=>$page,
                'search'=>$search,
                'invo_num'=>$invo_num,
                'per_page'=>$per_page,
            )
        )->render();
    }

    /**
     * 写入起初商品库存
     * @throws CException
     */
    public function add_proAction(){

        try {
            $param = $_POST['param'];
            $comp_id = \Ivy::app()->user->comp_id;
            $dept_id = \Ivy::app()->user->dept_id;
            foreach(explode(',',$param) as $_param){
                $data = explode('|',$_param);
                $invo_pro = Dept::model()->find("product_id={$data[0]} and comp_id={$comp_id} and dept_id={$dept_id}");
                if($data[1]!=''){
                    if(empty($invo_pro)){
                        $model = new Dept;
                    }else{
                        $model = Dept::model()->findByPk($invo_pro['id']);
                    }
                    $model->comp_id = \Ivy::app()->user->comp_id;
                    $model->dept_id = \Ivy::app()->user->dept_id;
                    $model->product_id = $data[0];
                    $model->num = $data[1];
                    $model->extra_num = $data[2];
                    $model->last_time = date('Y-m-d H:i:s');
                    $model->status = -2;
                    if(!$model->save()) throw new CException("商品库存初始化失败！");
                }
            }
            $this->ajaxReturn(200,"ok");
        } catch (CException $e) {
            $this->ajaxReturn(400,$e->getMessage());
        }
    }

    /**
     * 期初商品开帐
     */
    public function save_proAction(){
        Dept::model()->addInventory();
        if($flat = Dept::model()->popErr()){
            $this->ajaxReturn(400,$flat);
        }else{
            $this->ajaxReturn(200,"ok");
        }
    }

    /**
     * 停用/启用
     * @param null $id
     */
    public function changeAction($id=null){
        $model = Dept::model ()->findByPk ( $id );
        $model->status = $model->status==0?1:0;
        $model->last_time = date('Y-m-d H:i:s');
        if ($model->save()){
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
     * 删除
     */
    public function deleteAction() {
        try {
            $model = Dept::model()->findByPk($_GET['id']);
            $model->status = -1;
            $model->last_time = date('Y-m-d H:i:s');
            $model->save();
            $this->ajaxReturn(200,"ok");
        } catch (CException $e) {
            $this->ajaxReturn(400,$e->getMessage());
        }
    }



}