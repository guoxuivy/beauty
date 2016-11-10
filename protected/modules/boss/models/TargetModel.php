<?php
namespace boss;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 业绩目标相关模型
 */
class TargetModel extends Model
{
	/*************k********************/
	/**
	 * 门店业绩
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @param  [type] $xm    [description]
	 * @return [type]        [description]
	 */
	public function getTarget($user_id,$month)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" `comp_id`={$comp_id} AND `month`={$month} ";
        if($user_id!="0"){//全连锁
        	$sqlw.=" AND user_id = ({$user_id})";
        }
        $sql="SELECT SUM(`sale_target`) AS sale_target,SUM(`prac_target`) AS prac_target FROM `target_month` WHERE {$sqlw}";
		$result=$this->findBySql($sql);
        return $result;
	}
	
	
}