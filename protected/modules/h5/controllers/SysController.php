<?php
namespace h5;
use Ivy\core\CException;
use report\ReportModel;
/**
 * 报表
 */
class SysController extends BaseController {
    /**
     * 报表导航
     */
    public function indexAction(){
        $this->view->assign()->display();
    }

   
    /**
	 * 门店业绩报表
	 */
	public function MDYJAction()
	{
		$md_ids=$_GET['md_id'];
		if(empty($md_ids)){
			$md_ids=0;
		}
		//$_GET['begin']='2015-03-28';
		//$_GET['DATE']='2015-03-28';

		$stime=$_GET['DATE']?strtotime($_GET['DATE']):strtotime(date('Y-m-d'));
		$etime=$stime+24*3600;

		$model= ReportModel::model()->getMDYJ($md_ids,$stime,$etime);

		$list = \EmployDept::getMDList($md_ids);
		foreach ($list as &$value) {
			$value['md_ids']=$value['id'];
			$value=array_merge($value,ReportModel::model()->getMDYJ_TK($value['md_ids'],$stime,$etime));//退款部分
			$value=array_merge($value,ReportModel::model()->getMDYJ_SC($value['md_ids'],$stime,$etime));//实操部分
			
			foreach ($model as $row) {
				if($row['md_ids']==$value['md_ids']){
					$value=array_merge($value,$row);
				}
			}
		}
		$res=array();
		$res['MDID'] = $list[0]['md_ids'];//仅需要单门店查询
		$res['XJYJ']=array();//现金业绩
		$res['XJYJ']['CZ']=$list[0]['XJSR1']?$list[0]['XJSR1']:0;//充值
		$res['XJYJ']['HK']=$list[0]['XJSR2']?$list[0]['XJSR2']:0;//还款
		$res['XJYJ']['GM']=$list[0]['XJSR3']?$list[0]['XJSR3']:0;//购买


		$res['KKYJ']=array();//卡扣业绩
		$res['KKYJ']['XJ']=(int)($list[0]['TKXJ']+$list[0]['XJZHKK1']+$list[0]['XJZHKK2']+$list[0]['XJZHKK3']);//退款现金+购买现金卡扣
		$res['KKYJ']['ZS']=(int)($list[0]['TKZS']+$list[0]['ZSZHKK1']+$list[0]['ZSZHKK2']+$list[0]['ZSZHKK3']);//退款现金+购买现金卡扣

		$res['SHYJ']=array();//实耗业绩
		$res['SHYJ']['GM']=(int)$list[0]['GMXH'];
		$res['SHYJ']['ZS']=(int)$list[0]['ZSXH'];
		$res['SHYJ']['KJ']=(string)$list[0]['KJXH']?$list[0]['KJXH']:0;

		//到店客流
		$res['DDKL']= ReportModel::model()->getDDKL($md_ids,$stime,$etime,true);//到店客流
		$res['KLXX']= SysModel::model()->getKLXX($md_ids,$stime,$etime);
		
		//成交人头
		$res['CJRT']= ReportModel::model()->getCJRT($md_ids,$stime,$etime);//成交人头
		$res['RTXX']= SysModel::model()->getCJXX($md_ids,$stime,$etime);
		//getCJRT

		//var_dump($res);die;
		
		$this->view->assign(array(
			'model'=>$res,
			'md_ids'=>$md_ids,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$_GET['DATE']?$_GET['DATE']:date('Y-m-d')
			))->display();
	}




