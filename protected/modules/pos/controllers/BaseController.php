<?php
namespace pos;
use Ivy\core\CException;
/**
 * 前台开单操作通用方法
 *
 * 可共用原子操作放于此类
 */
class BaseController extends \SController
{
	//布局文件
	public $layout='/layouts/main_m';

	/**
	 * [业务单据中客户信息的统一显示调用]
	 * @param  [type]  $id     [客户id]
	 * @param  boolean $render [是否字符串排版显示]
	 * @return [type]          [description]
	 */
	protected function getCuInfo($id=null,$view=false,$css=""){
		$info = \CustomerInfo::model()
		->field('t.*,m.level_name as membership_name,s.dept_name as store_name')
		->join("employ_dept as s on s.id=t.store_id")
		->join("config_membership as m on m.id=t.membership_id")
		->findByPk($id);
		//var_dump(\CustomerInfo::model()->lastSql);die;
		
		$capital_list=null;
		if($info){
			$capital_list=\CompanyCapital::model()
			->field('t.*,IFNULL(cc.balance,0.00) AS balance')
			->join("customer_capital as cc on t.id=cc.capital_id and cc.cu_id=".$id)
			->where("t.status=1 AND t.comp_id=".$info->company_id )
			->order("t.type")
			->findAll();
		}
		//var_dump(\CompanyCapital::model()->lastSql);
	
		$view=$view?"base/cuinfo_practice":"base/cuinfo";
		return $this->view->assign(array(
			"info"=>$info,
			"css"=>$css,
			"capital_list"=>$capital_list
		))->render($view);
	}
	/**
	 * [业务单据中业绩比例渲染 ]
	 * @param  [type]  $order_id [订单id]
	 * @param  boolean $render   [是否字符串排版显示]
	 * @return [type]            [description]
	 */
	protected function getEffect($order_id=null,$view=false){
		$config=\CompanyInfo::getConfig('sale_access');
		$position_ids=array();
		if ($config['manager']=='true') 
			$position_ids[]=5;
		if ($config['counselor']=='true') 
			$position_ids[]=6;
		if ($config['operator']=='true') 
			$position_ids[]=7;
		if ($config['reception']=='true') 
			$position_ids[]=8;
		$sqlw="";
		if ($position_ids) {
			if (count($position_ids)==1) {
				$sqlw="t.position_id=".$position_ids[0];
			}else{
				$position_ids=implode(',',$position_ids);
				$sqlw="t.position_id in ({$position_ids})";
			}
		}
		$employ_list=\EmployUser::model()
		->field('t.id,t.netname')
		->where(array('t.comp_id'=>\Ivy::app()->user->comp_id,'t.status'=>1, 't.dept_id'=>\Ivy::app()->user->dept_id))
		->findAll("$sqlw");
		//var_dump(\EmployUser::model()->lastSql);
		
		$effect_user=$effect_rate=array();
		if($order_id){
			$effect_list = \OrderEffectUser::model()->findAll("order_id=".$order_id);
			foreach ($effect_list as $value) {
				$effect_user[]=$value['user_id'];
				$effect_rate[]=$value['rate'];
			}
		}
		
		$view=$view?"base/effect_view":"base/effect";
		return $this->view->assign(array(
			"employ_list"=>$employ_list,
			"effect_user"=>$effect_user,
			"effect_rate"=>$effect_rate,
		))->render($view);
		
	}

	/**
	 * [业务单据中赠送部分渲染 ]
	 * @param  [type]  $order_id [订单id]
	 * @param  boolean $render   [是否字符串排版显示]
	 * @return [type]            [description]
	 */
	protected function getGift($order_id=null,$view=false){

		$capital_list=\CompanyCapital::model()
			->where("type=2 and status =1 and comp_id=".$this->company['id'] )
			->findAll();
		$view=$view?"base/gift_view":"base/gift";
		return $this->view->assign(array(
			"capital_list"=>$capital_list,
			"order_id"=>$order_id,
		))->render($view);
	}

}
