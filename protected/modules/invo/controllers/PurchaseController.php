<?php
/**
 * @author  guojh <[<guojh@howa.com.cn>]>
 * @Date 2016-03-07
 */
namespace invo;
use Ivy\core\CException;

/**
* 采购记录
*/
class PurchaseController extends \SController
{
    
    public function indexAction(){
        $map = array(
                "t.comp_id"=>array("eq",\Ivy::app()->user->comp_id),
                "t.status"=>array("gt",0),
                "r.status"=>array("gt",0),
            );
        $pager = Purchase::model()
                 ->field("t.supply_id,
                    t.create_time,
                    group_concat(concat(r.name,'(',s.num,')')) as product_info,
                    p.netname as user_name,
                    s.order_id as id,
                    t.note")
                 ->join("invo_purchase_detail s on t.id=s.order_id")
                 ->join("product_info r on r.id=s.product_id")
                 ->join("employ_user p on p.id = t.creat_user")
                 ->where($map)
                 ->group("s.order_id")
                 ->order("t.create_time desc")
                 // ->buildSelectSql();
                 // die($pager);
                 ->getPagener();
        $supply = \SupplierInfo::getList();
        $format = "Y-m-d";
        foreach ($pager['list'] as $key=>$value) {
            $pager['list'][$key]['date'] = date($format,$value['create_time']);
            $pager['list'][$key]['supp_name'] = isset($supply[$value['supply_id']])?$supply[$value['supply_id']]:"";
            $pager['list'][$key]['note'] = empty($value['note'])?"无":$value['note'];
            
        }

        $this->view->assign(array('pager'=>$pager))->display();
    }
    public function editAction(){
        if ($this->isPost) {
            \Ivy::app()->user->checkToken();
            // 写入订单
            $data = $_POST;
            $data['comp_id'] = \Ivy::app()->user->comp_id;
            $data['dept_id'] = \Ivy::app()->user->dept_id;
            $data['creat_user'] = \Ivy::app()->user->id;
            $data['create_time'] = time();
            $res= Purchase::model()->saveData($data);

            if($res){               
                $this->ajaxReturn(200,'ok');
            }else{
                $this->ajaxReturn(400,'err');
            }
            // throw new CException(Purchase::model()->popErr());
        }else{
            $this->view->assign()->display("add");
        }
    }

    public function viewAction($id){
        $id=$id;
        $model=Purchase::model()->findByPk($id);
        if(!$model) throw new CException("无此单号！");
        $map['t.order_id']=array('eq',$id);
        $list = PurchaseDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
        ->join('product_info pro ON pro.id=t.product_id')
        ->join('pro_cate pc ON pc.id=pro.cate')
        ->join('pro_cate tpc ON tpc.id=pc.fid')
        ->findAll($map);
        $supply_info = \SupplierInfo::getList();
        $this->view->assign(array(
            'dept_name'=>\EmployDept::model()->findByPk($model->dept_id)->dept_name,
            'create_name'=>\EmployUser::model()->findByPk($model->creat_user)->netname,
            'model'=>$model,
            'supply'=>$supply_info[$model->supply_id],
            'list'=>$list,
            'light_nav'=>$this->url('index'),//强制点亮导航
        ))->display();
    }
}