	/**
	 * 经营报表
	 */
	public function JYBBAction()
	{
		$md_ids=$_GET['md_id'];
		if(empty($md_ids)){
			$md_ids=0;
		}

		//$_GET['DATE']="2015-12";
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');

		$times=ReportModel::model()->getTimes('m',$month);
		$stime=$times['stime'];
		$etime=$times['etime'];
		//$day=round(($etime-$stime)/24/3600);
		//var_dump(date('Ymd H:i:s',$stime) ,date('Ymd H:i:s',$etime) );die;
		$model= ReportModel::model()->getJYBB_XJ($md_ids,$stime,$etime);
		
		$list = \EmployDept::getMDList($md_ids);
		
		foreach ($list as &$value) {
			$value['md_ids']=$value['id'];
			$value['XJSHYJ']=ReportModel::model()->getXJSHYJ($value['md_ids'],$stime,$etime);//现金实耗业绩
			$value['DDKL']=ReportModel::model()->getDDKL($value['md_ids'],$stime,$etime,true);//到店客流
			$value['CJRT']=ReportModel::model()->getCJRT($value['md_ids'],$stime,$etime);//成交人头
			$value['XJSR']='/';
			$value['XJKK']='/';
			foreach ($model as $row) {
				if($row['md_ids']==$value['md_ids']){
					$value['XJSR']=$row['XJSR'];
					$value['XJKK']=$row['XJKK'];
				}
			}
		}
		$res = $list[0];
		//现金详细
		$res['XJSR_XX']=ReportModel::model()->getJYBB_XJSR($md_ids,$stime,$etime);
		$res['XJSR_XX']=$res['XJSR_XX'][0];
		unset($res['XJSR_XX']['md_ids'],$res['XJSR_XX']['dept_name']);

		//卡扣详细
		$comp_id = \Ivy::app()->user->comp_id;
		$sql_md = " and id in ({$md_ids})";
		if($md_ids==0) $sql_md = "";
		$dept_ids = \EmployDept::model()->findAll("comp_id={$comp_id} and status>0 {$sql_md}");
		$gm_xm=0;$gm_cp=0;$gm_kq=0;$gm_hk=0;$zs_xm=0;$zs_cp=0;$zs_kq=0; //全连锁的
		foreach($dept_ids as $dept_id){
			if($md_ids!=0 && $md_ids!=$dept_id['id']) continue;
			$gm_xm += $data[$dept_id['id']]['gm_xm'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,1,1,$stime,$etime);
			$gm_cp += $data[$dept_id['id']]['gm_cp'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,2,1,$stime,$etime);
			$gm_kq += $data[$dept_id['id']]['gm_kq'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,3,1,$stime,$etime);
			$gm_hk += $data[$dept_id['id']]['gm_hk'] = ReportModel::model()->getJYBB_KKYJ_HK($dept_id['id'],0,2,1,$stime,$etime);
			$zs_xm += $data[$dept_id['id']]['zs_xm'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,1,2,$stime,$etime);
			$zs_cp += $data[$dept_id['id']]['zs_cp'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,2,2,$stime,$etime);
			$zs_kq += $data[$dept_id['id']]['zs_kq'] = ReportModel::model()->getJYBB_KKYJ($dept_id['id'],1,3,2,$stime,$etime);
		}
		if($md_ids==0){
			$data[0]['gm_xm'] = sprintf('%.2f',$gm_xm);
			$data[0]['gm_cp'] = sprintf('%.2f',$gm_cp);
			$data[0]['gm_kq'] = sprintf('%.2f',$gm_kq);
			$data[0]['gm_hk'] = sprintf('%.2f',$gm_hk);
			$data[0]['zs_xm'] = sprintf('%.2f',$zs_xm);
			$data[0]['zs_cp'] = sprintf('%.2f',$zs_cp);
			$data[0]['zs_kq'] = sprintf('%.2f',$zs_kq);
		}
		$res['XJKK_XX']=$data[$md_ids];

		//实耗详细
		$res['SHYJ_XX']=array('gm_xm'=>ReportModel::model()->getXJSHYJ($md_ids,$stime,$etime),'zs_xm'=>0,'kq_xm'=>0);
		//客流详细
		$res['KL_XX']=ReportModel::model()->getJYBB_KL($md_ids,$stime,$etime);
		$res['KL_XX']=$res['KL_XX'][0];
		$res['KL_XX']['CJRT']=ReportModel::model()->getCJRT($md_ids,$stime,$etime);
		unset($res['KL_XX']['md_ids'],$res['KL_XX']['dept_name']);
		

		//var_dump($res);die;
		$this->view->assign(array(
			'model'=>$res,
			'md_ids'=>$md_ids,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
			))->display();
	}


