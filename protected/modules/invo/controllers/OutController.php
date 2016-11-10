<?php
/**
 * @Author: K
 * @Date:   2015-10-12 14:33:51
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-28 11:01:15
 */
namespace invo;
use Ivy\core\CException;
/**
 * 出库（领用,调拨出库,采购退货）
 */
class OutController extends \SController{
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
        $pager= OutDetail::model()
            ->field("t.*,ii.id as id,ii.out_time,ii.type as out_type, ii.status as out_status, it.from_id as from_dept,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")

            ->join('invo_out ii ON ii.id=t.order_id')
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
	//编辑或者添加出库
	public function editAction($id=0) {
		$id=(int)$id;
		$model=new Out;
		$model=$model->getEditModel($id);
			
		$this->view->assign(array(
			'model'=>$model,
			))->display();
	}
	//获取出库对象
	public function getobjAction($type){
		$arr=array();
		switch ($type) {
			case 1://调拨单
				$arr=\EmployDept::getList('status=1 AND type=2',false);
				break;
			case 4://采购
				$arr=\SupplierInfo::getList();
			default:
				# code...
				break;
		}
		$this->ajaxReturn('200', $arr);
	}
	//保存逻辑
	public function saveAction()
	{
		if ($_POST) {
			\Ivy::app()->user->checkToken();
            $res = Out::model()->saveData($_POST);
            if(!$res) $this->ajaxReturn(400,Out::model()->popErr());
		
			$this->ajaxReturn(200,"保存成功！",$res);
		}

	}
	public function viewAction($id){
        $id=$id;
        $model=Out::model()->findByPk($id);
        if(!$model) throw new CException("无此单号！");
        $map['t.order_id']=array('eq',$id);
        $list = OutDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
        ->join('product_info pro ON pro.id=t.product_id')
        ->join('pro_cate pc ON pc.id=pro.cate')
        ->join('pro_cate tpc ON tpc.id=pc.fid')
        ->findAll($map);
        $this->view->assign(array(
            'dept_name'=>\EmployDept::model()->findByPk($model->dept_id)->dept_name,
            'create_name'=>\EmployUser::model()->findByPk($model->create_user)->netname,
            'model'=>$model,
            'from_sn'=>Transfer::model()->findByPk($model->from_id)->sn,
            'list'=>$list,
            'light_nav'=>$this->url('index'),//强制点亮导航
        ))->display();
    }
}