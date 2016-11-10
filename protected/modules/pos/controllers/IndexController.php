<?php
namespace pos;
use Ivy\core\CException;
/**
 * pos 首页
 */
class IndexController extends BaseController
{

	/**
	 * 前台首页
	 * @param [type] $id [description]
	 */
	public function indexAction(){
		//购买、还款、充值
		$list1 = \OrderSale::model()
		->field(array("t.id,t.sn, substring(t.sn,3,8) as day ,1 as ptype ,t.cu_id,t.type,t.status,cu.name as cu_name"))
		->join('customer_info cu on cu.id=t.cu_id')
		->where(array(
			't.md_id'	=>	array('eq',\Ivy::app()->user->dept_id),
			't.status'	=>	array('eq',0)
		))
		->limit(100)
		->order('t.id desc')
		->findAll();
		$list1=$list1?$list1:array();
		//实操单
		$list2 = \PracticeOrder::model()
		->field(array("t.id,t.sn, substring(t.sn,3,8) as day ,0 as ptype ,t.cu_id,t.type,t.status,cu.name as cu_name"))
		->join('customer_info cu on cu.id=t.cu_id')
		->where(array(
			't.md_id'	=>	array('eq',\Ivy::app()->user->dept_id),
			't.status'	=>	array('eq',0)
		))
		->limit(100)
		->order('t.id desc')
		->findAll();
		$list2=$list2?$list2:array();
		$list = array_merge($list1,$list2);
		foreach ($list as $key => $value) {
			$day[$key]=$value['day'];
		}
		array_multisort($day,SORT_DESC,$list);	//合并排序
		//var_dump($list);die;
		$dept_id = \Ivy::app()->user->dept_id;
		$time = date('Ymd');

		$cu_count = \CustomerInfo::model()->where("store_id=".$dept_id)->count();
		$this->view->assign(array(
			'list'=>$list,
			'cu_count'=>$cu_count,
			'comp_name' => $this->company->comp_name,
			'dept_name' => \EmployDept::model()->findByPk($dept_id)->dept_name,
			'DDD'=>count(\ReBook::model()->getRebookList("t.status=1 and t.dept_id={$dept_id} and t.ymd={$time}")),
			'FWZ'=>count(\ReBook::model()->getRebookList("t.status=2 and t.dept_id={$dept_id} and t.ymd={$time}")),
			'YDD'=>count(\ReBook::model()->_getPractice($time,'0,1',$dept_id)),
		))->display();
	}
	/**
	 * 开单导航列表
	 * @param [type] $id [description]
	 */
	public function navAction(){
		$mem = \ConfigMembership::model()->findAll("status=1 AND comp_id=".$this->company->id);
		$this->view->assign(array(
			'mem_list' => $mem,
		))->display();
	}

	/**
	 * 获取客户当前等级
	 * @param [type] $id [description]
	 */
	public function culevelAction($cu_id){
		$res = \CustomerInfo::model()->field("c.id ,c.level_name as name")->join("config_membership c ON c.id=t.membership_id")->findByPk($cu_id);

		if($res)
			$this->ajaxReturn(200,"查询成功！",array("id"=>$res->id,"name"=>$res->name));
		$this->ajaxReturn(400,"查询等级失败！");
	}

	/**
	 * 获取客户升级会员等级
	 * @param [type] $id [description]
	 */
	public function levelAction($id,$level){
		$model = \CustomerInfo::model()->findByPk($id);
		$model->membership_id=$level;
		if(!$model->save())
			throw new CException('升级失败！');
		$this->redirect('customer/index/view',array('id'=>$id));

	}
	

}