	/**
	 * 品相业绩报表
	 */
	public function PXYJAction()
	{
		$md_ids=$_GET['md_id']?$_GET['md_id']:0;
		//$_GET['DATE']="2015-12";
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		$times=ReportModel::model()->getTimes('m',$month);
		$stime=$times['stime'];
		$etime=$times['etime'];

		$xm=$_GET['xm'];
		$cp=$_GET['cp'];
		$xm_list=$cp_list=array();
		if ($xm) {
			$ProjectInfo=\ProjectInfo::model()->findAll("id in ({$xm})");
			if($ProjectInfo){
				foreach ($ProjectInfo as $key => $value) {
					$xm_list[$value['id']]=$value['name'];
				}
			}
		}
		if ($cp) {
			$ProjectInfo=\ProductInfo::model()->findAll("id in ({$cp})");
			if($ProjectInfo){
				foreach ($ProjectInfo as $key => $value) {
					$cp_list[$value['id']]=$value['name'];
				}
			}
		}
		$model= ReportModel::model()->getPXYJBB($md_ids,$stime,$etime,$xm,$cp);
		if($model){
			foreach ($model as  &$value) {
				$HK=array();
				$HK=ReportModel::model()->getPXYJBB_HK($md_ids,$stime,$etime,$value['id'],$value['type']);
				if($HK){
					$value['XJSR']=$value['XJSR']+$HK['XJSR'];
					$value['XJZHKK']=$value['XJZHKK']+$HK['XJZHKK'];
				}
				$value=array_merge($value,ReportModel::model()->getPXYJBB_SC($md_ids,$stime,$etime,$value['id'],$value['type']));//实耗部分
			}
		}

		$this->view->assign(array(
			'model'=>$model,
			'xm_list'=>$xm_list,
			'cp_list'=>$cp_list,
			'md_ids'=>$md_ids,
			'md_list'=>\EmployDept::getMDList(),
			'all_xm_list'=>$this->getProList(1),
			'all_cp_list'=>$this->getProList(2),
			'date'=>$month
			))->display();
	}

