<?php
namespace pos;
use Ivy\core\CException;
/**
 * 实操单
 */
class PracticeController extends BaseController
{

	/**
	 * 订单列表
	 * @param [type] $id [description]
	 */
	public function indexAction(){
		$timeType=isset($_GET['stime'])?$_GET['stime']:"1";
		$stime = $this->sTime($timeType);
		if($stime){
			$map['t.last_time']	= 	array(array('gt',$stime['begin']),array('lt',$stime['end']));
		}
		$map['po.comp_id']=array('eq',\Ivy::app()->user->comp_id);
		if($this->is_store){
			$map['po.md_id']	= 	array('eq',\Ivy::app()->user->dept_id);
		}else{
            if($_GET['md_id']) $map['po.md_id']  =   array('eq',(int)$_GET['md_id']);
        }
		$search = isset($_GET['t_search'])?$_GET['t_search']:array();
        if(isset($search['cu_name'])){
            $map['cu.name']	= 	array('like',"%".$search['cu_name']."%");
            unset($search['cu_name']);
        }
        if(isset($search['project_name'])){
            $map['pi.name']	= 	array('like',"%".$search['project_name']."%");
            unset($search['project_name']);
        }
		foreach ($search as $key => $value) {
			if(!empty($value)){
				if(strpos($value,'%')===false)
					$map[$key]	= 	array('eq',$value);
				else
					$map[$key]	= 	array('like',$value);
			}
		}
		$pager= \PracticeOrderDetail::model()
		->field(array("t.*,po.id as id,po.sn as sn,po.status as status,cu.name as cu_name,pi.name as project_name,osd.num as num,e.netname as made_name"))
		->join('practice_order po on po.id=t.por_id')
        ->join('customer_info cu on cu.id=po.cu_id')
		->join('project_info pi on pi.id=t.project_id')
		->join('order_sale_detail osd on osd.id=t.detail_id')
		->join('employ_user e on e.id=po.create_user')
		->where($map)
		->order('last_time desc')
		->group('t.id')
		->getPagener();
		//var_dump($pager);

		$this->view->assign(array('pager'=>$pager,'timeType'=>$timeType,"light_nav"=>$this->url('pos/sale/index'),))->display();

	}
	/**
	 * 显示页面
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function viewAction($id)
	{
		$model = PracticeModel::model()->getPracticeModel($id,true);
		if(empty($model))
			throw new CException('无效订单');
		$cu_info=$this->getCuInfo($model->o_s->cu_id);
		$CustomerReProject=\CustomerReProject::getReProList($model->o_s->cu_id);
		$list=\CustomerSatisfice::getListByPractice($model->o_s->id);

		$this->view->assign(array(
			"list"				=>$list,
			"cu_info"           =>$cu_info,		//客户信息渲染
			"model"             =>$model,			
			"CustomerReProject" =>$CustomerReProject,
		))->display();
	}
	/**
	 * 获取配方选择页面
	 * @param  [int] $pro_id      [项目(改为detail_id)]
	 * @param  [int] $formula_ids [已选的配方]
	 * @return [type]              [description]
	 */
	public function getpfAction($pro_id,$formula_ids)
	{
		$OrderSaleDetail=\OrderSaleDetail::model()->findByPk($pro_id);
		$model=\ProjectFormula::model()->getByProj($OrderSaleDetail->pro_id,1);
		echo $this->view->assign(array(
			'model'       =>$model,
			'formula_ids' =>(int)$formula_ids,
			))->render();
	}
	/**
	 * 获取美疗师选择页面
	 * @param  [int] $pro_id      [项目]
	 * @param  [int] $formula_ids [已选的配方]
	 * @return [type]              [description]
	 */
	public function getmlsAction($operators)
	{
		$dept_id=\Ivy::app()->user->dept_id;
		$model=\EmployUser::getList(" AND position_id=7 AND dept_id={$dept_id}");
		$operators=@explode(',',$operators);
		echo $this->view->assign(array(
			'model'     =>$model,
			'operators' =>$operators,
			))->render();
	}
	/**
	 * 创建新订单
	 * @param [type] $id [description]
	 */
	public function addAction($id=null){
		if(is_null($id))
			throw new CException('无效客户id');
		$cu_info=$this->getCuInfo($id,true);
		$CustomerReProject=\CustomerReProject::getReProList($id);
		$this->view->assign(array(
			"id"                =>$id,
			"cu_info"           =>$cu_info,		//客户信息渲染
			"CustomerReProject" =>$CustomerReProject,//剩余项目
		))->display('edit');
	}


	/**
	 * 新建订单提交（非编辑提交）
	 */
	public function addSaveAction(){
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = PracticeModel::model()->savePracticeNew($_POST);

		if(!$res) $this->ajaxReturn(400,PracticeModel::model()->popErr());
		
		$this->ajaxReturn(200,"保存成功！",$res);

	}


	/**
	 * 编辑订单(未付款的订单)
	 */
	public function editAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');

