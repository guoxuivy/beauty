<?php
/**
 * @Author: K
 * @Date:   2015-10-12 14:33:51
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-28 11:26:39
 */
namespace invo;
use Ivy\core\CException;
/**
 * 损益
 */
class LossController extends \SController{
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
        $pager= LossDetail::model()
            ->field("t.*,ii.id as id,ii.create_time,ii.is_regular as ii_is_regular,ii.type as ii_type, ii.status as ii_status,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")

            ->join('invo_gain_loss ii ON ii.id=t.order_id')
            
            ->join('product_info pro ON pro.id=t.product_id')
            ->join('pro_cate pc ON pc.id=pro.cate')
            ->join('pro_cate tpc ON tpc.id=pc.fid')
            ->where($map)
            ->order('t.id desc')
            ->getPagener();
        //\Ivy::log(\ProductInfo::model()->lastSql);
        $this->view->assign(array('pager'=>$pager))->display();
    }
	//编辑或者添加
	public function editAction($id=0) {
		$id=(int)$id;
		$model=new Loss;
		$model=$model->getEditModel($id);
			
		$this->view->assign(array(
			'model'=>$model,
			))->display();
	}
	//保存逻辑
	public function saveAction()
	{
		if ($_POST) {
			\Ivy::app()->user->checkToken();
            $res = Loss::model()->saveData($_POST);
            if(!$res) $this->ajaxReturn(400,Loss::model()->popErr());
		
			$this->ajaxReturn(200,"保存成功！",$res);
		}

	}
    public function viewAction($id){
        $id=$id;
        $model=Loss::model()->findByPk($id);
        if(!$model) throw new CException("无此单号！");
        $map['t.order_id']=array('eq',$id);
        $list = LossDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
        ->join('product_info pro ON pro.id=t.product_id')
        ->join('pro_cate pc ON pc.id=pro.cate')
        ->join('pro_cate tpc ON tpc.id=pc.fid')
        ->findAll($map);
        $this->view->assign(array(
            'dept_name'=>\EmployDept::model()->findByPk($model->dept_id)->dept_name,
            'create_name'=>\EmployUser::model()->findByPk($model->create_user)->netname,
            'model'=>$model,
            'from_sn'=>$model->sn,
            'list'=>$list,
            'light_nav'=>$this->url('index'),//强制点亮导航
        ))->display();
    }
}