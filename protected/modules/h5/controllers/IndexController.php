<?php
namespace h5;
use Ivy\core\CException;
/**
 * 门店经理
 */
class IndexController extends BaseController {
	/**
	 * 总经理首页
	 */
	public function indexAction(){
        //今日销售业绩
        $d_stime=strtotime(date("Ymd"));
        $d_etime=$d_stime+24*3600;
        $xj_d = \report\ReportModel::model()->getJYBB_XJ(0,$d_stime,$d_etime);
    	$sc_d = \report\ReportModel::model()->getXJSHYJ(0,$d_stime,$d_etime);
    	$kk_d = \report\ReportModel::model()->getDDKL(0,$d_stime,$d_etime,true);//到店客流
    	$cj_d = \report\ReportModel::model()->getCJRT(0,$d_stime,$d_etime);//成交人头

    	//月数据
    	$m_stime=strtotime(date("Ym")."01");
    	$days = date('t', $stime);
    	$m_etime = $m_stime+$days*24*3600;

    	$xj_m = \report\ReportModel::model()->getJYBB_XJ(0,$m_stime,$m_etime);
    	$sc_m = \report\ReportModel::model()->getXJSHYJ(0,$m_stime,$m_etime);
    	$kk_m = \report\ReportModel::model()->getDDKL(0,$m_stime,$m_etime,true);//到店客流
    	$cj_m = \report\ReportModel::model()->getCJRT(0,$m_stime,$m_etime);//成交人头


    	$rc = \boss\TargetModel::model()->getTarget(0,date("Ym"));//目标值

    	$target["xj_d"]=round($xj_d[0]['XJSR']+$xj_d[0]['XJKK'],2);
    	$target["xj_m"]=round($xj_m[0]['XJSR']+$xj_m[0]['XJKK'],2);
    	$target["sc_d"]=round($sc_d,2);
    	$target["sc_m"]=round($sc_m,2);
    	$target["kk_d"]=round($kk_d,2);
    	$target["cj_d"]=round($cj_d,2);
    	$target["kk_m"]=round($kk_m,2);
    	$target["cj_m"]=round($cj_m,2);
    	$target['sale_target']=round($rc['sale_target'],2);
    	$target['prac_target']=round($rc['prac_target'],2);

        
    	//var_dump($target);
		$this->view->assign(array(
			'target' => $target,
			//'data' => $this->getLineData(),
            //'out'=>$this->getOutData(),
            'store_name'=>$this->company->comp_name,
		))->display();
	}




    

    //即将流失客户
    public function getOutData(){
        $out["A"] = \CustomerInfo::model()->outlistnum(null,"A");
        $out["B"] = \CustomerInfo::model()->outlistnum(null,"B");
        $out["C"] = \CustomerInfo::model()->outlistnum(null,"C");
        $out["D"] = \CustomerInfo::model()->outlistnum(null,"D");
        $out["E"] = \CustomerInfo::model()->outlistnum(null,"E");
        return $out;
    }

	//曲线图数据
    public function getLineData(){
        $month=date('Ym');
        //每日数据列表
        $days=date('t', strtotime($month.'01'));//此月份的天数
        $i=1;
        while($i<=$days){
            $cur_day=$month.str_pad($i, 2, "0", STR_PAD_LEFT);
			$d_stime=strtotime($cur_day);
			$d_etime=$d_stime+24*3600;	
            $xj_d = \report\ReportModel::model()->getJYBB_XJ(0,$d_stime,$d_etime);//获取当日现金业绩
    		$sc_d = \report\ReportModel::model()->getXJSHYJ(0,$d_stime,$d_etime);//获取当日实耗业绩
    		$kk_d = \report\ReportModel::model()->getDDKL(0,$d_stime,$d_etime,true);//到店客流

    		$sale_list[$i]=round($xj_d[0]['XJSR']+$xj_d[0]['XJKK'],2);
    		$prac_list[$i]=round($sc_d,2);
    		$kk_list[$i]=round($kk_d,2);
            $i++;
        }
        $data['sale_list']=$sale_list;//销售业绩
        $data['prac_list']=$prac_list;//实操业绩
        $data['kk_list']=$kk_list;//客流
        return $data;
    }







    /**
     * 业绩分配
     * @return [type] [description]
     */
    public function targetAction($month=null){
    	$month=is_null($month)?date("Ym"):$month;
		$maps = array('t.comp_id' => array('eq',$this->company->id),'t.month' => array('eq',$month));
    	$list = \TargetMonth::model()
    	->field("t.*,eu.netname,ed.dept_name")
    	->join("employ_user as eu ON eu.id=t.user_id")
    	->join("employ_dept as ed ON ed.id=eu.dept_id")
    	->where($maps)
    	->findAll();

    	$stime=strtotime($month."01");
    	$days = date('t', $stime);
    	$etime = $stime+$days*24*3600;

    	$xj_list = \report\ReportModel::model()->getJYBB_XJ(0,$stime,$etime);
    	$sc = \report\ReportModel::model()->getXJSHYJ(0,$stime,$etime);

    	$target["xj"]=round($xj_list[0]['XJSR']+$xj_list[0]['XJKK'],2);
    	$target["sc"]=round($sc,2);
    	$rc = TargetModel::model()->getTarget(0,$month);
    	$target['sale_target']=round($rc['sale_target'],2);
    	$target['prac_target']=round($rc['prac_target'],2);
        $this->view->assign(array(
        	"target"=>$target,
        	"list"=>$list,
        ))->display();
    }

    //添加(修改)业绩 数据保存
	public function addAction() {
		if(!\Ivy::app()->user->checkToken())
			throw new CException("重复提交");
		$_POST['comp_id']=$this->company->id;
		if($_POST['id']==''){
			$r = \TargetMonth::model()->where()->find("month={$_POST['month']} AND user_id={$_POST['user_id']}");
			if($r)
				throw new CException("保存失败！已经存在业绩目标。");
		}
		$res= \TargetMonth::model()->saveData($_POST);
		if($res){
            $arr=array(
                'to_uids'=>$res->user_id,
                'title'=>'【指标分配提醒】',
                'content'=>'您本月的业绩指标已分配(销售业绩目标：'.$res->sale_target.'，实操业绩目标：'.$res->prac_target.')。加油哦！',
            );
            \SmsMessage::send($arr);
            $this->redirect('target');
        }
		throw new CException("保存失败！");
	}

	//编辑业绩
	public function editAction($id=null) {
		$id=(int)$id;
		//门店经理列表
		$maps = array('comp_id' => array('eq',$this->company->id),'position_id' => array('eq',5));
		$user_list= \EmployUser::model()->where($maps)->findAll();

		$model = \TargetMonth::model()->findByPk($id);
		$html=$this->view->assign(array(
				'model' => $model,
				'user_list' => $user_list,
		))->render();
		$this->view->tagToken($html);//render 方式需要强制增加token
		echo $html;
	}

    //身份切换到公司管理员
    public function changeAction() {
        $show_position = \Ivy::app()->user->getState('show_position');
        if($show_position==10){
            \Ivy::app()->user->setState('show_position',1);
            $this->redirect('boss/index/index');
        }else{
            \Ivy::app()->user->setState('show_position',10);
            $this->redirect('admin/admin/index');
        }
        
        
    }


}