	protected function getProList($type=1){
		$m = \ProjectInfo::model();
		if($type==2)
			$m = \ProductInfo::model();
		
		$map['t.type']=array("eq",$type);
		$map['t.level']=array("eq",2);
		$map['t.status']=array("eq",1);
		$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);
		$list = \ProCate::model()->field("t.id,t.name")->where($map)->findAll();
		foreach ($list as &$value) {
			$value['list']=$m->field("id,name")->where("cate={$value['id']} AND status = 1")->findAll();
		}
		return $list;
	}

	/**
	 * 现金流水报表
	 * @param [type] $id [description]
	 */
	public function XJLSAction(){

		$md_id=$_GET['md_id']?$_GET['md_id']:0;
		
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		$list = array();
		if($month){
			$mon = str_replace("-","",$month);
			$rows = ReportModel::model()->getXJLS($md_id,$mon);//现金流水
			foreach ($rows as $k => $row){
				$o=$row;unset($o['day']); $_all=array_sum($o);
				if($_all==0) continue;
				$list[]=array('day'=>$row['day'],'xj'=>$_all);
			}

		}
		//var_dump($list);
		$this->view->assign(array(
			'list'=>$list,
			'md_id'=>$md_id,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
		))->display();
	}
	/**
	 * 现金流水报表-详细
	 * @param [type] $id [description]
	 */
	public function XJLS_XAction(){
		$md_id=$_GET['md_id']?$_GET['md_id']:0;
		$day=$_GET['DAY']?$_GET['DAY']:date('Ymd');
		$list = ReportModel::model()->getXJLS_X($md_id,$day);//现金流水日详细
		$this->view->assign(array(
			'list'=>$list,
		))->display();
	}

	/**
	 * 负债分析报表
	 * @param [type] $id [description]
	 */
	public function FZFXAction(){
		$md_id=$_GET['md_id']?$_GET['md_id']:"0";
		
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		$pre_month =  date("Y-m",strtotime($month.'-01')-100);
		//var_dump($month);
		//var_dump($pre_month);//die;
		if($month>date('Y-m')){
			throw new CException("无未来数据！");
		}
		if($month==date('Ym')){
			$list = ReportModel::model()->getFZFX($md_id);//负债分析 实时计算
		}else{
			$list = ReportModel::model()->getFZFX($md_id,str_replace("-","",$month));//负债分析 历史数据
		}
		$list_pre = ReportModel::model()->getFZFX($md_id,str_replace("-","",$pre_month));//负债分析

		$list_kk = ReportModel::model()->getFZFX_KK($md_id,str_replace("-","",$month));//负债分析 _卡扣

		
		$list = $this->merArr($list);
		$list_pre = $this->merArr($list_pre);
		$list_kk = $this->merArr($list_kk);
		
		//var_dump($list,$list_pre,$list_kk);
		$this->view->assign(array(
			'list'=>$list,
			'list_pre'=>$list_pre,
			'list_kk'=>$list_kk,
			'md_id'=>$md_id,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
		))->display();
	}

	//合并数组
	protected function merArr($list){
		$one = array_shift($list);
		unset($one['id'],$one['md_id'],$one['month'],$one['dept_name'],$one['dept_id']);
		foreach ($list as $v) {
			foreach ($one as $key => $value) {
				$fielt = array('id','md_id','month','dept_name','dept_id');
				if(!in_array($key, $fielt)){
					$one[$key]+=$v[$key];
				}
			}
		}
		return $one;
	}

	/**
	 * 到店客流报表
	 */
	public function DDKLAction()
	{
		$md_id=$_GET['md_id']?$_GET['md_id']:0;
		//$md_id=101;
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		//$month="2015-12";
		$times=ReportModel::model()->getTimes('m',$month);
		$stime=$times['stime'];
		$etime=$times['etime'];

	
		$day=round(($etime-$stime)/24/3600);
		$model= ReportModel::model()->getDDKL($md_id,$stime,$etime);
		if($model)
			foreach ($model as  &$value) {
				$value['RJKL']=$day?sprintf('%.2f',$value['ZKL']/$day):$value['ZKL'];//日均客流
				$value['DDRT']=ReportModel::model()->getDDRT($value['md_ids'],$stime,$etime);//到店人头
				$value['CJRT']=ReportModel::model()->getCJRT($value['md_ids'],$stime,$etime);//成交人头
				$value['DDPL']=$value['DDRT']?sprintf('%.2f',$value['ZKL']/$value['DDRT']):0;//到店频率
				$ZGKS=ReportModel::model()->getZGKS($value['md_ids']);
				$value['DDL']=$ZGKS?sprintf('%.2f',$value['DDRT']/$ZGKS):0;//到店率
			}
		//var_dump($model[0]);
		$this->view->assign(array(
			'list'=>$model[0],
			'md_id'=>$md_id,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
			))->display();
	}

	/**
	 * 月客流分析表
	 */
	public function YKLFXAction(){

		$md_id=$_GET['md_id']?$_GET['md_id']:0;
		//$md_id=101;
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		//$month="2016-02";
		$times=ReportModel::model()->getTimes('m',$month);
		$stime=$times['stime'];
		$etime=$times['etime'];

		$cu_types = \CustomerInfo::model()->getCuType();
		$md_ids = $md_id;
		if($md_ids==0){
			$arr=array();
			$d = \EmployDept::model()->findAll("status=1 AND type=2 AND comp_id=".\Ivy::app()->user->comp_id);
			foreach ($d as $val) {
				$arr[]=$val['id'];
			}
			$md_ids = implode(",", $arr);
		}
		$data = array();
		if(!empty($md_ids)){
			foreach($cu_types as $cu_type){
				$data[$cu_type]['ZS'] = ReportModel::model()->getDAZS($cu_type,null,$md_ids,$stime,$etime);
				$data[$cu_type]['YX'] = ReportModel::model()->getDAZS($cu_type,1,$md_ids,$stime,$etime);
				$data[$cu_type]['JD'] = ReportModel::model()->getDAZS($cu_type,2,$md_ids,$stime,$etime);
				$data[$cu_type]['SD'] = ReportModel::model()->getDAZS($cu_type,3,$md_ids,$stime,$etime);
				$data[$cu_type]['DDRT'] = ReportModel::model()->getCuTypeDDRT($cu_type,$md_ids,$stime,$etime);
				$data[$cu_type]['DDKL'] = ReportModel::model()->getCuTypeDDKL($cu_type,$md_ids,$stime,$etime);
				$data[$cu_type]['CJRT'] = ReportModel::model()->getCuTypeCJRT($cu_type,$md_ids,$stime,$etime);
			}
		}
		//var_dump($cu_types,$data);
		$this->view->assign(array(
			'cu_types'=>$cu_types,
			'data'=>$data,
			'days'=>date('t',$stime),
			'md_id'=>$md_id,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
		))->display();
	}



	/**
	 * 新客分析表
	 */
	public function XKFXAction(){
		

		$md_id=$_GET['md_id']?$_GET['md_id']:0;
		//$md_id=101;
		$month=$_GET['DATE']?$_GET['DATE']:date('Y-m');
		//$month="2016-02";
		$times=ReportModel::model()->getTimes('m',$month);
		$stime=$times['stime'];
		$etime=$times['etime'];

		$md_ids = $md_id;
		if($md_ids==0){
			$arr=array();
			$d = \EmployDept::model()->findAll("status=1 AND type=2 AND comp_id=".\Ivy::app()->user->comp_id);
			foreach ($d as $val) {
				$arr[]=$val['id'];
			}
			$md_ids = implode(",", $arr);
		}



		$data = $list = array();
		if(!empty($md_ids)){
			foreach(explode(',',$md_ids) as $dept_id){
				if($dept_id!='0'){
					$xkcj = ReportModel::model()->getXKCJCS($dept_id,$stime,$etime);
					$data[$dept_id]['dept_name'] = \EmployDept::model()->findByPk($dept_id)->dept_name;//$xkcj['dept_name'];
					$data[$dept_id]['dept_id'] = \EmployDept::model()->findByPk($dept_id)->id;//$xkcj['dept_name'];
					$data[$dept_id]['xk_rs'] = $xkcj['num'];
					$data[$dept_id]['xk_scxj'] = empty($xkcj['price'])?0:$xkcj['price'];
					$zcj = ReportModel::model()->getZCJCS($dept_id,$stime,$etime);
					$data[$dept_id]['z_cjcs'] = $zcj['num'];
					$data[$dept_id]['z_cjje'] = empty($zcj['price'])?0:$zcj['price'];
				}
			}
		}
		if($md_id==0){
			$list['dept_name']="全连锁";
			$list['dept_id']="0";
			foreach($data AS $row){
				$list['xk_rs'] +=$row['xk_rs'];
				$list['xk_scxj'] +=$row['xk_scxj'];
				$list['z_cjcs'] +=$row['z_cjcs'];
				$list['z_cjje'] +=$row['z_cjje'];
			}

		}else{
			$list = array_shift($data);
		}
		
		//var_dump($list);
		$this->view->assign(array(
			'list'=>$list,
			'md_id'=>$md_id,
			'md_list'=>\EmployDept::getMDList(),
			'date'=>$month
		))->display();
	}
	
}