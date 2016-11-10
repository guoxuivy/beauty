<?php
/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-10-19 17:23:49
 */
//项目管理
namespace admin;
use Ivy\core\CException;
class ProjectController extends \SController
{
	public function uploadAction() {
		$fn=uniqid("project_").'.xls';
		$savePath = __ROOT__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;
	    file_put_contents(
	        $savePath  . $fn,
	        file_get_contents('php://input')
	    );
	    $filename= $savePath.$fn;
	    if (file_exists($filename)) {
	    	$result = \ProjectInfo::insertFile($filename);
			@unlink($filename);
			if($result) {
				$this->ajaxReturn('200', '上传成功', $result);
			}
	    }
	    $this->ajaxReturn('400', '上传失败');
	}
	
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
		$pager= \ProjectInfo::model()
		->field(array("t.*","c.name as cname","c_t.name as fname"," IFNULL(f.id,0) as formula "))
		->join('project_formula f on f.project_id=t.id')
		->join('pro_cate c on t.cate=c.id')
		->join('pro_cate c_t on c_t.id=c.fid')
		->where($map)
		->order('formula')
		->group('t.id')
		->getPagener();
		//\Ivy::log(\ProjectInfo::model()->lastSql);
		
		$no_num = \ProjectFormula::getNoNum();
		
		$this->view->assign(array('pager'=>$pager,'no_num'=>$no_num))->display();
	}


	// 添加项目
	public function addAction() {
		\Ivy::app()->user->checkToken();
		$res= \ProjectInfo::model()->saveData($_POST);
		if($res)
			$this->redirect('index');
		throw new CException(\ProjectInfo::model()->popErr());
	}


	//编辑项目
	public function editAction($id=null){
		if($this->isPost){
			\Ivy::app()->user->checkToken();
			$res = \ProjectInfo::model()->saveData($_POST);
			if($res)
				$this->redirect('index');
			throw new CException(\ProjectInfo::model()->popErr());
		}else{
			$model = \ProjectInfo::model()->getEditModel($id);
			$this->view->assign(array(
				'model'=>$model,
				'light_nav'=>$this->url('admin/project/index'),//强制点亮导航
			))->display();
		}

	}

	//删除项目
	public function deleteAction($id=null) {
		$model = \ProjectInfo::model()->findByPk( $id );
		$model->delete();
		
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
	
	//修改项目状态 
	public function changeAction($id=null){
		$model = \ProjectInfo::model()->findByPk($id );
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
	 * 项目分类管理
	 * @return [type] [description]
	 */
	public function cateAction() {
		$childCate=$topCate=array();
		$topCate=\ProCate::model()->getProjectTopCate();
		foreach ($topCate as $key => $value) {
			$childCate[$key]=\ProCate::model()->field('id,name')->where(array(
				'comp_id'=>$this->company['id'],
				'fid'=>$key,
				'type'=>1,
				'status'=>1,
				'level'=>2,
			))->findAll();
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
		$data['level']=$_POST['fid']==0?1:2;
		$data['type']=1;
		$data['comp_id']=$this->company['id'];
		$data=\ProCate::model()->saveData($data);
		$parent_id = \ProCate::model()->findByPk($data['id'])->fid;
		$childCate=\ProCate::model()->field('id,name')->where(array(
			'comp_id'=>$this->company['id'],
			'fid'=>$parent_id,
			'type'=>1,
			'status'=>1,
			'level'=>2,
		))->findAll();
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
		if($show_del)
			$sqlw="";
		else
			$sqlw=" and status=1";
		$list=\ProCate::model()->field('id,name,status')->findAll('fid='.(int)$_GET['fid'].' and comp_id='.$this->company['id'].' and level=2 and type=1 '.$sqlw);
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
	public function cateProjAction() {
		$show_del=(int)$_GET['show_del'];
		if($show_del)
			$sqlw="";
		else
			$sqlw=" and status=1";
		$list=\ProjectInfo::model()->findAll('cate='.(int)$_GET['fid'].' and comp_id='.$this->company['id'].$sqlw);
		if($list)
			foreach ($list as &$value) {
				if($value['status']<0)
					$value['name']=$value['name'].'(删除)';
				if($value['status']==0)
					$value['name']=$value['name'].'(停售)';
			}
		if($list)
			$this->ajaxReturn(200,'ok',$list);
		$this->ajaxReturn(400,'error');
	}



}