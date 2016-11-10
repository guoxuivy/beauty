<?php
namespace invo;
use Ivy\core\CException;
/**
 * in 仓库入库
 */
class InController extends \SController{

    // 列表
    public function indexAction() {
        $map['ii.comp_id']	= 	array('eq',\Ivy::app()->user->comp_id);
        $map['ii.dept_id']  =   array('eq',\Ivy::app()->user->dept_id);
        $map['ii.status']	=	array('egt',0);
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]	= 	array('eq',$value);
                else
                    $map[$key]	= 	array('like',$value);
            }
        }
        $pager= InDetail::model()
            ->field("t.*,ii.id as id,ii.in_time,ii.type as in_type, ii.status as in_status, it.from_id as from_dept,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")

            ->join('invo_in ii ON ii.id=t.order_id')
            ->join('invo_transfer it ON it.id=ii.from_id')
            
            ->join('product_info pro ON pro.id=t.product_id')
            ->join('pro_cate pc ON pc.id=pro.cate')
            ->join('pro_cate tpc ON tpc.id=pc.fid')
            ->where($map)
            ->order('t.id desc')
            ->getPagener();
        //\Ivy::log(\ProductInfo::model()->lastSql);
        $this->view->assign(array('pager'=>$pager))->display();
    }

    //新建入库单(有调拨单类型 调拨入库1、分仓退货2)
    public function addAction($type=null){
        if($this->isPost){
            \Ivy::app()->user->checkToken();
            $res = In::model()->saveData($_POST);
            if($res){
                //推送消息
                $TFmodel = Transfer::model()->findByPk($_POST['from_id']);//出库时调拨单
                $map['dept_id']=array('eq',$TFmodel->from_id);
                $map['position_id']=array('eq',8); //门店前台
                $map['status']=array('eq',1);
                $list = \EmployUser::model()->field('id')->where($map)->findAll();
                $to_uids = array();
                foreach ($list as $value) {
                    $to_uids[]=$value['id'];
                }
                $sn_str = $TFmodel->sn;
                $dept_str = \EmployDept::model()->findByPk($TFmodel->to_id)->dept_name;
                $arr=array(
                    'to_uids'=>$to_uids,
                    'title'=>'【收货啦】',
                    'content'=>'调拨单<a href="'.\Ivy::app()->_route->url('invo/out/index').'">'.$sn_str.'</a>已与'.date("Y-m-d H:i",time()).' 在【'.$dept_str.'店】确认入库。（点击调拨单号， 可进入看调拨单据详情）',
                );
                \SmsMessage::send($arr);

                $this->redirect('index');
            }
            throw new CException(In::model()->popErr());
        }else{
            //var_dump(\Ivy::app()->user);
            $this->view->assign(array(
                'dept_name'=>\EmployDept::model()->findByPk(\Ivy::app()->user->dept_id)->dept_name,
                'light_nav'=>$this->url('index'),//强制点亮导航
                'type'=>$type,//默认入库类型
                'translist'=>$this->translist($type),//调拨单列表
            ))->display();
        }
    }
    //新建入库单(无调拨单类型 采购入库4、购买退回5)
    public function addnAction($type=4){
        if($this->isPost){
            \Ivy::app()->user->checkToken();
            $res = In::model()->savenData($_POST);
            if($res)
                $this->redirect('index');
            throw new CException(In::model()->popErr());
        }else{
            //var_dump(\Ivy::app()->user);
            $this->view->assign(array(
                'dept_name'=>\EmployDept::model()->findByPk(\Ivy::app()->user->dept_id)->dept_name,
                'light_nav'=>$this->url('index'),//强制点亮导航
                'type'=>$type,//默认入库类型
            ))->display();
        }
    }

    /**
     * [入库单查看]
     * @param  [type] $id 入库单号
     * @return [type]     [description]
     */
    public function viewAction($id){
        $id=$id;
        $in_model=In::model()->findByPk($id);
        if(!$in_model) throw new CException("无此单号！");
        $map['t.order_id']=array('eq',$id);
        $list = InDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
        ->join('product_info pro ON pro.id=t.product_id')
        ->join('pro_cate pc ON pc.id=pro.cate')
        ->join('pro_cate tpc ON tpc.id=pc.fid')
        ->findAll($map);
        $this->view->assign(array(
            'dept_name'=>\EmployDept::model()->findByPk($in_model->dept_id)->dept_name,
            'create_name'=>\EmployUser::model()->findByPk($in_model->create_user)->netname,
            'in_model'=>$in_model,//默认入库类型
            'from_sn'=>Transfer::model()->findByPk($in_model->from_id)->sn,
            'list'=>$list,
            'light_nav'=>$this->url('index'),//强制点亮导航
        ))->display();
    }
    /**
     * 获取调拨单列表
     * @return [type] [description]
     */
    private function translist($type){
        if($type){
            $map['to_id']=array('eq',\Ivy::app()->user->dept_id);
            $map['type']=array('eq',$type);
            $map['state']=array('in','1,3');
            $list = Transfer::model()->field("id,sn")->findAll($map);
            return $list;
        }
    }
    /**
     * 获取调拨单详情
     * @return [type] [description]
     */
    public function tranAction(){
        if($this->isAjax){
            $res = Transfer::model()->findByPk($_GET['id']);
            if($res)
                $this->ajaxReturn(200,'ok',$res->attributes);
        }
    }

    /**
     * 拒绝调拨单 
     * 原调拨单 state 改为 9
     * 复制调拨单数据 返回到原仓库
     * @return [type] [description]
     */
    public function refuseAction(){
        if($this->isAjax){
            $res = Transfer::model()->refuse($_GET['id']);
            if($res){
                //推送消息
                $TFmodel = Transfer::model()->findByPk($_GET['id']); //原调拨单
                $map['dept_id']=array('eq',$TFmodel->from_id);
                $map['position_id']=array('eq',8); //门店前台
                $map['status']=array('eq',1);
                $list = \EmployUser::model()->field('id')->where($map)->findAll();
                $to_uids = array();
                foreach ($list as $value) {
                    $to_uids[]=$value['id'];
                }
                $sn_str = $TFmodel->sn;
                $dept_str = \EmployDept::model()->findByPk($TFmodel->to_id)->dept_name;
                $arr=array(
                    'to_uids'=>$to_uids,
                    'title'=>'【被拒绝入库】',
                    'content'=>'调拨单<a href="'.\Ivy::app()->_route->url('invo/out/index').'">'.$sn_str.'</a>已与'.date("Y-m-d H:i",time()).' 在【'.$dept_str.'店】被拒绝入库。（点击调拨单号， 可进入看调拨单据详情）',
                );
                \SmsMessage::send($arr);
                $this->ajaxReturn(200,'拒绝入库成功！');
            }
        }
    }

    /**
     * 获取调拨单商品详细
     * @return [type] [description]
     */
    public function inlistAction(){
        if($this->isAjax){
            $map['t.order_id']=array('eq',$_GET['id']);
            $list = TransferDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
            ->join('product_info pro ON pro.id=t.product_id')
            ->join('pro_cate pc ON pc.id=pro.cate')
            ->join('pro_cate tpc ON tpc.id=pc.fid')
            ->findAll($map);
            if($list)
                $this->ajaxReturn(200,'ok',$list);
        }
    }

}