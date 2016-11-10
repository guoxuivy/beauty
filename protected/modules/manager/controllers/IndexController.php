<?php
namespace manager;
use Ivy\core\CException;
/**
 * 门店经理
 */
class IndexController extends \SController
{
	/**
	 * 门店经理首页
	 */
    public function indexAction(){
        $user_id=\Ivy::app()->user->id;
        $store_id=\Ivy::app()->user->dept_id;
        //今日销售业绩
        $d_stime=strtotime(date("Ymd"));
        $d_etime=$d_stime+24*3600;
        $xj_d = \report\ReportModel::model()->getJYBB_XJ($store_id,$d_stime,$d_etime);
        $sc_d = \report\ReportModel::model()->getXJSHYJ($store_id,$d_stime,$d_etime);
        $kk_d = \report\ReportModel::model()->getDDKL($store_id,$d_stime,$d_etime,true);//到店客流
        $cj_d = \report\ReportModel::model()->getCJRT($store_id,$d_stime,$d_etime);//成交人头

        //月数据
        $m_stime=strtotime(date("Ym")."01");
        $days = date('t', $stime);
        $m_etime = $m_stime+$days*24*3600;

        $xj_m = \report\ReportModel::model()->getJYBB_XJ($store_id,$m_stime,$m_etime);
        $sc_m = \report\ReportModel::model()->getXJSHYJ($store_id,$m_stime,$m_etime);
        $kk_m = \report\ReportModel::model()->getDDKL($store_id,$m_stime,$m_etime,true);//到店客流
        $cj_m = \report\ReportModel::model()->getCJRT($store_id,$m_stime,$m_etime);//成交人头


        $rc = \boss\TargetModel::model()->getTarget($user_id,date("Ym"));//目标值

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
            'data' => $this->getLineData(),
            'out'=>$this->getOutData(),
            'store_name'=>\EmployDept::model()->findByPk(\Ivy::app()->user->dept_id)->dept_name,
        ))->display();
    }

    //即将流失客户
    public function getOutData(){
        $out["A"] = \CustomerInfo::model()->outlistnum(\Ivy::app()->user->dept_id,"A");
        $out["B"] = \CustomerInfo::model()->outlistnum(\Ivy::app()->user->dept_id,"B");
        $out["C"] = \CustomerInfo::model()->outlistnum(\Ivy::app()->user->dept_id,"C");
        $out["D"] = \CustomerInfo::model()->outlistnum(\Ivy::app()->user->dept_id,"D");
        $out["E"] = \CustomerInfo::model()->outlistnum(\Ivy::app()->user->dept_id,"E");
        return $out;
    }

    //曲线图数据
    public function getLineData(){
        $store_id=\Ivy::app()->user->dept_id;
        $month=date('Ym');
        //每日数据列表
        $days=date('t', strtotime($month.'01'));//此月份的天数
        $i=1;
        while($i<=$days){
            $cur_day=$month.str_pad($i, 2, "0", STR_PAD_LEFT);
            $d_stime=strtotime($cur_day);
            $d_etime=$d_stime+24*3600;  
            $xj_d = \report\ReportModel::model()->getJYBB_XJ($store_id,$d_stime,$d_etime);//获取当日现金业绩
            $sc_d = \report\ReportModel::model()->getXJSHYJ($store_id,$d_stime,$d_etime);//获取当日实耗业绩
            $kk_d = \report\ReportModel::model()->getDDKL($store_id,$d_stime,$d_etime,true);//到店客流

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


    


}