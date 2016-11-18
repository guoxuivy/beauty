<?php
namespace admin;
use Ivy\core\CException;
class ProductController extends \SController
{
	public function uploadAction() {
		$fn=uniqid("product_").'.xls';
		$savePath = __ROOT__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;
	    file_put_contents(
	        $savePath  . $fn,
	        file_get_contents('php://input')
	    );
	    $filename= $savePath.$fn;
	    if (file_exists($filename)) {
	    	$result = \ProductInfo::insertFile($filename);
			@unlink($filename);
			if($result) {
				$this->ajaxReturn('200', '上传成功', $result);
			}
	    }
	    $this->ajaxReturn('400', '上传失败');
	}
	
	// 列表
	public function indexAction() {
		$map['t.comp_id']	= 	array('eq',$this->company['id']);
		$map['t.status']	=	array('egt',0);
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \ProductInfo::model()
		->field(array("t.*","c.name as cname","c_t.name as fname"))
		->join('pro_cate c on t.cate=c.id')
		->join('pro_cate c_t on c_t.id=c.fid')
		->where($map)
		->group('t.id')
		->getPagener();
		$this->view->assign(array('pager'=>$pager))->display();
	}
	// 添加
	public function addAction() {
		\Ivy::app()->user->checkToken();
		$res= \ProductInfo::model()->saveData($_POST);
		if($res)
			die("<script language=JavaScript> history.go(-1);location.reload();</script>") ;
			//$this->redirect('index');
		throw new CException(\ProductInfo::model()->popErr());
	}

	//编辑 ajax 方式需要手动添加token
	public function editAction($id=null){
		if(!$this->isAjax)
			throw new CException('非正常访问');
		$id=(int)$id;
		$model = \ProductInfo::model()->findByPk($id);
		if($model->comp_id!=$this->company['id']) {
			throw new CException('无效参数');
		}
		$fid=\ProCate::model()->findByPk($model->cate)->fid;
		$html=$this->view->assign(array(
			'model'=>$model,
			'fid'=>$fid,
			'cates'=>\ProCate::model()->findAll('fid='.(int)$fid)
		))->render();
		$this->view->tagToken($html);//render 方式需要强制增加token
		echo $html;
	}
	
	//修改商品状态 
	public function changeAction($id=null){
		$model = \ProductInfo::model ()->findByPk ( $id );
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
	
	/**
	 * 产品分类管理
	 * @return [type] [description]
	 */
	public function cateAction() {
		$topCate = \ProCate::getProductTopCate();
		$childCate=array();
		foreach ($topCate as $key => $value) {
			$childCate[$key]=\ProCate::model()->field('id,name')->findAll('fid='.$key.' and comp_id='.$this->company['id'].' and level=2 and type=2 and status=1');
		}
		$this->view->assign(array(
			'topCate'=>$topCate,
			'childCate'=>$childCate,
		))->display();
	}

	public function addCateAction() {
		$data=$_POST;
		if($_POST['id'])
			$_POST['fid']=\ProCate::model()->findByPk($_POST['id'])->fid;
		if($res= \ProCate::model()->where("name='{$_POST['name']}' and fid={$_POST['fid']} and comp_id=".$this->company['id'])->find('status=1'))
			$this->ajaxReturn(400,"已存在该分类名");
		$data['level']=2;
		$data['type']=2;
		$data['comp_id']=$this->company['id'];
		$data=\ProCate::model()->saveData($data);
		$proCate = \ProCate::model()->findByPk($_POST['id']);
		$childCate =\ProCate::model()->field('id,name')->findAll('fid='.$proCate->fid.' and comp_id='.$this->company['id'].' and level=2 and type=2 and status=1');
		$this->ajaxReturn(200,"ok",array('id'=>$data['id'],'name'=>$data['name'],'childCount'=>count($childCate)));
	}

	public function delCateAction() {
		try {
			\ProCate::model()->findByPk($_POST['id'])->updateStatus();
		} catch (CException $e) {
			$this->ajaxReturn(400,$e->getMessage());
		}
		
		$this->ajaxReturn(200,"ok");
	}

	//分类级联 json
	public function childCateAction() {
		$show_del=(int)$_GET['show_del'];
		if($show_del==1)
			$sqlw="";
		else
			$sqlw=" and status=1";
		$list=\ProCate::model()->field('id,name,status')->findAll('fid='.(int)$_GET['fid'].' and comp_id='.$this->company['id'].' and level=2 and type=2 '.$sqlw);
		if($list)
			foreach ($list as &$value) {
				if($value['status']<0)
					$value['name']=$value['name'].'(删除)';
			}
		if($list)
			$this->ajaxReturn(200,'ok',$list);
		$this->ajaxReturn(400,'error');
	}

	//分类级联 json 分类下的商品
	public function cateProdAction() {
		$show_del=(int)$_GET['show_del'];
		if($show_del==1)
			$sqlw="";
		else
			$sqlw=" and t.status=1";
		$company_info=\Ivy::app()->user->getState('company_info');
		$comp_id=$company_info['id'];
		$dept_id=\Ivy::app()->user->dept_id;

		
		if( ($company_info['has_invo']!='Y') || !$this->is_store){//报表中（都有$show_del）或者无进销存功能的才显示所有商品
			$list=\ProductInfo::model()
			->field('t.*,tc.name AS top_name,c.name AS c_name')
			->join('pro_cate c on t.cate=c.id')
			->join('pro_cate tc on c.fid=tc.id')
			->findAll('t.cate='.(int)$_GET['fid'].' and t.comp_id='.$this->company['id'].$sqlw);
		}else{
			$dept_sql = $this->is_store?" AND ind.dept_id={$dept_id} ":"";
			$list=\ProductInfo::model()
					->field('t.*,ind.num max_num,tc.name AS top_name,c.name AS c_name')
					->join('pro_cate c on t.cate=c.id')
					->join('pro_cate tc on c.fid=tc.id')
					->join("invo_dept ind on ind.comp_id={$comp_id} {$dept_sql} AND t.id=ind.product_id AND ind.status=1",'right join')
					->group('t.id')
					->findAll('t.cate='.(int)$_GET['fid'].' and t.comp_id='.$this->company['id'].$sqlw);
		}
		if($list)
			foreach ($list as $key=>$value) {
				$list[$key]['name']=$value['name'].'&nbsp ('.$value['code'].')';
				if($value['status']<0)
					$list[$key]['name']=$value['name'].'(删除)';
				if($value['status']==0)
					$list[$key]['name']=$value['name'].'(停售)';
			}
		if($list)
			$this->ajaxReturn(200,'ok',$list);
		$this->ajaxReturn(400,'error');
	}
	
	//删除产品
	public function deleteAction($id=null) {
		$model = \ProductInfo::model ()->findByPk ( $id );
		$model->delete ();
		
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
	
}