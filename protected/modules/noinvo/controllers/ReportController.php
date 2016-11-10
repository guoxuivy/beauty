<?php
/**
 * @Author: K
 * @Date:   2015-10-26 17:30:07
 * @Last Modified by:   K
 * @Last Modified time: 2015-11-19 15:54:55
 */
namespace noinvo;
use Ivy\core\CException;
/**
 * 库存报告
 */
class ReportController extends \SController{
	/**
     * 出库记录报表
     */
    public function CKJLAction()
    {
        if(isset($_GET['md_id'])){
            if($_GET['md_id']!=0){
                $md_ids=explode(',', $_GET['md_id']);
                $map['t.dept_id'] = $map2['t.dept_id'] =   array('in',$md_ids);
            }

            $stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
            $etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
            //领用出库
            $map['t.comp_id'] =   array('eq',\Ivy::app()->user->comp_id);
            $map['t.state']   =   array('eq',1);
            $map['t.create_time'] = array(array('gt',$stime),array('lt',$etime));

            $list1= Recipients::model()
                ->field("t.*,u.netname")
                ->join("employ_user as u ON u.id=t.create_user")
                ->where($map)->limit(200)->order('t.id desc')->findAll();

            //寄存领取
            $map2['d.comp_id'] =   array('eq',\Ivy::app()->user->comp_id);
            $map2['t.status']   =   array('eq',1);
            $map2['t.receive_time'] = array(array('gt',$stime),array('lt',$etime));
            $list2= \CustomerProdReceive::model()
                ->field("t.*,c.name as cu_name,u.netname")
                ->join("employ_dept as d ON d.id=t.dept_id")
                ->join("employ_user as u ON u.id=t.create_user")
                ->join("customer_info as c ON c.id=t.cu_id")
                ->where($map2)->limit(200)->order('t.id desc')->findAll();
            
        }

        
        $this->view->assign(array(
            'list1'=>$list1,
            'list2'=>$list2,
            ))->display();
    }


