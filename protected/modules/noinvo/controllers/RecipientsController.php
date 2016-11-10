<?php
/**
 * @Author: K
 * @Date:   2015-11-5 11:58:47
 * @Last Modified by:   K
 * @Last Modified time: 2015-11-19 14:28:43
 */
namespace noinvo;
use Ivy\core\CException;
/**
 * 领用单(无进销存功能的)
 */
class RecipientsController extends \SController{
	// 列表
    public function indexAction() {
        $stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
        $etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
        if($_GET['md_ids']!=0){
            $md_ids=explode(',', $_GET['md_ids']);
            $map['t.dept_id'] = array('in',$md_ids);
        }
        $map['t.comp_id']	= 	array('eq',\Ivy::app()->user->comp_id);
        if ($this->is_store)
            $map['t.dept_id']  =   array('eq',\Ivy::app()->user->dept_id);
        $map['t.create_time']=array('BETWEEN',"$stime,$etime");
        $map['t.state']	=	array('egt',0);
        $search = isset($_GET['t_search'])?$_GET['t_search']:array();
        foreach ($search as $key => $value) {
            if(!empty($value)){
                if(strpos($value,'%')===false)
                    $map[$key]	= 	array('eq',$value);
                else
                    $map[$key]	= 	array('like',$value);
            }
        }
        $pager= Recipients::model()
            ->where($map)
            ->order('t.id desc')
            ->getPagener();
        //\Ivy::log(\ProductInfo::model()->lastSql);
        $this->view->assign(array('pager'=>$pager))->display();
    }
	//编辑或者添加出库
	public function editAction($id=0) {
		$id=(int)$id;
		$model=new Recipients;
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
            $res = Recipients::model()->saveData($_POST);
            if(!$res) $this->ajaxReturn(400,Recipients::model()->popErr());
		
			$this->ajaxReturn(200,"保存成功！",$res);
		}

	}
	public function viewAction($id){
        $id=$id;
        $model=Recipients::model()->findByPk($id);
        if(!$model) throw new CException("无此单号！");
        $map['t.order_id']=array('eq',$id);
        $list = RecipientsDetail::model()->field("t.*,pro.name,pro.code,pro.specs,pro.unit,pc.name as cname,tpc.name as tcname")
        ->join('product_info pro ON pro.id=t.product_id')
        ->join('pro_cate pc ON pc.id=pro.cate')
        ->join('pro_cate tpc ON tpc.id=pc.fid')
        ->findAll($map);
        $this->view->assign(array(
            'dept_name'=>\EmployDept::model()->findByPk($model->dept_id)->dept_name,
            'create_name'=>\EmployUser::model()->findByPk($model->create_user)->netname,
            'model'=>$model,
            'list'=>$list,
            'light_nav'=>$this->url('index'),//强制点亮导航
        ))->display();
    }
}