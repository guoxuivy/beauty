<?php

/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   Administrator
 * @Last Modified time: 2016-04-05 17:35:02
 */
// 供应商管理
namespace admin;

use Ivy\core\CException;

class SupplierController extends \SController {
	
	/**
	 * 供应商列表
	 */
	public function indexAction() {
		$my = \CompanySupplier::model()->findAll('comp_id = '.\Ivy::app()->user->comp_id);
		
		$my_arr=array();
		if($my){
			foreach ($my as $key => $value) {
				$my_arr[]=$value['supp_id'];
			}
			$map['id'] = array('in',$my_arr);
		}

		$map['beauty_id'] = array('eq',\Ivy::app()->user->comp_id);
		$map['_logic'] = 'OR';
		
		$list= \SupplierInfo::model()->where($map)->findAll();
		//var_dump(\SupplierInfo::model());
		$this->view->assign(array(
				'list' => $list
		))->display();
	}

	/**
	 * 供应商数据申请
	 */
	public function appliAction() {
		$pager= CompanyRelation::getPager();
		$this->view->assign(array(
				'pager' => $pager
		))->display();
	}

	/**
	 * 供应商数据申请
	 */
	public function appliStatusAction() {
		if($this->getIsAjax()) {
			$supp_id = $_POST['supp_id'];
			$beauty_id = \Ivy::app()->user->comp_id;
			$model = CompanyRelation::model()->findByPk(array('comp_id'=>$supp_id,'beauty_id'=>$beauty_id));
			if($model->status==1){
				$model->status=0;
			}else{
				$model->status=1;
			}
			if ($model->save()){
				$this->ajaxReturn('200', 'ok！');
			}
			$this->ajaxReturn('400', 'error！');
		}
		throw new CException ('非法访问。');
	}
	
	/**
	 * 编辑添加供应商
	 *
	 * @throws CException
	 */
	public function addAction() {
		$_POST['beauty_id'] = \Ivy::app()->user->comp_id;
		$_POST['create_time'] = time();
		$_POST['phone'] = "0".$_POST['phone'];
		$res = \SupplierInfo::model ()->saveData( $_POST );
		if ($res)
			$this->redirect ( 'index' );
		throw new CException ( \SupplierInfo::model ()->popErr () );
	}
	
	/**
	 * 编辑显示
	 *
	 * @param string $id        	
	 */
	public function editAction($id = null) {
		$model = \SupplierInfo::model ()->findByPk ( $id );
		$phone = $model->phone;
		if($phone[0]=="0"){
			$model->phone = substr($phone,1);
		} 
		$html = $this->view->assign ( array (
				'model' => $model,
		) )->render ();
		$this->view->tagToken ( $html ); // render 方式需要强制增加token
		echo $html;
	}
	
	// 更改供应商状态
	public function changeAction($id = null) {
		$model = \SupplierInfo::model ()->findByPk ( $id );
		$model->updateUseStatus();
		if ($model){
			$this->ajaxReturn ( 200, "ok", array (
					"status" => $model->status 
			) );
		}else{
			$this->ajaxReturn ( 400, "修改失败", array (
					"status" => $model->status
			) );
		}
	}
	
	// 删除供应商
	public function deleteAction($id = null) {
		$model = \SupplierInfo::model ()->findByPk( $id );
		$model->delete();
		if ($model){
			$this->resetsupp($id);//商品批量解除
			$this->ajaxReturn ( 200, "ok", array (
					"status" => $model->status
			) );
		}else{
			$this->ajaxReturn ( 400, "修改失败", array (
					"status" => $model->status
			) );
		}
	}

	// 解除供应商
	public function delrelAction($id=null) {
		$res = \CompanySupplier::model()->deleteAll("comp_id =".\Ivy::app()->user->comp_id." AND supp_id={$id}");
		if($res){
			$this->resetsupp($id);//商品批量解除
			$this->ajaxReturn( 200, "ok");
		}else{
			$this->ajaxReturn( 400, "修改失败");
		}
	}

	//批量更新相关商品的供应商ID 为0
	protected function resetsupp($supp_id=null){
		$map['comp_id']=array('eq',\Ivy::app()->user->comp_id);
		$map['supp_id']=array('eq',$supp_id);
		$res = \ProductInfo::model()->cleanSupplier($map);
		$res = \ProjectInfo::model()->cleanSupplier($map);
	}

	/**
	 * [供应商搜索配对模糊搜索]
	 * @return [type] [description]
	 */
	public function searchAction() {
		$map['t.comp_name']	=  array('like',"%{$_GET['name']}%");
		//$map['t.beauty_id']	=  array('in',array('0',\Ivy::app()->user->comp_id));
		$map['t.status']	=  array('eq',1);
		$map['t.check_time']=array('gt',0);//审核过的

		$list = \SupplierInfo::model()
        	->field("t.comp_name,t.id,t.boss_name,t.phone")
        	->where($map)
        	->limit(3)
        	->findAll();
        if($list){
        	foreach ($list as &$value) {
        		if($value['phone']){
        			$p=$value['phone'];
	        		$p[3]='*';
	        		$p[4]='*';
	        		$p[5]='*';
	        		$p[6]='*';
	        		$value['phone']=$p;
        		}
        	}
			$this->ajaxReturn( 200, "", $list);
        }
		$this->ajaxReturn( 400, "");
	}


	//保存我的供应商
	public function relsaveAction($id = null) {
		$model = new \CompanySupplier;
		$model->comp_id = \Ivy::app()->user->comp_id;
		$model->supp_id = $id;
		
		if ($model->save()){
			$this->ajaxReturn ( 200, "ok" );
		}else{
			$this->ajaxReturn ( 400, "修改失败");
		}
	}

	
}