        $model = PracticeModel::model()->getPracticeModel($id,true);
        if($model->o_s->status==1||$model->o_s->status==-1){
            if(empty($model))
                throw new CException('无效订单');
            $cu_info=$this->getCuInfo($model->o_s->cu_id);
            $CustomerReProject=\CustomerReProject::getReProList($model->o_s->cu_id);

            $this->view->assign(array(
                "cu_info"           =>$cu_info,		//客户信息渲染
                "model"             =>$model,
                "CustomerReProject" =>$CustomerReProject,
            ))->display('view');
        }else{
            $cu_info=$this->getCuInfo($model->o_s->cu_id);
            $CustomerReProject=\CustomerReProject::getReProList($model->o_s->cu_id);
            $this->view->assign(array(
                "cu_info"           =>$cu_info,		//客户信息渲染
                "model"             =>$model,			//订单货物模型
                "CustomerReProject" =>$CustomerReProject,
            ))->display('edit');
        }
	}
	/**
	 * 编辑订单提交（编辑提交）
	 */
	public function editSaveAction($id=null){
		if(is_null($id))
			throw new CException('无效订单id');
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = PracticeModel::model()->editPracticeNew($id,$_POST);

		if(!$res) $this->ajaxReturn(400,PracticeModel::model()->popErr());

		$this->ajaxReturn(200,"保存成功！",$res);

	}


	/**
	 * 订单付款界面
	 * @param [type] $id 订单id
	 */
	public function payAction($id=null){
		if(is_null($id))
			throw new CException('无效订单');
		$model = PracticeModel::model()->getPracticeModel($id,true);
		$cu_info=$this->getCuInfo($model->o_s->cu_id,true);
		$CustomerReProject=\CustomerReProject::getReProList($model->o_s->cu_id);

		$this->view->assign(array(
			"cu_info"           =>$cu_info,		//客户信息渲染
			"model"             =>$model,			
			"CustomerReProject" =>$CustomerReProject,
		))->display();
	}
	/**
	 * 订单付款提交
	 * @param [type] $id 订单id
	 */
	public function paySaveAction($id=null){

		if(is_null($id))
			throw new CException('无效订单');
		if(!\Ivy::app()->user->checkToken())
			$this->ajaxReturn(400,"重复提交");

		$res = PracticeModel::model()->PracticeHk($id);
		if(!$res) $this->ajaxReturn(400,PracticeModel::model()->popErr());
		$model=\PracticeOrder::model()->findByPk($id);
		if(\CompanyInfo::getConfig('wx_practice_msg')=='true'){
			\WemallApi::sendNew(
				$model->comp_id,
				$model->cu_id,
				date('Y年m月d日').' 耗卡通知',
				'亲,您本次实操已经耗卡成功.感谢您的光临!',
				array('m'=>'meihui_practice','do'=>'mylist','op'=>'detail','id'=>$id),
				'wechat_charge.jpg',
				'meihui_practice'
				);
		}
		
		$this->ajaxReturn(200,"保存成功！",$res);
	}
	public function wxSendWjAction($id=null)
	{
		if(is_null($id))
			throw new CException('无效订单');
		$model=\PracticeOrder::model()->findByPk($id);
		$cu_m=\CustomerInfo::model()->findByPk($model->cu_id);
		$dm=\EmployDept::model()->findByPk($model->md_id);
		$customer_name=$cu_m->name;
		$gender_str=$cu_m->sex.'士';
		$ymd=date('Y-m-d',$model->pay_time);
		$time=date('m月d日 H:i', $model->pay_time);
		$dept_name=$dm->dept_name;
		$prom=\PracticeOrderDetail::model()->field('pi.name')->join('project_info pi ON t.project_id=pi.id')->findAll("por_id={$id}");
		$project_name='';
		foreach ($prom as $key => $value) {
			$project_name.=$value['name'].',';
		}
		$description = "";
		$description .= "尊敬的{$customer_name}{$gender_str}\n";
		$description .= "您于{$ymd}的最新服务信息\n";
		$description .= "服务时间：{$time}\n";
		$description .= "服务地点：{$dept_name}\n";
		$description .= "服务项目：{$project_name}\n";
		$description .= "\n为更好的帮助我们提高服务质量，敬请您对本次服务做出客观的评价。您的评价内容将不会泄露给为您提供服务的具体人员，请放心评价！\n";

		\WemallApi::sendNew(
				$model->comp_id,
				$model->cu_id,
				date('Y年m月d日').' 服务提醒',
				$description,
				array('m'=>'meihui_satisfice','do'=>'create','id'=>$id),
				'survey_s.jpg',
				'meihui_satisfice'
				);
	}
	 /**
     * 废弃订单
     * @param null $id
     * @throws CException
     */
    public function cancelAction($id=null){
        if(is_null($id))
            throw new CException('无效订单');
        if(!\Ivy::app()->user->checkToken())
            $this->ajaxReturn(400,"重复提交");

        $model = \PracticeOrder::model()->findByPk($id);
        $model->status = -1;
        if(!$model->save()) $this->ajaxReturn(400,\PracticeOrder::model()->popErr());

        $this->ajaxReturn(200,"废弃订单成功！",$model->cu_id);
    }
}