    /**
     * 消耗报表
     */
    public function XHBBAction()
    {
        $md_id=$_GET['md_id'];
        $sqlw1=" AND cpr.dept_id={$md_id}";
        $sqlw2=" AND nir.dept_id={$md_id}";
        if(empty($md_ids))
            $md_ids=0;
        if (strpos($md_ids,',')) {
            $md_ids=explode(',', $md_ids);
        }
        $stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
        $etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
        $comp_id=\Ivy::app()->user->comp_id;
        if (strpos($md_id,',')) {
            $md_id=explode(',', $md_id);
        }
        if(is_array($md_id)) {
            $md_id = implode(",",$md_id);
            $sqlw1=" AND cpr.dept_id in ({$md_id})";
            $sqlw2=" AND nir.dept_id in ({$md_id})";
            $md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
            $sqlw2=$sqlw1="";

        }
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]  =   array('eq',$value);
                else
                    $map[$key]  =   array('like',$value);
            }
        }
        $pager=\ProductInfo::model()
                ->field('t.id,tpc.name as tcname,pc.name as cname,t.name,t.code,t.specs,t.unit,(SUM(IF(cpr.id>0,cprd.num,0))+ sum(IF(nir.id>0,nird.num,0))) AS num')
                ->join('pro_cate pc ON pc.id=t.cate')
                ->join('pro_cate tpc ON tpc.id=pc.fid')
                ->join('customer_prod_receive_detail cprd ON t.id=cprd.product_id')
                ->join("customer_prod_receive cpr ON cprd.order_id=cpr.id ")
                ->join('no_invo_recipients_detail nird ON nird.product_id=t.id')
                ->join("no_invo_recipients nir ON nird.order_id=nir.id ")
                ->where("t.comp_id={$comp_id} AND t.status=1 AND ((cpr.re_status='1' AND cpr.status=1 AND cpr.receive_time BETWEEN {$stime} AND {$etime} {$sqlw1}) OR (nir.comp_id={$comp_id} AND nir.state=1  AND nir.create_time BETWEEN {$stime} AND {$etime} {$sqlw2}))")
                ->where($map)
                ->group('t.id')
                ->order('num desc')
                ->getPagener();
        $this->view->assign(array('pager'=>$pager))->display();
    }
	/**
	 * 客户寄存汇总表
	 */
	public function JCHZAction()
	{
		$md_id=$_GET['md_id'];
        $sqlw1=" AND cpr.dept_id={$md_id}";
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
		$etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
		$comp_id=\Ivy::app()->user->comp_id;
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw1=" AND cpr.dept_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw1="";

        }
        
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]  =   array('eq',$value);
                else
                    $map[$key]  =   array('like',$value);
            }
        }

		$pager=\ProductInfo::model()
				->field('t.id,tpc.name as tcname,pc.name as cname,t.name,t.code,t.specs,t.unit,SUM(IF(cpr.id>0,cprd.remain_num,0)) AS num')
				->join('pro_cate pc ON pc.id=t.cate')
            	->join('pro_cate tpc ON tpc.id=pc.fid')
            	->join('customer_prod_storage_detail cprd ON t.id=cprd.product_id')
            	->join("customer_prod_storage cpr ON cprd.storage_id=cpr.id ")
                ->where("t.comp_id={$comp_id} AND t.status=1 AND cpr.status=1 {$sqlw1}")
                ->where($map)
            	->group('t.id')
            	->order('num desc')
            	->getPagener();
		$this->view->assign(array('pager'=>$pager))->display();
	}


    /**
     * 客户寄存/领取 报表
     */
    public function JCGLAction()
    {
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]  =   array('eq',$value);
                else
                    $map[$key]  =   array('like',$value);
            }
        }

        $map['ci.company_id']=array('eq',\Ivy::app()->user->comp_id);
        $map['ci.store_id']=array('eq',\Ivy::app()->user->dept_id);
        $pager=\CustomerProdStorageDetail::model()
                ->field('ci.cardid, ci.id as cu_id,ci.name, SUM(t.remain_num) as num,ci.last_time,gw.netname as gw_name,mrs.netname as mrs_name,ed.dept_name')
                ->join('customer_info ci ON ci.id=t.cu_id')
                ->join('employ_user gw ON gw.id=ci.counselor_id')
                ->join('employ_user mrs ON mrs.id=ci.operator_id')
                ->join('employ_dept ed ON ed.id=ci.store_id')
                ->where($map)
                ->group('t.cu_id')
                ->getPagener();

        $this->view->assign(array('pager'=>$pager))->display();
    }

    /**
     * 客户寄存/领取 详细报表 客户寄存列表
     */
    public function viewAction()
    {
        if(!$_GET['cu_id']){
            throw new CException("无效客户！");
        }
        $map['t.cu_id']=array('eq',$_GET['cu_id']);
        $pager=\CustomerProdStorageDetail::model()
                ->field('t.*,cps.type as buy_type,pi.name,os.sn,os.id as order_id, ceil(osd.pay_price/osd.num) as dj')
                ->join('product_info pi ON pi.id=t.product_id')
                ->join('order_sale_detail osd ON osd.id=t.detail_id')
                ->join('order_sale os ON os.id=osd.order_id')

                ->join('customer_prod_storage cps ON cps.id=t.storage_id')
                ->where($map)
                ->getPagener();
        $this->view->assign(array(
            "light_nav"=>$this->url('JCGL'),     //需要点亮的导航
            'pager'=>$pager,
            'cuname'=>\CustomerInfo::model()->findByPk($_GET['cu_id'])->name,
        ))->display();
    }

    /**
     * 查看客户商品领取详细
     * storage_detail_id  寄存详单id
     */
    public function listAction()
    {
        if(!$_GET['id']){
            throw new CException("无效寄存！");
        }
        $map['t.storage_detail_id']=array('eq',$_GET['id']);
        $list=\CustomerProdReceiveDetail::model()
                ->field(' FROM_UNIXTIME( cpr.receive_time, "%Y-%m-%d" ) as receive_time ,t.num,t.after_num,cpr.id')
                ->join('customer_prod_receive cpr ON cpr.id=t.order_id')
                ->where($map)
                ->findAll();
        if($list)
            $this->ajaxReturn(200,'ok',$list);
        $this->ajaxReturn(400,'暂无记录');
    }
    

    
}