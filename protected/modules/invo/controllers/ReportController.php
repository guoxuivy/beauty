<?php
/**
 * @Author: K
 * @Date:   2015-10-26 17:30:07
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-03-23 17:32:20
 */
namespace invo;
use Ivy\core\CException;
/**
 * 库存报告
 */
class ReportController extends \SController{
	// 列表
    public function indexAction() {
        $begin=$_GET['begin'];
        $end=$_GET['end'];
        $md_id=$_GET['md_id']?$_GET['md_id']:\Ivy::app()->user->dept_id;
        $map['t.comp_id']	= 	array('eq',\Ivy::app()->user->comp_id);
        $map['t.dept_id']  =   array('eq',$md_id);
        $map['t.status']	=	array('egt',1);
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]	= 	array('eq',$value);
                else
                    $map[$key]	= 	array('like',$value);
            }
        }
        $stime=$begin?date('Y-m-d',strtotime($begin)):date('Y-m-d');
        $etime=$end?date('Y-m-d',strtotime($end)+24*3600):date('Y-m-d',strtotime('+1 days'));
        $pager= Dept::model()
            ->field("pro.id as pid,pc.name as cname,tpc.name as tcname,pro.name,pro.code,pro.specs,pro.unit,idg.num as s_num,idg2.num as e_num,tmp_out.out_num as out_num,tmp_in.in_num as in_num,tmp_loss.loss_num as loss_num")
            ->join('product_info pro ON pro.id=t.product_id')
            ->join('pro_cate pc ON pc.id=pro.cate')
            ->join('pro_cate tpc ON tpc.id=pc.fid')
            ->join("invo_dept_log idg ON idg.comp_id=t.comp_id AND idg.dept_id=t.dept_id AND idg.product_id=t.product_id AND idg.id=(SELECT id from invo_dept_log WHERE comp_id=idg.comp_id AND dept_id=idg.dept_id AND product_id=idg.product_id AND LEFT(create_time,10)<'{$stime}' ORDER BY `create_time` DESC LIMIT 1)")
            ->join("invo_dept_log idg2 ON idg2.comp_id=t.comp_id AND idg2.dept_id=t.dept_id AND idg2.product_id=t.product_id AND idg2.id=(SELECT id from invo_dept_log WHERE comp_id=idg2.comp_id AND dept_id=idg2.dept_id AND product_id=idg2.product_id AND LEFT(create_time,10)<'{$etime}' ORDER BY `create_time` DESC LIMIT 1)")
            
            ->join("(SELECT SUM(outd.out_num) as out_num ,outd.product_id AS product_id FROM invo_out `out` LEFT JOIN invo_out_detail outd ON outd.order_id=out.id WHERE  `out`.dept_id={$md_id} AND `out`.status=1  AND `out`.create_time BETWEEN '{$stime}' and '{$etime}' group BY outd.product_id) tmp_out ON tmp_out.product_id=t.product_id")
    
            ->join("(SELECT SUM(ind.in_num) as in_num ,ind.product_id AS product_id FROM invo_in `in` LEFT JOIN invo_in_detail ind ON ind.order_id=in.id WHERE  `in`.dept_id={$md_id} AND `in`.status=1  AND `in`.create_time BETWEEN '{$stime}' and '{$etime}' group BY ind.product_id) tmp_in ON tmp_in.product_id=t.product_id")

            ->join("(SELECT SUM(IF(loss.type<0,-lossd.num,lossd.num)) as loss_num ,lossd.product_id AS product_id FROM invo_gain_loss loss LEFT JOIN invo_gain_loss_detail lossd ON lossd.order_id=loss.id WHERE  loss.dept_id={$md_id} AND loss.status=1  AND loss.create_time BETWEEN '{$stime}' and '{$etime}' group BY lossd.product_id) tmp_loss ON tmp_loss.product_id=t.product_id")
            
            ->where($map)
            ->order('t.id desc')
            ->getPagener();
        //\Ivy::log(\ProductInfo::model()->lastSql);
        $this->view->assign(array('pager'=>$pager))->display();
    }

    /**
     * 库存报告--详细
     */
    public function viewAction(){
        $dept_id = $_GET['md_id'];
        $begin = strtotime($_GET['begin']);
        $end = strtotime($_GET['end']);
        $product_id = $_GET['pid'];
        if($begin==$end){
            $end = $end+24*3600-1;
        }
        $data = ReportModel::model()->getKCBG_XX($dept_id,$begin,$end,$product_id);
        $qckc = ReportModel::model()->getProductQCKC($product_id,$dept_id,$begin);
        $product_name = \ProductInfo::model()->findByPk($product_id)->name;
        $this->view->assign(
            array(
                'data'=>$data,
                'qckc'=>$qckc,
                'product_name'=>$product_name,
                'light_nav'=>$this->url('index'),//强制点亮导航
            )
        )->display();
    }


    // 出入口记录列表
    public function listAction() {
        $begin=$_GET['begin'];
        $type=$_GET['type'];
        $md_id=$_GET['md_id']?$_GET['md_id']:\Ivy::app()->user->dept_id;
        $map['t.comp_id']   =   array('eq',\Ivy::app()->user->comp_id);
        $map['t.dept_id']  =   array('eq',$md_id);
        $map['t.status']    =   array('egt',1);

        $field="t.id,t.sn,t.from_id,t.create_time,t.type,t.status,ed.dept_name";
        switch ($type) {
            case '2':
                $model=Out::model();
                $view_url='invo/out/view';
                break;
            case '3':
                $model=Loss::model();
                $view_url='invo/loss/view';
                $field="t.id,t.sn,0 as from_id,t.create_time,t.type,t.status,ed.dept_name";
                break;
            default:
                $model=In::model();
                $view_url='invo/in/view';

        }
        $pager= $model
            ->field($field)
            ->join('employ_dept ed ON ed.id=t.dept_id')
            ->where("DATE_FORMAT(t.`create_time`,'%Y%m')='".$begin."'")
            ->where($map)
            ->order('t.id desc')
            ->getPagener();
            //var_dump($pager);die;
        $this->view->assign(array('pager'=>$pager,'model'=>$model,'view_url'=>$view_url))->display();
    }
}