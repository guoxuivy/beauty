<?php
namespace report;
use Ivy\core\CException;
/**
 * report 首页
 */
class IndexController extends \SController
{

	/**
	 * 报表导航列表
	 * @param [type] $id [description]
	 */
	public function indexAction(){
		$this->view->assign(array(
		))->display();
	}
	
	/**
	 * 获取门店人员列表
	 * @param [type] $id [description]
	 */
	public function getMdUserAction($md_id){
		$map['t.dept_id']=array("in",$md_id);
		if($md_id=="0"){//全连锁
			unset($map['t.dept_id']);
		}
		$map['t.status']=array("eq","1");
		$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);

		$list = \EmployUser::model()->field("id,netname")->findAll($map);

		$this->ajaxReturn(200,"",$list);
	}
	/*************k********************/
	/**
	 * 到店客流报表
	 */
	public function DDKLAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
			if($this->is_store)
				$md_ids = \Ivy::app()->user->dept_id;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime=$times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$day=round(($etime-$stime)/24/3600);
		$model= ReportModel::model()->getDDKL($md_ids,$stime,$etime);
		if($model)
			foreach ($model as  &$value) {
				$value['RJKL']=$day?sprintf('%.2f',$value['ZKL']/$day):$value['ZKL'];//日均客流
				$value['DDRT']=ReportModel::model()->getDDRT($value['md_ids'],$stime,$etime);//到店人头
				$value['CJRT']=ReportModel::model()->getCJRT($value['md_ids'],$stime,$etime);//成交人头
				$value['DDPL']=$value['DDRT']?sprintf('%.2f',$value['ZKL']/$value['DDRT']):0;//到店频率
				$ZGKS=ReportModel::model()->getZGKS($value['md_ids']);
				$value['DDL']=$ZGKS?sprintf('%.2f',$value['DDRT']/$ZGKS):0;//到店率
			}
		$this->view->assign(array(
			'model'=>$model,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 经营报表
	 */
	public function JYBBAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids)){
			$md_ids=0;
			if($this->is_store)
				$md_ids = \Ivy::app()->user->dept_id;
		}
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime=$times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$day=round(($etime-$stime)/24/3600);
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
		$this->view->assign(array(
			'model'=>$list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 经营报表-实耗业绩
	 */
	public function JYBB_SHYJAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime=$times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$mdids=	ReportModel::model()->getMDIDS($md_ids);
		echo	'<div class="table_container">
		                     <!--table_con-->
		                     <div class="table_con pos_cz_qr_top_04" style="margin-right:0px;">
		                        
		                        <!--table_con_son-->
		                         <div class="table_con_son" style="min-width: 695px;">
		                          <!-- <div class="pos_cz_tow_top pos_cz_qr_02">赠送现金</div>-->
		                           <div class="table_title">
		                             <ul>
		                               <li>业务明细</li>
		                               ';
		                               foreach ((array)$mdids as $value) {
		                               	echo "<li>{$value['dept_name']}</li>";
		                               }
		                               
		                               
		echo                       ' </ul>
		                          </div>';

        echo              '<div class="table_list clear">
                                    <ul>
                                      <li  style="width: 130px;">购买项目消耗</li>';
                                      foreach ((array)$mdids as $key=>$value) {
                                      	$_money= ReportModel::model()->getXJSHYJ($value['id'],$stime,$etime);
                                      	$mdids[$key]=array_merge($mdids[$key],ReportModel::model()->getMDYJ_SC($value['id'],$stime,$etime));//实操部分
		                               	echo "<li style=\"width: 130px;\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';
                          
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li  style="width: 130px;">赠送项目消耗(疗次)</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money=$value['ZSXH']?$value['ZSXH']:0;
		                               	echo "<li style=\"width: 130px;\">{$_money}</li>";
		                               	//$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                 
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li  style="width: 130px;">卡券内项目消耗(疗次)</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money=$value['KJXH']?$value['KJXH']:0;
		                               	echo "<li style=\"width: 130px;\">{$_money}</li>";
		                               	//$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                  
       
        echo              '<div class="pos_cz_tow_two_two table_list sjbb_ddkl_tablefoot">
                             <ul>
                                 <li class="li001" style="width: 130px;">合计</li>';
                                 foreach ((array)$mdids as $value) {
		                               	echo "<li style=\"width: 130px;\">{$_money_sum[$value['id']]}</li>";
		                               	
		                               }

         echo                '</ul>
                          </div>
                       </div><!--table_con_son!end-->
                       
                     </div><!--table_con!end-->
                   </div>';
	}
	/**
	 * 经营报表-现金收入
	 */
	public function JYBB_KLAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime=$times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$mdids=	ReportModel::model()->getMDIDS($md_ids);
		
		echo	'<div class="table_container">
		                     <!--table_con-->
		                     <div class="table_con pos_cz_qr_top_04" style="margin-right:0px;">
		                        
		                        <!--table_con_son-->
		                         <div class="table_con_son" style="min-width: 695px;">
		                          <!-- <div class="pos_cz_tow_top pos_cz_qr_02">赠送现金</div>-->
		                           <div class="table_title">
		                             <ul>
		                               <li>业务明细</li>
		                               ';
		                               foreach ((array)$mdids as $value) {
		                               	echo "<li>{$value['dept_name']}</li>";
		                               }
		                               
		                               
		echo                       ' </ul>
		                          </div>';

        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >到店总客流</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_KL($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['ZKL'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';
                          
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >到店有效客流</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_KL($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['YXKL'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                 
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >到店总人头</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_KL($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['ZRT'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                  
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >到店总有效人头</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_KL($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['YXRT'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>'; 
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >成交人头</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money=ReportModel::model()->getCJRT($value['id'],$stime,$etime);
                                      	
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';

         echo                '
                       </div><!--table_con_son!end-->
                       
                     </div><!--table_con!end-->
                   </div>';
	}
	/**
	 * 经营报表-现金收入
	 */
	public function JYBB_XJSRAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime=$times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$mdids=	ReportModel::model()->getMDIDS($md_ids);
		
		echo	'<div class="table_container">
		                     <!--table_con-->
		                     <div class="table_con pos_cz_qr_top_04" style="margin-right:0px;">
		                        
		                        <!--table_con_son-->
		                         <div class="table_con_son" style="min-width: 695px;">
		                          <!-- <div class="pos_cz_tow_top pos_cz_qr_02">赠送现金</div>-->
		                           <div class="table_title">
		                             <ul>
		                               <li>业务明细</li>
		                               ';
		                               foreach ((array)$mdids as $value) {
		                               	echo "<li>{$value['dept_name']}</li>";
		                               }
		                               
		                               
		echo                       ' </ul>
		                          </div>';

        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >购买项目/疗程</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_XJSR($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['XMXJ'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';
                          
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >购买产品</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_XJSR($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['CPXJ'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                 
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >购买卡券</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_XJSR($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['KJXJ'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';                  
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >会员充值</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_XJSR($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['CZXJ'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>'; 
        echo              '<div class="table_list clear">
                                    <ul>
                                      <li >现金还款</li>';
                                      foreach ((array)$mdids as $value) {
                                      	$_money='0.00';
                                      	$model= ReportModel::model()->getJYBB_XJSR($value['id'],$stime,$etime);
                                      	foreach ((array)$model as  $value2) {
                                      		if($value['id']==$value2['md_ids'])
                                      		{
                                      			$_money=$value2['HKXJ'];
                                      			break;
                                      		}
                                      	}
		                               	echo "<li  class=\"li002\">{$_money}</li>";
		                               	$_money_sum[$value['id']]+=$_money;
		                               }

        echo                        '</ul>
                            </div>';

        echo              '<div class="pos_cz_tow_two_two table_list sjbb_ddkl_tablefoot">
                             <ul>
                                 <li class="li001">合计</li>';
                                 foreach ((array)$mdids as $value) {
		                               	echo "<li  class=\"li002\">{$_money_sum[$value['id']]}</li>";
		                               	
		                               }

         echo                '</ul>
                          </div>
                       </div><!--table_con_son!end-->
                       
                     </div><!--table_con!end-->
                   </div>';
	}

	/**
	 * 经营报表-现金卡扣
	 */
	public function JYBB_KKYJAction(){
		$md_ids = $_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		$sql_md = " and id in ({$md_ids})";
		if($md_ids==0){
			$sql_md = "";
			$md_info[0] = '全连锁';
		}
		$comp_id = \Ivy::app()->user->comp_id;
		$dept_ids = \EmployDept::model()->findAll("comp_id={$comp_id} and status>0 {$sql_md}");

		$times = ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime = $times['stime']?$times['stime']:strtotime(date('Y-m-d'));
		$etime = $times['etime']?$times['etime']:strtotime(date('Y-m-d'));
		$gm_xm=0;$gm_cp=0;$gm_kq=0;$gm_hk=0;$zs_xm=0;$zs_cp=0;$zs_kq=0;
		foreach($dept_ids as $dept_id){
			$md_info[$dept_id['id']] = $dept_id['dept_name'];
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
		echo $this->view->assign(array(
			'light_nav'=>$this->url("index"),
			'md_info'=>$md_info,
			'data'=>$data,
		))->render();
	}

	/**
	 * 品相业绩报表
	 */
	public function PXYJBBAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
		$etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
		$xm=$_GET['xm'];
		$cp=$_GET['cp'];
		$xm_list=$cp_list=array();
		if ($xm) {
			$ProjectInfo=\ProjectInfo::model()->findAll("id in ({$xm})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$xm_list[$value['id']]=$value['name'];
				}
			}
		}
		if ($cp) {
			$ProjectInfo=\ProductInfo::model()->findAll("id in ({$cp})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$cp_list[$value['id']]=$value['name'];
				}
			}
		}
		$model= ReportModel::model()->getPXYJBB($md_ids,$stime,$etime,$xm,$cp);
		if($model)
			foreach ($model as  &$value) {
				$HK=array();
				$HK=ReportModel::model()->getPXYJBB_HK($md_ids,$stime,$etime,$value['id'],$value['type']);
				if($HK)
				{
					$value['XJSR']=$value['XJSR']+$HK['XJSR'];
					$value['XJZHKK']=$value['XJZHKK']+$HK['XJZHKK'];
				}
				$value=array_merge($value,ReportModel::model()->getPXYJBB_SC($md_ids,$stime,$etime,$value['id'],$value['type']));//实耗部分
			}
			
		$this->view->assign(array(
			'model'=>$model,
			'xm_list'=>$xm_list,
			'cp_list'=>$cp_list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 门店业绩报表
	 */
	public function MDYJAction()
	{
		$md_ids=$_GET['md_id'];
		if(empty($md_ids)){
			$md_ids=0;
			if($this->is_store)
				$md_ids = \Ivy::app()->user->dept_id;
		}
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
		$etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
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
		
		$this->view->assign(array(
			'model'=>$list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 客户购买分析报表
	 */
	public function KHGMFXAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
		$etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
		$xm=$_GET['xm'];
		$cp=$_GET['cp'];
		$xm_list=$cp_list=array();
		if ($xm) {
			$ProjectInfo=\ProjectInfo::model()->findAll("id in ({$xm})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$xm_list[$value['id']]=$value['name'];
				}
			}
		}
		if ($cp) {
			$ProjectInfo=\ProductInfo::model()->findAll("id in ({$cp})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$cp_list[$value['id']]=$value['name'];
				}
			}
		}
		$model= ReportModel::model()->getKHGMFX($md_ids,$stime,$etime,$xm,$cp);


		$this->view->assign(array(
			'model'=>$model,
			'xm_list'=>$xm_list,
			'cp_list'=>$cp_list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 客户实操分析报表
	 */
	public function KHSHFXAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$times=ReportModel::model()->getTimes($_GET['type'],$_GET['times']);
		$stime=$_GET['begin']?strtotime($_GET['begin']):strtotime(date('Y-m-d'));
		$etime=$_GET['end']?strtotime($_GET['end'])+24*3600:strtotime(date('Y-m-d'))+24*3600;
		$xm=$_GET['xm'];
		$xm_list=$cp_list=array();
		if ($xm) {
			$ProjectInfo=\ProjectInfo::model()->findAll("id in ({$xm})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$xm_list[$value['id']]=$value['name'];
				}
			}
		}
		$model= ReportModel::model()->getKHSHFX($md_ids,$stime,$etime,$xm);

		$this->view->assign(array(
			'model'=>$model,
			'xm_list'=>$xm_list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/**
	 * 项目覆盖分析
	 */
	public function XMFGFXAction()
	{
		$md_ids=$_GET['md_ids'];
		if(empty($md_ids))
			$md_ids=0;
		if (strpos($md_ids,',')) {
			$md_ids=explode(',', $md_ids);
		}
		$xm=$_GET['xm'];
		$xm_list=array();
		if ($xm) {
			$ProjectInfo=\ProjectInfo::model()->findAll("id in ({$xm})");
			if($ProjectInfo)
			{
				foreach ($ProjectInfo as $key => $value) {
					$xm_list[$value['id']]=$value['name'];
				}
			}
		}
		$model= ReportModel::model()->getXMFGFX($md_ids,$xm);

		$this->view->assign(array(
			'model'=>$model,
			'xm_list'=>$xm_list,
			'light_nav'=>$this->url("index")
			))->display();
	}
	/*************k*end****************/

	/*************guo******************/

	/**
	 * 现金流水报表
	 * @param [type] $id [description]
	 */
	public function XJLSAction($md_id,$month){
		if($month)
			$list = ReportModel::model()->getXJLS($md_id,$month);//现金流水
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}
	/**
	 * 现金流水报表-详细
	 * @param [type] $id [description]
	 */
	public function XJLS_XAction($md_id,$day){
		$list = ReportModel::model()->getXJLS_X($md_id,$day);//现金流水日详细
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}
	/**
	 * 人员业绩表表
	 * @param [type] $id [description]
	 */
	public function RYYJAction($md_id,$begin,$end,$user_ids){
		if($user_ids)
			$list = ReportModel::model()->getRYYJ($user_ids,$begin,$end);//人员业绩
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}
	/**
	 * 人员业绩表表
	 * @param [type] $id [description]
	 */
	public function RYYJ_XAction($md_id,$begin,$end,$user_id){
		if($user_id)
			$list = ReportModel::model()->getRYYJ_X($user_id,$begin,$end);//人员业绩
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}

	/**
	 * 品类业绩报表
	 * @param [type] $id [description]
	 */
	public function PLYJAction($md_id,$begin,$end,$xm,$cp){
		if($begin)
			$list = ReportModel::model()->getPLYJ($md_id,$begin,$end,$xm,$cp);
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}

	/**
	 * 退款分析报表
	 * @param [type] $id [description]
	 */
	public function TKFXAction($md_id,$month){
		if($month)
			$list = ReportModel::model()->getTKFX($md_id,$month);//退款分析
		$this->view->assign(array(
			'list'=>$list,
			'light_nav'=>$this->url("index")
		))->display();
	}

	/**
	 * 负债分析报表
	 * @param [type] $id [description]
	 */
	public function FZFXAction(){
		$md_id=0;
		if($this->is_store)
			$md_id=\Ivy::app()->user->dept_id;
		$month = !empty($_GET['month'])?$_GET['month']:date('Ym');
		$pre_month =  date("Ym",strtotime($month.'01')-100);
		//var_dump($month);
		//var_dump($pre_month);//die;
		if($month>date('Ym')){
			throw new CException("无未来数据！");
		}
		if($month==date('Ym')){
			$list = ReportModel::model()->getFZFX($md_id);//负债分析
		}else{
			$list = ReportModel::model()->getFZFX($md_id,$month);//负债分析
		}
		$list_pre = ReportModel::model()->getFZFX($md_id,$pre_month);//负债分析

		$list_kk = ReportModel::model()->getFZFX_KK($md_id,$month);//负债分析 _卡扣

		//var_dump($list);die;
		$this->view->assign(array(
			'list'=>$list,
			'list_pre'=>$list_pre,
			'list_kk'=>$list_kk,
			'light_nav'=>$this->url("index")
		))->display();
	}

	/*************guo end**************/

    /*************kevin-zdp******************/

    /**
     * 美容师服务分析表
     */
    public function FWFXAction(){
		$dept_id = $_GET['dept_id'];
		$year = !empty($_GET['year'])?$_GET['year']:date('Y');
		$month = !empty($_GET['month'])?$_GET['month']:date('m');
		$data = array();
		if(!empty($dept_id)){
			$mrs_list = \EmployUser::model()->getList(" AND position_id=7 AND dept_id={$dept_id}");
			$days = date('t',strtotime($year.'/'.$month.'/1'));
			for($i=1;$i<=$days;$i++){
				$data[$i]['DATE'] = date('Y-m-d',strtotime($year.'/'.$month.'/'.$i));
				$md_kl = ReportModel::model()->getDDKL($dept_id,strtotime($year.'/'.$month.'/'.$i),strtotime($year.'/'.$month.'/'.($i))+24*3600-1);
				$data[$i]['DDKL'] = empty($md_kl)?0:$md_kl[0]['ZKL'];
				$data[$i]['PJKL'] = $data[$i]['DDKL']/count($mrs_list)==0?0:sprintf("%.1f",$data[$i]['DDKL']/count($mrs_list));
				foreach($mrs_list as $mrs_id=>$mrs){
					$mrs_kl = ReportModel::model()->getMRSDDKL($mrs_id,$dept_id,strtotime($year.'/'.$month.'/'.$i),strtotime($year.'/'.$month.'/'.($i))+24*3600-1);
					$data[$i]['MRS'][$mrs_id] = empty($mrs_kl['ZKL'])?0:sprintf("%.1f",$mrs_kl['ZKL']);
				}
			}
		}
        $this->view->assign(array(
            'light_nav'=>$this->url('index'),
            'mrs_list'=>$mrs_list,
            'year'=>$year,
            'month'=>$month,
            'data'=>$data,
        ))->display();
    }

	/**
	 * 月客流分析表
	 */
	public function YKLFXAction(){
		$dept_ids = $_GET['dept_ids'];
		$year = !empty($_GET['year'])?$_GET['year']:date('Y');
		$month = !empty($_GET['month'])?$_GET['month']:date('m');
		$cu_types = \CustomerInfo::model()->getCuType();
		$data = array();
		$stime = strtotime($year.'/'.$month.'/1');
		$etime = strtotime('+1 month' ,$stime)-1;//月末
		$days = date('t',$stime);
		if(!empty($dept_ids)){
			foreach($cu_types as $cu_type){
				$data[$cu_type]['ZS'] = ReportModel::model()->getDAZS($cu_type,null,$dept_ids,$stime,$etime);
				$data[$cu_type]['YX'] = ReportModel::model()->getDAZS($cu_type,1,$dept_ids,$stime,$etime);
				$data[$cu_type]['JD'] = ReportModel::model()->getDAZS($cu_type,2,$dept_ids,$stime,$etime);
				$data[$cu_type]['SD'] = ReportModel::model()->getDAZS($cu_type,3,$dept_ids,$stime,$etime);
				$data[$cu_type]['DDRT'] = ReportModel::model()->getCuTypeDDRT($cu_type,$dept_ids,$stime,$etime);
				$data[$cu_type]['DDKL'] = ReportModel::model()->getCuTypeDDKL($cu_type,$dept_ids,$stime,$etime);
				$data[$cu_type]['CJRT'] = ReportModel::model()->getCuTypeCJRT($cu_type,$dept_ids,$stime,$etime);
			}
		}
		$this->view->assign(array(
			'light_nav'=>$this->url('index'),
			'dept_ids'=>$dept_ids,
			'year'=>$year,
			'month'=>$month,
			'cu_types'=>$cu_types,
			'data'=>$data,
			'days'=>$days,
		))->display();
	}

	/**
	 * 月客流分析表--详细
	 */
	public function YKLFX_XAction(){
		$type = $_GET['type'];
		$dept_ids = $_GET['dept_ids'];
		$year = !empty($_GET['year'])?$_GET['year']:date('Y');
		$month = !empty($_GET['month'])?$_GET['month']:date('m');
		$stime = strtotime($year.'/'.$month.'/1');//月初
		$etime = strtotime('+1 month' ,$stime)-1;//月末

		$data = array();
		$customers = ReportModel::model()->getDDCuType($type,$dept_ids,$stime,$etime);
		foreach($customers as $customer){
			$data[$customer['cu_id']]['cu_name'] = $customer['cu_name'];
			$data[$customer['cu_id']]['card_id'] = $customer['card_id'];
			$data[$customer['cu_id']]['cu_mrs'] = \EmployUser::model()->findByPk($customer['mrs'])->netname;
			$data[$customer['cu_id']]['cu_gw'] = \EmployUser::model()->findByPk($customer['gw'])->netname;
			$data[$customer['cu_id']]['by_ddcs'] = ReportModel::model()->getDDCS($customer['cu_id'],$stime,$etime);
			$BYCJ = ReportModel::model()->getCJCS($customer['cu_id'],$stime,$etime);
			$data[$customer['cu_id']]['by_cjcs'] = $BYCJ['num'];
			$data[$customer['cu_id']]['by_cjje'] = empty($BYCJ['price'])?0:$BYCJ['price'];
			$data[$customer['cu_id']]['sy_ddcs'] = ReportModel::model()->getDDCS($customer['cu_id'],strtotime('-1 month' ,$stime),$stime-1);
			$SYCJ = ReportModel::model()->getCJCS($customer['cu_id'],strtotime('-1 month' ,$stime),$stime-1);
			$data[$customer['cu_id']]['sy_cjcs'] = $SYCJ['num'];
			$data[$customer['cu_id']]['sy_cjje'] = empty($SYCJ['price'])?0:$SYCJ['price'];
		//var_dump($year.'/'.($month-2).'/1');die;
			$data[$customer['cu_id']]['qy_ddcs'] = ReportModel::model()->getDDCS($customer['cu_id'],strtotime('-2 month' ,$stime),strtotime('-1 month' ,$stime)-1);
			$QYCJ = ReportModel::model()->getCJCS($customer['cu_id'],strtotime('-2 month' ,$stime),strtotime('-1 month' ,$stime)-1);
			$data[$customer['cu_id']]['qy_cjcs'] = $QYCJ['num'];
			$data[$customer['cu_id']]['qy_cjje'] = empty($QYCJ['price'])?0:$QYCJ['price'];
		}

		$this->view->assign(array(
			'light_nav'=>$this->url('index'),
			'type'=>$type,
			'dept_ids'=>$dept_ids,
			'year'=>$year,
			'month'=>$month,
			'data'=>$data,
		))->display();
	}

	/**
	 * 新客分析表
	 */
	public function XKFXAction(){
		$type = $_GET['type'];
		$dept_ids = $_GET['dept_ids'];
		$year = !empty($_GET['year'])?$_GET['year']:date('Y');
		$month = !empty($_GET['month'])?$_GET['month']:date('m');
		$stime = strtotime($year.'/'.$month.'/1');
		$etime = strtotime('+1 month' ,$stime)-1;//月末
		$data = array();
		if(!empty($dept_ids)){
			foreach(explode(',',$dept_ids) as $dept_id){
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

		$this->view->assign(array(
			'light_nav'=>$this->url('index'),
			'type'=>$type,
			'dept_ids'=>$dept_ids,
			'year'=>$year,
			'month'=>$month,
			'data'=>$data,
		))->display();
	}

	/**
	 * 新客分析表--详细
	 */
	public function XKFX_XAction(){
		$dept_id = $_GET['dept_id'];
		$year = !empty($_GET['year'])?$_GET['year']:date('Y');
		$month = !empty($_GET['month'])?$_GET['month']:date('m');
		$stime = strtotime($year.'/'.$month.'/1');
		$etime = strtotime('+1 month' ,$stime)-1;//月末
		$data = array();
		$orders= ReportModel::model()->getXKKH($dept_id,$stime,$etime);
		foreach($orders as $order){
			if(!empty($order['order_id'])){
				$data[$order['order_id']] = $order;
				$data[$order['order_id']]['type_name'] = \OrderSale::model()->getType($order['type']);
				$data[$order['order_id']]['seller_name'] = ReportModel::model()->getOrderSellers($order['order_id']);
				if(\OrderSale::model()->findByPk($order['order_id'])->type==2){
					$data[$order['order_id']]['pro_info'] = '充值';
				}else{
					$data[$order['order_id']]['pro_info'] = ReportModel::model()->getOrderProInfo($order['order_id']);
				}
			}
		}
		$this->view->assign(array(
			'light_nav'=>$this->url('index'),
			'dept_name'=>\EmployDept::model()->findByPk($dept_id)->dept_name,
			'year'=>$year,
			'month'=>$month,
			'data'=>$data,
		))->display();
	}

	/**
	 * 客户年消费分析表
	 */
	public function KHNXFFXAction(){
		$dept_id = $_GET['dept_id'];
		$cu_type = $_GET['cu_type'];
		$xf_begin = $_GET['xf_begin'];
		$xf_end = $_GET['xf_end'];
		$sh_begin = $_GET['sh_begin'];
		$sh_end = $_GET['sh_end'];
		$year = $_GET['year'];
		$_data = "";$data = "";
		if(!empty($year)){
			$_data = ReportModel::model()->geyKHNFXFX($dept_id,$year,$cu_type,$xf_begin,$xf_end,$sh_begin,$sh_end);
		}
		foreach($_data['result'] as $item){
			$data[$item['cu_name']][$item['month']] = $item;
			$data[$item['cu_name']]['total'] = $_data['total'][$item['cu_id']];
		}
		$this->view->assign(array(
			'light_nav'=>$this->url('index'),
			'data'=>$data,
		))->display();
	}

    /*************kevin-zdp end**************/
	

}
