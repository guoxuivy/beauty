<?php
namespace h5;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 数据报表 H5报表补充模型
 */
class SysModel extends Model{
	
	/**
	 * 到店客流详细
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
	public function getKLXX($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id={$md_id}";
		if($md_id==0){//全连锁
			$sqlw="";
		}

		$result = \PracticeOrder::model()
			->field("ci.name,ci.last_time")
			->join("customer_info ci ON ci.id = t.cu_id")
			->where("t.`status`=1 
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id} {$sqlw}")
			->group("t.cu_id")
			->limit(100)
			->findAll();
		return $result;
	}

	/**
	 * 成交人头      
	 * 销售单统计 人头去重复
	 * @var $md_id 门店ID
	 * stime 开始时间 etime结束时间 时间戳
	 **/
	public function getCJXX($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id={$md_id}";
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sqlw .= " AND t.is_init=0 "; //排除期初数据
		$sql="SELECT ci.name, SUM((t.cash+t.cash_pos+t.cash_tra+t.cash_other)) AS XJ
				FROM order_sale AS t
				LEFT JOIN `customer_info` ci ON ci.id = t.cu_id
				WHERE t.`pay_status`=1 
				AND t.`status`=1 
				AND t.`type` in (1,2) 
				AND t.pay_time BETWEEN {$stime} AND {$etime}
				AND t.comp_id={$comp_id}
				AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
				{$sqlw}
				GROUP BY t.cu_id";//, FROM_UNIXTIME(pay_time,'%Y%m%d')
		$result=$this->findAllBySql($sql);
		return $result;
	}



}