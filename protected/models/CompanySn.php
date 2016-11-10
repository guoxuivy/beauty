<?php
/**
 * @Author: K
 * @Date:   2015-03-30 18:31:18
 * @Last Modified by:   K
 * @Last Modified time: 2015-04-29 16:40:47
 */
/**
 * 公司单据计数器
 */
use Ivy\core\ActiveRecord;
class CompanySn extends ActiveRecord
{
	public function tableName() {
		return 'company_sn';
	}


	public function nowNum($type="gm",$comp_id=null){
		if($comp_id==null) $comp_id=\Ivy::app()->user->comp_id;
		
		$map['comp_id']=$comp_id;
		$map['sn_type']=$type;
		$res = $this->where($map)->find(" '".date("Y-m-d")."' = DATE_FORMAT(last_time ,'%Y-%m-%d') ");

		if(!$res){
			$res = new CompanySn;
			$res->attributes=$map;
			$res->sn_count=0;
		}

		return $res;
	}
}	