<?php
namespace report;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 数据报表 查询模型
 * 使用AR查询 报表查询瓶颈转移到缓存服务器 方便扩展，提高效率
 */
class ReportModel extends Model
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
	public function getMDYJ($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.`md_id`={$md_id}";//排除期初
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids,
						IF('{$md_id}'=0,'全连锁',ed.dept_name) AS dept_name,
						SUM(IF(t.`type`=2,  t.cash+t.cash_pos+t.cash_tra+t.cash_other, NULL)) AS XJSR1,
						SUM(IF(t.`type`=2, t.cash_card, NULL)) AS XJZHKK1,
						SUM(IF(t.`type`=2, NULL, NULL)) AS ZSZHKK1,
						COUNT(DISTINCT IF(t.`type`=2,t.cu_id, NULL)) CJRT1,
						SUM(IF(t.`type`=0, t.cash+t.cash_pos+t.cash_tra+t.cash_other, NULL)) AS XJSR2,
						SUM(IF(t.`type`=0, t.cash_card, NULL)) AS XJZHKK2,
						SUM(IF(t.`type`=0, NULL, NULL)) AS ZSZHKK2,
						COUNT(DISTINCT IF(t.`type`=0,t.cu_id, NULL)) CJRT2,
						SUM(IF(t.`type`=1, osd.cash, NULL)) AS XJSR3,
						SUM(IF(t.`type`=1, osd.card_cash, NULL)) AS XJZHKK3,
						SUM(IF(t.`type`=1, osd.card_gift, NULL)) AS ZSZHKK3,
						COUNT(DISTINCT IF((t.`type`=1 AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0),t.cu_id, NULL)) CJRT3
				FROM order_sale t 
				LEFT JOIN order_sale_detail osd ON t.id=osd.order_id AND osd.pro_type<>4  AND t.`type`<>2
				LEFT JOIN employ_dept ed ON t.md_id=ed.id
				WHERE t.pay_status=1 
				AND t.`status`>0
				AND t.is_init=0
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY md_ids";
		$result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 门店业绩退款部分
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
	public function getMDYJ_TK($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids,- SUM(IF(cc.`type`=1,ordm.money, NULL)) AS TKXJ,- SUM(IF(cc.`type`=2,ordm.money, NULL)) AS TKZS, COUNT(DISTINCT t.cu_id) AS HKRT
				FROM order_refund t
				LEFT JOIN order_refund_detail `ord` ON t.id=`ord`.refund_id
				LEFT JOIN order_refund_detail_money ordm ON `ord`.id=ordm.refund_detail_id
				LEFT JOIN customer_re_project crp ON `ord`.re_pro_id=crp.id
				LEFT JOIN company_capital cc ON ordm.capital_id=cc.id
				WHERE  t.`status`=1
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY md_ids";
		$result=$this->findBySql($sql);
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 门店业绩实操部分
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
	public function getMDYJ_SC($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND po.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND po.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }

        $sql="SELECT IF('{$md_id}'=0,0,po.md_id) AS md_ids,IFNULL(SUM(IF(osd.buy_type=1, truncate((osd.cash+osd.card_cash+osd.card_gift)/osd.num*t.use_num,2), NULL)),'0.00') AS GMXH,IFNULL(SUM(IF(osd.buy_type=2,t.use_num,NULL)),'0') AS ZSXH,IFNULL(SUM(IF(osd.buy_type=3,t.use_num,NULL)),'0') AS KJXH,COUNT(DISTINCT po.cu_id) AS SHRT
				FROM practice_order_detail t
				LEFT JOIN practice_order po ON po.id=t.por_id
				LEFT JOIN order_sale_detail osd ON t.detail_id=osd.id
				WHERE po.`status`=1 
				AND po.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND po.comp_id={$comp_id}
				{$sqlw}
				GROUP BY md_ids";
		$result=$this->findBySql($sql);
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 品项业绩报表
	 * @param  [type] $md_ids [门店]
	 * @param  [type] $stime  [开始时间戳]
	 * @param  [type] $etime  [结束时间戳]
	 * @param  [type] $xm     [项目]
	 * @param  [type] $cp     [产品]
	 * @return [type]         [description]
	 */
	public function getPXYJBB($md_id,$stime,$etime,$xm,$cp)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND os.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND os.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sqlw .= " AND os.`is_init`=0 ";//排除期初订单
        
        $sql="SELECT * FROM (SELECT t.id,1 AS `type`,t.name  FROM project_info t 
	        	WHERE 1
	        	".($xm?" AND t.id in ({$xm})":' AND t.id=0')."
	        	 )  tmp
				UNION
			  SELECT * FROM (SELECT t.id,2 AS `type`,t.name FROM product_info t
			  	WHERE 1
			  	".($cp?" AND t.id in ({$cp})":' AND t.id=0').") tmp
		";
		$result1=$this->findAllBySql($sql);
        $sql="SELECT * FROM (SELECT t.id,1 AS `type`,t.name, IFNULL(SUM(osd.cash),'0.00') AS XJSR, IFNULL(SUM(osd.card_cash),'0.00') AS XJZHKK, IFNULL(SUM(osd.card_gift),'0.00') AS ZSZHKK, COUNT(DISTINCT os.cu_id) AS CJRT
				FROM project_info t
				LEFT JOIN order_sale_detail osd ON osd.pro_id=t.id AND osd.pro_type=1
				JOIN order_sale os ON osd.order_id=os.id 
				WHERE t.status=1
				AND os.pay_status=1 
				AND os.`status`>0
				AND os.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND os.comp_id={$comp_id}
				{$sqlw}
				".($xm?" AND t.id in ({$xm})":' AND t.id=0')."
				GROUP BY t.id) tmp
				UNION
				SELECT * FROM (SELECT t.id,2 AS `type`,t.name, IFNULL(SUM(osd.cash),'0.00') AS XJSR, IFNULL(SUM(osd.card_cash),'0.00') AS XJZHKK, IFNULL(SUM(osd.card_gift),'0.00') AS ZSZHKK, COUNT(DISTINCT os.cu_id) AS CJRT
				FROM product_info t
				LEFT JOIN order_sale_detail osd ON osd.pro_id=t.id AND osd.pro_type=2
				LEFT JOIN order_sale os ON osd.order_id=os.id 
				WHERE t.status=1
				AND os.pay_status=1 
				AND os.`status`>0
				AND os.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND os.comp_id={$comp_id}
				{$sqlw}
				".($cp?" AND t.id in ({$cp})":' AND t.id=0')."
				GROUP BY t.id) tmp";
		$result=$this->findAllBySql($sql);
		foreach ((array)$result1 as  $value) {
			$is_has=false;
			foreach ((array)$result as  $value2) {
				if($value['type']==$value2['type'] && $value['id']==$value2['id'])
				{
					$is_has=true;
					break;
				}
			}
			if(!$is_has)
				array_push($result,array('id'=>$value['id'],'type'=>$value['type'],'name'=>$value['name'],'XJSR'=>'0','XJZHKK'=>'0.00','ZSZHKK'=>'0.00','CJRT'=>0));

		}
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 品项还款部分
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @param  [type] $xm    [description]
	 * @param  [type] $cp    [description]
	 * @return [type]        [description]
	 */
	public function getPXYJBB_HK($md_id,$stime,$etime,$pro,$type)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sqlw.=" AND osd.pro_id={$pro} AND osd.pro_type={$type}";
        $sql="SELECT t.id,osd.pro_type AS `type`, IFNULL(SUM(t.cash+t.cash_pos+t.cash_tra+t.cash_other),'0.00') AS XJSR, IFNULL(SUM(t.cash_card),'0.00') AS XJZHKK
					FROM order_sale t
					LEFT JOIN order_repay_detail `ord` on `ord`.order_id=t.id
					LEFT JOIN order_sale_detail osd ON `ord`.order_detail_id=osd.id
				WHERE t.type=0
				AND t.pay_status=1 
				AND t.`status`>0
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY osd.pro_id
				";
		$result=$this->findBySql($sql);
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 品项业绩报表中的实操部分
	 * @param  [type] $md_ids [description]
	 * @param  [type] $stime  [description]
	 * @param  [type] $etime  [description]
	 * @param  [type] $pro    [description]
	 * @param  [type] $type   [description]
	 * @return [type]         [description]
	 */
	public function getPXYJBB_SC($md_id,$stime,$etime,$pro,$type)
	{
		if($type==2)//产品无实操
			return array('GMXH'=>'0.00','ZSXH'=>'0.00','KJXH'=>'0.00','SHRT'=>0);
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND po.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND po.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT IFNULL(SUM(IF(osd.buy_type=1,t.pra_price,NULL)),'0.00') AS GMXH,IFNULL(SUM(IF(osd.buy_type=2,t.pra_price,NULL)),'0.00') AS ZSXH,IFNULL(SUM(IF(osd.buy_type=3,t.pra_price,NULL)),'0.00') AS KJXH,COUNT(DISTINCT po.cu_id) AS SHRT
				FROM practice_order_detail t
				LEFT JOIN practice_order po ON po.id=t.por_id
				LEFT JOIN order_sale_detail osd ON t.detail_id=osd.id
				WHERE po.`status`=1 
				AND po.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND po.comp_id={$comp_id}
				AND t.project_id={$pro}
				{$sqlw}
				";
		$result=$this->findBySql($sql);
        $result=$result?$result:array('GMXH'=>'0.00','ZSXH'=>'0.00','KJXH'=>'0.00','SHRT'=>0);
        return $result;
	}
	/**
	 * 时间处理函数
	 * @param  [type] $type  [description]
	 * @param  [type] $times [description]
	 * @return [type]        [description]
	 */
	public function getTimes($type,$times)
	{
		$stime=strtotime(date('Y-m-d'));
		$etime=strtotime(date('Y-m-d'))+24*3600;
		switch ($type) {
			case 'y':
				$stime=mktime(0,0,0,1,1,$times);
				$etime=mktime(23,59,59,12,31,$times);
				break;
			case 'm':
				$times=explode('-', $times);
				$stime=mktime(0,0,0,$times[1],1,$times[0]);
				$etime=mktime(23,59,59,$times[1],31,$times[0]);
				break;
			case 'd':
				$times=explode('-', $times);
				$stime=mktime(0,0,0,$times[1],$times[2],$times[0]);
				$etime=mktime(23,59,59,$times[1],$times[2],$times[0]);
				break;
			default:
				# code...
				break;
		}
		return array('stime'=>$stime,'etime'=>$etime);
	}
	/**
	 * 经营报表现金收入卡扣部分
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
	public function getJYBB_XJ($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sqlw .= " AND t.is_init=0 "; //排除期初

        $result = \OrderSale::model()
        		->field("IF({$md_id}=0,'0',t.md_id) AS md_ids, IF({$md_id}=0,'全连锁',ed.dept_name) AS dept_name, SUM(t.cash+t.cash_pos+t.cash_tra+t.cash_other) AS XJSR, SUM(t.cash_card) AS XJKK")
        		->join("employ_dept ed ON t.md_id=ed.id")
        		->where("t.pay_status=1")
        		->where("t.`status`>0")
        		->where("t.pay_time BETWEEN {$stime} AND {$etime}")
        		->where("t.comp_id={$comp_id} {$sqlw}")
        		->group("md_ids")
        		->findAll();

		// $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids, IF('{$md_id}'=0,'全连锁',ed.dept_name) AS dept_name, SUM(t.cash+t.cash_pos+t.cash_tra+t.cash_other) AS XJSR, SUM(t.cash_card) AS XJKK
		// 		FROM order_sale t
		// 		LEFT JOIN employ_dept ed ON t.md_id=ed.id
		// 		WHERE t.pay_status=1 
		// 		AND t.`status`>0
		// 		AND t.pay_time BETWEEN {$stime} 
		// 		AND {$etime}
		// 		AND t.comp_id={$comp_id}
		// 		{$sqlw}
		// 		GROUP BY md_ids";
		// $result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        return $result;	
	}
	/**
	 * 经营报表-现金收入
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
	public function getJYBB_XJSR($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sqlw .= " AND t.is_init=0 "; //排除期初

        $result = \OrderSale::model()
        		->field("IF('{$md_id}'=0,'0',t.md_id) AS md_ids, IF('{$md_id}'=0,'全连锁',ed.dept_name) AS dept_name,
					SUM(IF(osd.pro_type=1,osd.cash,'0')) AS XMXJ,
					SUM(IF(osd.pro_type=2,osd.cash,'0')) AS CPXJ,
					SUM(IF(osd.pro_type=3,osd.cash,'0')) AS KJXJ,
					SUM(IF(t.type=2,t.cash+t.cash_pos+t.cash_tra+t.cash_other,'0')) AS CZXJ,
					SUM(IF(t.type=0,t.cash+t.cash_pos+t.cash_tra+t.cash_other,'0')) AS HKXJ")
				->join("employ_dept ed ON t.md_id=ed.id")
				->join("order_sale_detail osd ON t.id=osd.order_id AND t.type not in (0,2)")
				->where("t.pay_status=1 
						AND t.`status`>0
						AND t.pay_time BETWEEN {$stime} 
						AND {$etime}
						AND t.comp_id={$comp_id}
						{$sqlw}")
				->group("md_ids")
				->findAll();
		// $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids, IF('{$md_id}'=0,'全连锁',ed.dept_name) AS dept_name,
		// 		SUM(IF(osd.pro_type=1,osd.cash,0)) AS XMXJ,
		// 		SUM(IF(osd.pro_type=2,osd.cash,0)) AS CPXJ,
		// 		SUM(IF(osd.pro_type=3,osd.cash,0)) AS KJXJ,
		// 		SUM(IF(t.type=2,t.cash+t.cash_pos+t.cash_tra+t.cash_other,0)) AS CZXJ,
		// 		SUM(IF(t.type=0,t.cash+t.cash_pos+t.cash_tra+t.cash_other,0)) AS HKXJ
		// 		FROM order_sale t
		// 		LEFT JOIN employ_dept ed ON t.md_id=ed.id
		// 		LEFT JOIN order_sale_detail osd ON t.id=osd.order_id

		// 		WHERE t.pay_status=1 
		// 		AND t.`status`>0
		// 		AND t.pay_time BETWEEN {$stime} 
		// 		AND {$etime}
		// 		AND t.comp_id={$comp_id}
		// 		{$sqlw}
		// 		GROUP BY md_ids";
		// $result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        return $result;	
	}
	/**
	 * 经营报表-客流总汇
	 * @param  [type] $md_id [description]
	 * @param  [type] $stime [description]
	 * @param  [type] $etime [description]
	 * @return [type]        [description]
	 */
    public function getJYBB_KL($md_id,$stime,$etime)
  	{
  		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids , 
        			IF('{$md_id}'=0,'全连锁',ed.dept_name) as dept_name, 
        			COUNT(t.`cu_id`) AS ZKL, 
        			COUNT(IF(cm.is_member=1,1, NULL)) AS YXKL, 
        			COUNT(DISTINCT t.`cu_id`) AS ZRT,
        			COUNT(DISTINCT  IF(cm.is_member=1,t.`cu_id`, NULL)) AS YXRT
				FROM practice_order t
				LEFT JOIN customer_info ci ON t.cu_id=ci.id
				LEFT JOIN config_membership cm ON ci.membership_id=cm.id
				LEFT JOIN employ_dept ed ON t.md_id=ed.id
				WHERE t.`status`=1 
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY md_ids";
        $result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        return $result;
  	}
	public function getMDIDS($md_id)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT id, dept_name
				FROM employ_dept t
				WHERE  t.`status`>0
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY t.id";
		$result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        if($md_id==0)
        	$result=array_merge(array(array('id'=>0,'dept_name'=>'全连锁')),$result);
        return $result;	
	}
	/**
	 * 现金实耗业绩
	 * @param  
	 * @return [type]        [description]
	 */
	public function getXJSHYJ($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }

		$result = \PracticeOrder::model()
				->field("IF('{$md_id}'=0,'0',t.md_id) AS md_ids ,TRUNCATE(SUM(osd.pay_price/osd.num*pod.use_num),'2') AS num")
				->join("practice_order_detail pod ON t.id=pod.por_id")
				->join("order_sale_detail osd ON pod.detail_id=osd.id")
				->where("t.`status`=1 
					AND t.pay_time BETWEEN {$stime} 
					AND {$etime}
					AND t.comp_id={$comp_id} {$sqlw}")
				->group("md_ids")
				->find();
		// $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids ,TRUNCATE(SUM(osd.pay_price/osd.num*pod.use_num),2) AS num 
		// 			FROM practice_order t
		// 			LEFT JOIN practice_order_detail pod ON t.id=pod.por_id
		// 			LEFT JOIN order_sale_detail osd ON pod.detail_id=osd.id
		// 			WHERE t.`status`=1 
		// 			AND t.pay_time BETWEEN {$stime} 
		// 			AND {$etime}
		// 			AND t.comp_id={$comp_id}
		// 			{$sqlw}
		// 			GROUP BY md_ids";
		// $result=$this->findBySql($sql);
        $result=$result?$result['num']:"0";
        return $result;
	}
	/**
     * 到店客流     
     * 实操单统计 
     * @var $md_id 门店ID
     * stime 开始时间 etime结束时间 时间戳
     **/
    public function getDDKL($md_id,$stime,$etime,$short=false)
  	{
  		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        if($short){
			$result = \PracticeOrder::model()
				->field("IF('{$md_id}'=0,'0',t.md_id) AS md_ids ,COUNT(t.`cu_id`) AS num")
				->where("t.`status`=1 
					AND t.pay_time BETWEEN {$stime} 
					AND {$etime}
					AND t.comp_id={$comp_id} {$sqlw}")
				->group("md_ids")
				->find();
			// $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids ,COUNT(t.`cu_id`) AS num 
			// 		FROM practice_order t
			// 		WHERE t.`status`=1 
			// 		AND t.pay_time BETWEEN {$stime} 
			// 		AND {$etime}
			// 		AND t.comp_id={$comp_id}
			// 		{$sqlw}
			// 		GROUP BY md_ids";
			// $result=$this->findBySql($sql);
	        $result=$result?$result['num']:"0";
	        return $result;
        }
        $sql="SELECT IF('{$md_id}'=0,0,t.md_id) AS md_ids , IF('{$md_id}'=0,'全连锁',ed.dept_name) as dept_name, COUNT(t.`cu_id`) AS ZKL, COUNT(IF(cm.is_member=1,1, NULL)) AS YXKL, COUNT(IF(ci.cu_type='A',1, NULL)) AS AKL, COUNT(IF(ci.cu_type='B',1, NULL)) AS BKL, COUNT(IF(ci.cu_type='C',1, NULL)) AS CKL, COUNT(IF(ci.cu_type='D',1, NULL)) AS DKL, COUNT(IF(ci.cu_type='E',1, NULL)) AS EKL
				FROM practice_order t
				LEFT JOIN customer_info ci ON t.cu_id=ci.id
				LEFT JOIN config_membership cm ON ci.membership_id=cm.id
				LEFT JOIN employ_dept ed ON t.md_id=ed.id
				WHERE t.`status`=1 
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY md_ids";
        $result=$this->findAllBySql($sql);
        $result=$result?$result:array();
        return $result;
  	}
	 /**
	 * 到店人头      
	 * 实操单统计 人头去重复
	 * @var $md_id 门店ID
	 * stime 开始时间 etime结束时间 时间戳
	 **/
	public function getDDRT($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id={$md_id}";
		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
		if(is_array($md_id)) {
			$md_id = implode(",",$md_id);
			$sqlw=" AND t.md_id in ({$md_id})";
			$md_id = str_replace(",","|",$md_id);
		}
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sql="SELECT COUNT(cu_id) as num
				FROM (
				SELECT t.cu_id, t.md_id
				FROM practice_order AS t
				WHERE t.`status`=1 
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				GROUP BY t.cu_id) tmp";//, FROM_UNIXTIME(pay_time,'%Y%m%d')
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
	}
	/**
	 * 成交人头      
	 * 销售单统计 人头去重复
	 * @var $md_id 门店ID
	 * stime 开始时间 etime结束时间 时间戳
	 **/
	public function getCJRT($md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id={$md_id}";
		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
		if(is_array($md_id)) {
			$md_id = implode(",",$md_id);
			$sqlw=" AND t.md_id in ({$md_id})";
			$md_id = str_replace(",","|",$md_id);
		}
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sqlw .= " AND t.is_init=0 "; //排除期初数据
		$sql="SELECT COUNT(cu_id) as num
				FROM (
				SELECT t.cu_id, t.md_id
				FROM order_sale AS t
				WHERE t.`pay_status`=1 
				AND t.`status`=1 
				AND t.`type` in (1,2) 
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
				{$sqlw}
				GROUP BY t.cu_id) tmp";//, FROM_UNIXTIME(pay_time,'%Y%m%d')
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
	}
	/**
	 * 总顾客数     
	 * 
	 * @var $md_id 门店ID
	 **/
	public function getZGKS($md_id)
  	{
  		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.store_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
		if(is_array($md_id)) {
			$md_id = implode(",",$md_id);
			$sqlw=" AND t.store_id in ({$md_id})";
			$md_id = str_replace(",","|",$md_id);
		}
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sql="SELECT count(t.id) AS num
				FROM customer_info AS t
				WHERE t.`status`>=1 
				AND t.company_id={$comp_id}
				{$sqlw}
				";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
  	}
  	/**
	 * 客户购买分析报表
	 * @param  [type] $md_ids [门店]
	 * @param  [type] $stime  [开始时间戳]
	 * @param  [type] $etime  [结束时间戳]
	 * @param  [type] $xm     [项目]
	 * @param  [type] $cp     [产品]
	 * @return [type]         [description]
	 */
	public function getKHGMFX($md_id,$stime,$etime,$xm,$cp)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND os.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND os.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sqlw .= " AND os.is_init=0 "; //排除期初数据
        $sql="SELECT * FROM (SELECT t.id,1 AS `type`,t.name  FROM project_info t 
	        	WHERE 1
	        	".($xm?" AND t.id in ({$xm})":' AND t.id=0')."
	        	 )  tmp
				UNION
			  SELECT * FROM (SELECT t.id,2 AS `type`,t.name FROM product_info t
			  	WHERE 1
			  	".($cp?" AND t.id in ({$cp})":' AND t.id=0').") tmp
		";
		$result1=$this->findAllBySql($sql);
        $sql="SELECT * FROM (SELECT t.id,1 AS `type`,t.name,COUNT(DISTINCT os.cu_id) AS CJRT, IFNULL(SUM(osd.pay_price),'0.00') AS CJJE,COUNT(DISTINCT if(ci.cu_type='A' and os.rh_id=0 ,ci.id,NULL) ) AS ACJRT,IFNULL(SUM(if(ci.cu_type='A' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS ACJJE,COUNT(DISTINCT if(ci.cu_type='B' and os.rh_id=0 ,ci.id,NULL) ) AS BCJRT,IFNULL(SUM(if(ci.cu_type='B' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS BCJJE,COUNT(DISTINCT if(ci.cu_type='C' and os.rh_id=0 ,ci.id,NULL) ) AS CCJRT,IFNULL(SUM(if(ci.cu_type='C' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS CCJJE,COUNT(DISTINCT if(ci.cu_type='D' and os.rh_id=0 ,ci.id,NULL) ) AS DCJRT,IFNULL(SUM(if(ci.cu_type='D' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS DCJJE,COUNT(DISTINCT if(ci.cu_type='E' and os.rh_id=0 ,ci.id,NULL) ) AS ECJRT,IFNULL(SUM(if(ci.cu_type='E' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS ECJJE,COUNT(DISTINCT if(ci.cu_type='未分类' and os.rh_id=0 ,ci.id,NULL) ) AS WCJRT,IFNULL(SUM(if(ci.cu_type='未分类' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS WCJJE,COUNT(DISTINCT if(os.rh_id <> 0 ,ci.id,NULL) ) AS XCJRT,IFNULL(SUM(if(os.rh_id <> 0,osd.pay_price,NULL)),'0.00') AS XCJJE
           		FROM project_info t
				LEFT JOIN order_sale_detail osd ON osd.pro_id=t.id AND osd.pro_type=1
				LEFT JOIN order_sale os ON osd.order_id=os.id
				LEFT JOIN customer_info ci ON os.cu_id=ci.id
				WHERE t.status=1
				AND os.pay_status=1 
				AND os.`status`>0
				AND os.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND os.comp_id={$comp_id}
				{$sqlw}
				".($xm?" AND t.id in ({$xm})":' AND t.id=0')."
				GROUP BY t.id) tmp
				UNION
				SELECT * FROM (SELECT t.id,2 AS `type`,t.name,COUNT(DISTINCT os.cu_id) AS CJRT, IFNULL(SUM(osd.pay_price),'0.00') AS CJJE,COUNT(DISTINCT if(ci.cu_type='A' and os.rh_id=0 ,ci.id,NULL) ) AS ACJRT,IFNULL(SUM(if(ci.cu_type='A' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS ACJJE,COUNT(DISTINCT if(ci.cu_type='B' and os.rh_id=0 ,ci.id,NULL) ) AS BCJRT,IFNULL(SUM(if(ci.cu_type='B' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS BCJJE,COUNT(DISTINCT if(ci.cu_type='C' and os.rh_id=0 ,ci.id,NULL) ) AS CCJRT,IFNULL(SUM(if(ci.cu_type='C' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS CCJJE,COUNT(DISTINCT if(ci.cu_type='D' and os.rh_id=0 ,ci.id,NULL) ) AS DCJRT,IFNULL(SUM(if(ci.cu_type='D' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS DCJJE,COUNT(DISTINCT if(ci.cu_type='E' and os.rh_id=0 ,ci.id,NULL) ) AS ECJRT,IFNULL(SUM(if(ci.cu_type='E' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS ECJJE,COUNT(DISTINCT if(ci.cu_type='未分类' and os.rh_id=0 ,ci.id,NULL) ) AS WCJRT,IFNULL(SUM(if(ci.cu_type='未分类' and os.rh_id=0,osd.pay_price,NULL)),'0.00') AS WCJJE,COUNT(DISTINCT if(os.rh_id <> 0 ,ci.id,NULL) ) AS XCJRT,IFNULL(SUM(if(os.rh_id <> 0,osd.pay_price,NULL)),'0.00') AS XCJJE
				FROM product_info t
				LEFT JOIN order_sale_detail osd ON osd.pro_id=t.id AND osd.pro_type=2
				LEFT JOIN order_sale os ON osd.order_id=os.id 
				LEFT JOIN customer_info ci ON os.cu_id=ci.id
				WHERE t.status=1
				AND os.pay_status=1 
				AND os.`status`>0
				AND os.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND os.comp_id={$comp_id}
				{$sqlw}
				".($cp?" AND t.id in ({$cp})":' AND t.id=0')."
				GROUP BY t.id) tmp";
		$result=$this->findAllBySql($sql);
		foreach ((array)$result1 as  $value) {
			$is_has=false;
			foreach ((array)$result as  $value2) {
				if($value['type']==$value2['type'] && $value['id']==$value2['id'])
				{
					$is_has=true;
					break;
				}
			}
			if(!$is_has)
				array_push($result,array('id'=>$value['id'],'type'=>$value['type'],'name'=>$value['name'],'CJRT'=>'0','CJJE'=>'0.00'
					,'ACJRT'=>'0','ACJJE'=>'0.00'
					,'BCJRT'=>'0','BCJJE'=>'0.00'
					,'CCJRT'=>'0','CCJJE'=>'0.00'
					,'DCJRT'=>'0','DCJJE'=>'0.00'
					,'ECJRT'=>'0','ECJJE'=>'0.00'
					,'WCJRT'=>'0','WCJJE'=>'0.00'
					,'XCJRT'=>'0','XCJJE'=>'0.00'
					,));

		}
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 客户实耗分析报表
	 * @param  [type] $md_ids [门店]
	 * @param  [type] $stime  [开始时间戳]
	 * @param  [type] $etime  [结束时间戳]
	 * @param  [type] $xm     [项目]
	 * @return [type]         [description]
	 */
	public function getKHSHFX($md_id,$stime,$etime,$xm)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.md_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND t.md_id in ({$md_id})";
        	$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT t.id,t.name  FROM project_info t 
	        	WHERE 1
	        	".($xm?" AND t.id in ({$xm})":' AND t.id=0')." 
		";
		$result1=$this->findAllBySql($sql);
        $sql="SELECT `pi`.id,`pi`.name
        		, COUNT(DISTINCT t.cu_id) AS SCRT, FORMAT(IFNULL(SUM(osd.price*osd.rebate/100/osd.num*pod.use_num),'0.00'),2) AS SCJE
				, COUNT(DISTINCT IF(ci.cu_type='A' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='A' AND osd.buy_type=3,t.cu_id, NULL)) AS ASCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='A' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='A' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS ASCJE
				, COUNT(DISTINCT IF(ci.cu_type='B' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='B' AND osd.buy_type=3,t.cu_id, NULL)) AS BSCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='B' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='B' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS BSCJE
				, COUNT(DISTINCT IF(ci.cu_type='C' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='C' AND osd.buy_type=3,t.cu_id, NULL)) AS CSCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='C' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='C' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS CSCJE
				, COUNT(DISTINCT IF(ci.cu_type='D' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='D' AND osd.buy_type=3,t.cu_id, NULL)) AS DSCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='D' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='D' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS DSCJE
				, COUNT(DISTINCT IF(ci.cu_type='E' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='E' AND osd.buy_type=3,t.cu_id, NULL)) AS ESCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='E' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='E' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS ESCJE
				, COUNT(DISTINCT IF(ci.cu_type='未分类' AND os.rh_id=0,t.cu_id, NULL))+COUNT(DISTINCT IF(ci.cu_type='未分类' AND osd.buy_type=3,t.cu_id, NULL)) AS WSCRT
				, FORMAT(IFNULL(SUM(IF(ci.cu_type='未分类' AND os.rh_id=0,osd.price*osd.rebate/100/osd.num*pod.use_num, 0))+SUM(IF(ci.cu_type='未分类' AND osd.buy_type=3,osd.price*osd.rebate/100/osd.num*pod.use_num, 0)),'0.00'),2) AS WSCJE
				, COUNT(DISTINCT IF(os.rh_id <> 0,t.cu_id, NULL)) AS XSCRT
				, FORMAT(IFNULL(SUM(IF(os.rh_id <> 0,osd.price*osd.rebate/100/osd.num*pod.use_num, NULL)),'0.00'),2) AS XSCJE
				FROM practice_order t
				LEFT JOIN customer_info ci ON t.cu_id=ci.id
				LEFT JOIN practice_order_detail pod ON t.id=pod.por_id
				LEFT JOIN order_sale_detail osd ON pod.detail_id=osd.id AND osd.pro_type=1
				LEFT JOIN order_sale os ON osd.order_id=os.id AND osd.buy_type<>3
				LEFT JOIN project_info `pi` ON osd.pro_id=`pi`.id
				
				WHERE `pi`.status=1 
				AND t.`status`>0
				AND t.pay_time BETWEEN {$stime} 
				AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				".($xm?" AND `pi`.id in ({$xm})":' AND `pi`.id=0')."
				GROUP BY `pi`.id";
		$result=$this->findAllBySql($sql);
		foreach ((array)$result1 as  $value) {
			$is_has=false;
			foreach ((array)$result as  $value2) {
				if($value['id']==$value2['id'])
				{
					$is_has=true;
					break;
				}
			}
			if(!$is_has)
				array_push($result,array('id'=>$value['id'],'name'=>$value['name']
					,'SCRT'  =>'0','SCJE'=>'0.00'
					,'ASCRT' =>'0','ASCJE'=>'0.00'
					,'BSCRT' =>'0','BSCJE'=>'0.00'
					,'CSCRT' =>'0','CSCJE'=>'0.00'
					,'DSCRT' =>'0','DSCJE'=>'0.00'
					,'ESCRT' =>'0','ESCJE'=>'0.00'
					,'WSCRT' =>'0','WSCJE'=>'0.00'
					,'XSCRT' =>'0','XSCJE'=>'0.00'
					,));

		}
        $result=$result?$result:array();
        return $result;
	}
	/**
	 * 项目覆盖分析
	 * @param  [type] $md_ids [门店]
	 * @param  [type] $xm     [项目]
	 * @return [type]         [description]
	 */
	public function getXMFGFX($md_id,$xm)
	{
		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND ci.store_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
        if(is_array($md_id)) {
        	$md_id = implode(",",$md_id);
        	$sqlw=" AND ci.store_id in ({$md_id})";
        	//$md_id = str_replace(",","|",$md_id);
        }
        if($md_id==0){//全连锁
        	$sqlw="";
        }
        $sql="SELECT t.id,pc_top.name as PL,t.name as PX,pc.name as SX
				FROM project_info AS t
				LEFT JOIN pro_cate pc ON t.cate=pc.id
				LEFT JOIN pro_cate pc_top ON pc.fid=pc_top.id
	        	WHERE 1
	        	".($xm?" AND t.id in ({$xm})":' AND t.id=0')." 
		";
		$result1=$this->findAllBySql($sql);
        $sql="SELECT t.id,count(DISTINCT crp.cu_id) as YXFGRS
				FROM project_info AS t
				LEFT JOIN customer_re_project crp ON t.id=crp.project_id
				LEFT JOIN customer_info ci ON crp.cu_id=ci.id
				LEFT JOIN config_membership cm ON ci.membership_id=cm.id
				WHERE `t`.status=1 
				AND ci.status=1
				AND cm.is_member=1
				AND crp.re_num <> 0
				AND ci.company_id={$comp_id}
				{$sqlw}
				".($xm?" AND `t`.id in ({$xm})":' AND `t`.id=0')."
				GROUP BY `t`.id";
		$result=$this->findAllBySql($sql);
		$YXKHS=$this->getYXKHS($md_id);
		foreach ((array)$result1 as  $value) {
			$is_has=false;
			foreach ((array)$result as  $value2) {
				if($value['id']==$value2['id'])
				{
					$is_has=true;
					break;
				}
			}
			if(!$is_has)
				array_push($result,array('id'=>$value['id'],'PL'=>$value['PL'],'PX'=>$value['PX'],'SX'=>$value['SX']
					,'YXFGRS'  =>'0',
					));

		}
		foreach ($result as &$value) {
			foreach ((array)$result1 as  $value2) {
				if($value['id']==$value2['id'])
				{
					$value['id']= $value2['id'];
					$value['PL']= $value2['PL'];
					$value['PX']= $value2['PX'];
					$value['SX']= $value2['SX'];
					break;
				}
			}
			$value['YXKHS']=$YXKHS;
			$value['YXFGL']=$value['YXKHS']?sprintf("%.2f",$value['YXFGRS']/$value['YXKHS']*100):0;
			$value['YXFGL']=$value['YXFGL'].'%';
		}
        $result=$result?$result:array();

        return $result;
	}
	/**
	 * 有效客户数     
	 * 
	 * @var $md_id 门店ID
	 **/
	public function getYXKHS($md_id)
  	{
  		$comp_id=\Ivy::app()->user->comp_id;
  		$sqlw=" AND t.store_id={$md_id}";
  		if (strpos($md_id,',')) {
			$md_id=explode(',', $md_id);
		}
		if(is_array($md_id)) {
			$md_id = implode(",",$md_id);
			$sqlw=" AND t.store_id in ({$md_id})";
			$md_id = str_replace(",","|",$md_id);
		}
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sql="SELECT count(t.id) AS num
				FROM customer_info AS t
				LEFT JOIN config_membership cm ON t.membership_id=cm.id
				WHERE t.`status`=1 
				AND t.company_id={$comp_id}
				AND cm.is_member=1
				{$sqlw}
				";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
  	}
  	/*************k*end****************/

	/**
	 * 查询时间处理
	 * @param  string $type [description]
	 * @return [type]       [description]
	 */
	public function sTime($type="1",$format=true){
		//当点9点时间戳
		$day_s = strtotime(date('Ymd'));
		$map=array();
		switch ($type) {
			case '1': //当天时间段
				$map['begin'] = $day_s;
				$map['end'] = $day_s+3600*24;//以当天24点计算
				break;
			case '2'://当周时间段
				$d = 6;		
				$map['begin'] = $day_s - $d*3600*24;	//7天前开始时间
				$map['end'] = $day_s+3600*24;	//结束时间
				break;
			case '3'://当月时间段
				$d = 30;		
				$map['begin'] = $day_s - $d*3600*24;	//30天前开始时间
				$map['end'] = $day_s+3600*24;	//结束时间
				break;
			default:
				break;
		}
		if(empty($map)) return false; 
		if($format){
			$map['begin']=date('Y-m-d H:i:s',$map['begin']);
			$map['end']=date('Y-m-d H:i:s',$map['end']);
		}
		return $map;
	}

	/**
	 * 人员业绩统计 
	 * @var $user_ids 人员IDs
	 * stime 开始时间 etime结束时间  20140901
	 **/
	public function getRYYJ($user_ids,$begin,$end)
	{
		//销售
		$comp_id=\Ivy::app()->user->comp_id;
		$stime=strtotime($begin);
		$etime=strtotime($end)+24*60*60;
		$map['t.pay_time']=array(array('gt',$stime),array('lt',$etime));
		$map['t.status']=array("eq",1);
		$map['t.pay_status']=array("eq",1);
		$map['t.is_init']=array("eq",0);//排除期初
		$map['t.comp_id']=array("eq",$comp_id);
		$map['oeu.rate']=array("gt",0);
		$map['eu.id']=array("in",$user_ids);
		$result=array();

		$list = \OrderSale::model()
			->field("eu.id as uid, ed.dept_name, eu.netname, 
				SUM(t.cash_card*oeu.rate/100) AS xjkk ,
				SUM( (t.cash + t.cash_pos + t.cash_tra + t.cash_other)*oeu.rate/100 ) AS xj ")
			->join("employ_dept ed ON t.md_id=ed.id")
			->join("order_effect_user oeu ON oeu.order_id=t.id")
			->join("employ_user eu ON eu.id=oeu.user_id")
			->where($map)
			->group("eu.id")
			->findAll();
		if($list){
			foreach ($list as $value) {
				$result[$value['uid']]=$value;
			}
		}
		//实操
		foreach (explode(",", $user_ids) as $uid) {
			
			$sql="SELECT tmp.id,tmp.netname,tmp.dept_name, SUM(tmp.pra_price) AS bzxh, SUM(tmp.price*use_num*rate) AS scxh FROM(
				SELECT eu.id,eu.netname,ed.dept_name, pod.project_id,pod.pra_price, IF(osd.buy_type='1',pi.price,0) AS price,pod.use_num, osd.buy_type AS buy_type, peu.rate/100 AS rate
				FROM practice_order t
				LEFT JOIN practice_order_detail pod ON t.`id`=pod.`por_id`
        		LEFT JOIN practice_effect_user peu ON pod.`id` = peu.`detail_id` 
				LEFT JOIN project_info `pi` ON pi.`id`=pod.`project_id`
				LEFT JOIN order_sale_detail osd ON osd.`id`=pod.`detail_id`
				LEFT JOIN employ_user eu ON eu.`id`=peu.`user_id`
				LEFT JOIN employ_dept ed ON ed.`id`=eu.`dept_id`
				WHERE t.`status`=1
				AND t.`pay_time` BETWEEN {$stime} AND {$etime}
				AND t.`comp_id`={$comp_id}
				AND peu.`user_id` = {$uid} 
				AND peu.`rate` > 0
			) tmp ";
			$res=$this->findBySql($sql);
			if($res&&$res['id']){
				$result[$uid]['uid']=$res['id'];
				$result[$uid]['netname']=$res['netname'];
				$result[$uid]['dept_name']=$res['dept_name'];
				$result[$uid]['bzxh']=$res?$res['bzxh']:0;
				$result[$uid]['scxh']=$res?round($res['scxh'],2):0;
			}
		}

		return $result;
	}

	/**
	 * 人员业绩_详细统计 
	 * @var $user_id 人员ID
	 * stime 开始时间 etime结束时间  20140901
	 **/
	public function getRYYJ_X($user_id,$begin,$end)
	{
		//销售
		$comp_id=\Ivy::app()->user->comp_id;
		$stime=strtotime($begin);
		$etime=strtotime($end)+24*60*60;
		$map['t.pay_time']=array(array('gt',$stime),array('lt',$etime));
		$map['t.status']=array("eq",1);
		$map['t.pay_status']=array("eq",1);
		$map['t.is_init']=array("eq",0);//排除期初
		$map['t.comp_id']=array("eq",$comp_id);
		$map['t.type']=array("in","0,1,2"); //购买'充值‘还款'
		$map['oeu.rate']=array("gt",0);
		$map['oeu.user_id']=array("eq",$user_id);

		$result=array();

		$result['sale'] = \OrderSale::model()
			->field("ci.name ,t.sn,t.id,t.type,t.pay_time,
				SUM(t.cash_card*oeu.rate/100) AS xjkk ,
				SUM((t.cash + t.cash_pos + t.cash_tra + t.cash_other)*oeu.rate/100) AS xj ")
			->join("order_effect_user oeu ON oeu.order_id=t.id")
			->join("customer_info ci ON ci.id=t.cu_id")
			->where($map)
			->group("t.id")
			->findAll();
		
		//实操单据
		unset($map);
		$map['t.pay_time']=array(array('gt',$stime),array('lt',$etime));
		$map['t.status']=array("eq","1");
		$map['t.comp_id']=array("eq",$comp_id);
		$map['peu.rate']=array("gt",0);
		$map['peu.user_id']=array("eq",$user_id);
		$result['practice'] = \PracticeOrder::model()
			->field("t.id,t.sn,ci.name,t.pay_time, SUM(pod.pra_price) AS bzxh,  SUM(IF(osd.buy_type='1',pi.price,0)*pod.use_num*peu.rate/100) AS scxh ")
			->join("practice_order_detail pod ON t.`id`=pod.`por_id`")
			->join("practice_effect_user peu ON pod.`id` = peu.`detail_id`")
			->join("project_info `pi` ON pi.`id`=pod.`project_id`")
			->join("order_sale_detail osd ON osd.`id`=pod.`detail_id`")
			->join("customer_info ci ON ci.id=t.cu_id")
			->where($map)
			->group("t.id")
			->findAll();
		return $result;
	}

	/**
	 * 现金流水报表  按月统计（日分组）
	 * @var $md_id 门店ID
	 * @var $month 201405
	 **/
	public function getXJLS($md_id,$month){
		$map['t.md_id']=array("in",$md_id);
		if($md_id=="0"){//全连锁
			unset($map['t.md_id']);
		}
		$map['t.status']=array("eq","1");
		$map['t.pay_status']=array("eq","1");
		$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);
		$map['t.is_init']=array("eq","0"); //排除期初数据

		$days=date('t', strtotime($month."01"));	//此月份的天数
		$result=array();
		$config=\CompanyInfo::getConfig('payee');	//现金收款方式配置

		for ($i=1; $i <= $days; $i++) { 
			$tmp=array();
			$day = $month.($i<10?"0".$i:$i);
			$stime=strtotime($day);
			$etime=$stime+24*60*60;

			$map['t.pay_time']=array(array('gt',$stime),array('lt',$etime));
			//获取当天现金流水合计 购买、充值、还款
			$rows=\OrderSale::model()
			->field("t.cash, t.cash_pos, t.cash_tra, t.cash_other, t.pos_type, t.tra_type, t.other_type")
			->where($map)
			->findAll();
			$tmp=$this->_XJ($config,$rows);
			$tmp['day']=$day;
			$result[]=$tmp;
		}
		return $result;
	}
	/**
	 * 现金流水报表 -日详细 
	 * @var $md_id 门店ID
	 * @var $day 20140509
	 **/
	public function getXJLS_X($md_id,$day){
		$map['t.md_id']=array("in",$md_id);
		if($md_id=="0"){//全连锁
			unset($map['t.md_id']);
		}
		$map['t.status']=array("eq","1");
		$map['t.pay_status']=array("eq","1");
		$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);

		$result=array();
		$config=\CompanyInfo::getConfig('payee');	//现金收款方式配置

		$stime=strtotime($day);
		$etime=$stime+24*60*60;
		$map['t.pay_time']=array(array('gt',$stime),array('lt',$etime));
		//获取当天现金流水合计 购买、充值、还款
		$rows=\OrderSale::model()
		->field("t.id,ed.dept_name,ci.id AS cu_id,ci.name,t.type,t.cash, t.cash_pos, t.cash_tra, t.cash_other, t.pos_type, t.tra_type, t.other_type")
		->join("employ_dept ed ON t.md_id=ed.id")
		->join("customer_info ci ON t.cu_id=ci.id")
		->where($map)
		->findAll();
		$result=array();
		foreach ($rows as $key => $row) {
			$tmp				= $this->_XJ($config,array($row));
			$tmp['dept_name']	= $row['dept_name'];
			$tmp['name']		= $row['name'];
			$tmp['cu_id']		= $row['cu_id'];
			$tmp['type']		= \OrderSale::getType($row['type']);
			$tmp['id']			= $row['id'];
			$result[]	= $tmp;
		}
		return $result;
	}
	protected function _XJ(&$config,$rows){
		if(empty($rows)) return array();
		$res=array('xj'=>0);
		foreach ($rows as $row) {
			$res['xj']+=$row['cash'];
			$pos_key=$config['post']['has'][$row['pos_type']];
			if($pos_key)
				$res[$pos_key] += $row['cash_pos'];
			$tra_key=$config['bank']['has'][$row['tra_type']];
			if($tra_key)
				$res[$tra_key] += $row['cash_tra'];
			$other_key=$config['other']['has'][$row['other_type']];
			if($other_key)
				$res[$other_key] += $row['cash_other'];
		}
		return $res;
	}
	/**
	 * 品类业绩
	 * @param $md_id 
	 * @param $begin
	 * @param $end
	 * @return array
	 */
	public function getPLYJ($md_id,$begin,$end,$xm,$cp){
		$map['t.md_id']=array("in",$md_id);
		if($md_id=="0"){//全连锁
			unset($map['t.md_id']);
		}
		$map['t.type']=array("eq","1");
		$map['t.status']=array("eq","1");
		$map['t.pay_status']=array("eq","1");
		$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);
		

		$begin=strtotime($begin);
		$end=strtotime($end)+24*60*60;
		$map['t.pay_time']=array(array('gt',$begin),array('lt',$end));
		$result_xm=array();
		//项目购买
		$map['osd.pro_type']=array("eq","1");
		if(!empty($xm)){
			$list = \OrderSale::model()->field("pct.id,pct.name,SUM(osd.cash) as cash,SUM(osd.card_cash) as card,SUM(osd.card_gift) as gift,COUNT(*) as num")
			->join("order_sale_detail osd on osd.order_id=t.id")
			->join("project_info `pi` on pi.id=osd.pro_id")
			->join("pro_cate pc on pc.id=pi.cate")
			->join("pro_cate pct on pct.id=pc.fid")
			->where($map)
			->where("pct.id in ($xm) AND t.is_init=0")
			->group("pct.id")
			->findAll();
		}
		foreach ($list as $row) {
			$result_xm[$row['id']]=$row;
		}
		//项目还款
		$map['t.type']=array("eq","0");
		if(!empty($xm)){
			$list_hk = \OrderSale::model()->field("pct.id,pct.name,SUM( IF(ord.type = 1, ord.money, '0') ) as cash, SUM( IF(ord.type = 2, ord.money, '0') ) as card")
			->join("order_repay_detail ord on ord.order_id=t.id")
			->join("order_sale_detail osd on osd.id=ord.order_detail_id")
			->join("project_info `pi` on pi.id=osd.pro_id")
			->join("pro_cate pc on pc.id=pi.cate")
			->join("pro_cate pct on pct.id=pc.fid")
			->where($map)
			->where("pct.id in ($xm) AND t.is_init=0")
			->group("pct.id")
			->findAll();
		}
		foreach ($list_hk as $row) {
			if(isset($result_xm[$row['id']])){
				$result_xm[$row['id']]['cash']+=$row["cash"];
				$result_xm[$row['id']]['card']+=$row["card"];
			}else{
				$result_xm[$row['id']]=$row;
			}
		}
		//项目实操
		unset($map['osd.pro_type'],$map['t.pay_status']);
		foreach (explode(",", $xm) as $p_id) {
			//单一分类实操数据
			$map['pct.id']=array("eq",$p_id);
			$map['t.type']=array("eq","1");
			$plist = \PracticeOrder::model()->field("pct.id,pct.name,osd.buy_type,SUM(pod.pra_price) as money,COUNT(*) as num ")
			->join("practice_order_detail pod on pod.por_id=t.id")
			->join("order_sale_detail osd on osd.id=pod.detail_id")
			->join("project_info `pi` on pi.id=pod.project_id")
			->join("pro_cate pc on pc.id=pi.cate")
			->join("pro_cate pct on pct.id=pc.fid")
			->where($map)
			->group("osd.buy_type")
			->findAll();
			if($plist){
				foreach ($plist as $row) {
					$result_xm[$p_id]['practice'][]=$row;
					$result_xm[$p_id]['name']=$row['name'];
					$result_xm[$p_id]['id']=$row['id'];
					$result_xm[$p_id]['practice_num']+=$row['num'];
				}
			}
		}
		//项目完毕
		//产品购买
		$result_cp=array();
		if($cp){
			unset($map,$list);
			$map['t.md_id']=array("in",$md_id);
			if($md_id=="0"){//全连锁
				unset($map['t.md_id']);
			}
			$map['t.type']=array("eq","1");
			$map['t.status']=array("eq","1");
			$map['t.pay_status']=array("eq","1");
			$map['t.comp_id']=array("eq",\Ivy::app()->user->comp_id);
			$map['t.pay_time']=array(array('gt',$begin),array('lt',$end));

			//产品购买
			$map['osd.pro_type']=array("eq","2");
				$list = \OrderSale::model()->field("pct.id,pct.name,SUM(osd.cash) as cash,SUM(osd.card_cash) as card,SUM(osd.card_gift) as gift,COUNT(*) as num")
				->join("order_sale_detail osd on osd.order_id=t.id")
				->join("product_info `pi` on pi.id=osd.pro_id")
				->join("pro_cate pc on pc.id=pi.cate")
				->join("pro_cate pct on pct.id=pc.fid")
				->where($map)
				->where("pct.id in ($cp) AND t.is_init=0")
				->group("pct.id")
				->findAll();
			foreach ($list as $row) {
				$result_cp[$row['id']]=$row;
			}
			//产品还款
			$map['t.type']=array("eq","0");
				$list_hk = \OrderSale::model()->field("pct.id,pct.name,SUM( IF(ord.type = 1, ord.money, '0') ) as cash, SUM( IF(ord.type = 2, ord.money, '0') ) as card")
				->join("order_repay_detail ord on ord.order_id=t.id")
				->join("order_sale_detail osd on osd.id=ord.order_detail_id")
				->join("product_info `pi` on pi.id=osd.pro_id")
				->join("pro_cate pc on pc.id=pi.cate")
				->join("pro_cate pct on pct.id=pc.fid")
				->where($map)
				->where("pct.id in ($cp) AND t.is_init=0")
				->group("pct.id")
				->findAll();
			foreach ($list_hk as $row) {
				if(isset($result_cp[$row['id']])){
					$result_cp[$row['id']]['cash']+=$row["cash"];
					$result_cp[$row['id']]['card']+=$row["card"];
				}else{
					$result_cp[$row['id']]=$row;
				}
			}
		}
		$result = array_merge($result_xm,$result_cp);

		//var_dump($result);die;
		return $result;
	}

	/**
	 * 负债分析表
	 * @return array
	 */
	public function getFZFX($md_id,$month=''){
		if(empty($month)){
			//实时计算
			//现金余额、赠送余额
			$map['ed.comp_id']=array("eq",\Ivy::app()->user->comp_id);
			if($md_id)
				$map['ed.id']=array("eq",$md_id);
			$list = \CustomerCapital::model()->field("ed.id as dept_id,ed.dept_name,SUM(IF(cc.type=1,t.balance,0)) as xjye,SUM(IF(cc.type=2,t.balance,0)) as zsye")
			->join("company_capital cc on cc.id=t.capital_id")
			->join("customer_info ci on ci.id=t.cu_id")
			->join("employ_dept ed on ed.id=ci.store_id")
			->where($map)
			->group("ed.id")
			->findAll();
			foreach ($list as $value) {
				$res[$value['dept_id']]=$value;
			}
			//本月疗程账户余额
			$map['ed.comp_id']=array("eq",\Ivy::app()->user->comp_id);
			$list = \CustomerReProject::model()->field("ed.id as dept_id,ed.dept_name, SUM(TRUNCATE(osd.pay_price/num*t.re_num,1)) as lcye")
			->join("customer_info ci on ci.id=t.cu_id")
			->join("employ_dept ed on ed.id=ci.store_id")
			->join("order_sale_detail osd on osd.id=t.detail_id")
			->where($map)
			->group("ed.id")
			->findAll();
			foreach ($list as $value) {
				$res[$value['dept_id']] = isset($res[$value['dept_id']])?array_merge($res[$value['dept_id']], $value):$value;
			}
			return $res;
		}else{
			$res=array();
			$map['t.month']=array("eq",$month);
			$map['ed.comp_id']=array("eq",\Ivy::app()->user->comp_id);
			if($md_id)
				$map['t.md_id']=array("eq",$md_id);
			$list = \StaticFz::model()->field("t.*,ed.dept_name,ed.id as dept_id")
			->join("employ_dept ed on ed.id=t.md_id")
			->where($map)
			->findAll();
			foreach ($list as $value) {
				$res[$value['dept_id']] = $value;
			}
			return $res;
		}
	}
	//负债表卡扣部分计算
	public function getFZFX_KK($md_id,$month){
		$days=date('t', strtotime($month."01"));	//此月份的天数
		$begin=strtotime($month."01");
		$end=$begin+$days*24*3600;
		$map['os.comp_id']=array("eq",\Ivy::app()->user->comp_id);
		$map['os.type']=array("eq",1);
		$map['os.status']=array("eq",1);
		if($md_id)
			$map['t.md_id']=array("eq",$md_id);
		$list = \OrderSaleDetail::model()
			->field("t.md_id as dept_id, SUM(t.card_cash) as xjkk,SUM(t.card_gift) as zskk")
			->join("order_sale os on t.order_id=os.id")
			->where($map)
			->group("t.md_id")
			->findAll();
		$res = array();
		foreach ($list as $value) {
			$value['lcxh']=$this->getXJSHYJ($value['dept_id'],$begin,$end); //疗程账户消耗
			$res[$value['dept_id']]=$value;
		}
		//var_dump($res);die;
		return $res;
	}



	/**
	 * 退款分析表
	 * @param $md_id 门店id
	 * @param $month 结束时间戳
	 * @return array
	 */
	public function getTKFX($md_id,$month)
	{
		$m=substr($month, -2);
		$y=substr($month, 0,4);
		if($m!='00'){
			$days=date('t', strtotime($month."01"));	//此月份的天数
			$begin=strtotime($month."01");
			$end=$begin+$days*24*3600;
		}else{
			$begin=strtotime($y."0101");
			$end=strtotime(($y+1)."0101");
		}
		$map['t.pay_time']	= 	array(array('gt',$begin),array('lt',$end));

		$map['t.md_id']=array("in",$md_id);
		if($md_id=="0"){//全连锁
			unset($map['t.md_id']);
		}
		$map['t.comp_id']	= 	array('eq',\Ivy::app()->user->comp_id);
		
		
		$result = \OrderRefund::model()
		->field(array("t.*,cu.name as cu_name,e.dept_name "))
		->join('customer_info cu on cu.id=t.cu_id')
		->join('employ_dept e on e.id=t.md_id')
		->where($map)
		->order('create_time desc')
		->group('t.id')
		->findAll();
		return $result;
	}

	/**
	 * 美容师服务人次
	 * @param $mrs_id 美容师id
	 * @param $md_id 门店id
	 * @param $stime 开始时间戳
	 * @param $etime 结束时间戳
	 * @return array
	 */
	public function getMRSDDKL($mrs_id,$md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id={$md_id}";
		if(is_array($md_id)) {
			$md_id = implode(",",$md_id);
			$sqlw=" AND t.md_id in ({$md_id})";
		}
		if($md_id==0){//全连锁
			$sqlw="";
		}
		$sql="SELECT sum(1/(operators_num._num+1)) as ZKL
			  FROM (SELECT t.cu_id AS cu_id, CHAR_LENGTH(pod.operators) AS num,(LENGTH (replace(pod.operators,',','--'))-LENGTH(pod.operators)) as _num
				FROM practice_order t
				LEFT JOIN customer_info ci ON t.cu_id=ci.id
				LEFT JOIN employ_dept ed ON t.md_id=ed.id
				LEFT JOIN practice_order_detail pod ON t.id=pod.por_id
				WHERE t.status=1
				AND t.pay_time BETWEEN {$stime} AND {$etime}
				AND t.comp_id={$comp_id}
				{$sqlw}
				AND find_in_set({$mrs_id},pod.operators)
				GROUP BY t.id) operators_num
				";
		$result=$this->findBySql($sql);
		$result=$result?$result:array();
		return $result;
	}

	/**
	 * 获取客户档案总数
	 * @param $type  客户类型(A\B\C\D\E)
	 * @param $dept_ids
	 * @param $status
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getDAZS($type,$status=null,$dept_ids,$stime,$etime){
		$where = "";
		if($status){
			$where = "AND cu.status={$status}";
		}
		$sql="SELECT count(cu.id) as num
				FROM customer_info cu
				WHERE cu.store_id in ({$dept_ids})
				AND cu.cu_type='{$type}'
				{$where}
				";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:0;
		return $result;
	}

	/**
	 * 通过客户类型获取客户的到店客流
	 * @param $cu_type
	 * @param $md_id
	 * @param $stime
	 * @param $etime
	 * @return array|int
	 */
	public function getCuTypeDDKL($cu_type,$md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id in ({$md_id})";
		$sql="SELECT COUNT(t.`cu_id`) AS num
				FROM practice_order t
				LEFT JOIN customer_info ci ON t.cu_id=ci.id
				WHERE t.`status`=1
				AND t.pay_time BETWEEN {$stime} AND {$etime}
				AND ci.cu_type='{$cu_type}'
				{$sqlw}
				";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:0;
		return $result;
	}

	/**
	 * 通过客户类型获取客户的到店人头
	 * @param $cu_type
	 * @param $md_id
	 * @param $stime
	 * @param $etime
	 * @return array|string
	 */
	public function getCuTypeDDRT($cu_type,$md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.md_id in ({$md_id})";
		$sql="	SELECT COUNT(cu_id) as num
				FROM (
					SELECT t.cu_id, t.md_id
					FROM practice_order AS t
					LEFT JOIN customer_info as cu
					ON cu.id = t.cu_id
					WHERE t.status=1
					AND t.pay_time BETWEEN {$stime}
					AND {$etime}
					AND t.comp_id={$comp_id}
					AND cu.cu_type='{$cu_type}'
					{$sqlw}
					GROUP BY t.cu_id
				) tmp";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
	}

	/**
	 * 通过客户类型获取客户的成交人头
	 * @param $cu_type
	 * @param $md_id
	 * @param $stime
	 * @param $etime
	 * @return array|string
	 */
	public function getCuTypeCJRT($cu_type,$md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND t.is_init=0 AND t.md_id in ({$md_id})";//排除期初数据
		$sql="SELECT COUNT(cu_id) as num
			  FROM (
					SELECT t.cu_id, t.md_id
					FROM order_sale AS t
					LEFT JOIN customer_info as cu
					ON cu.id = t.cu_id
					WHERE t.pay_status=1
					AND t.status=1
					AND t.type in (0,1,2)
					AND t.pay_time BETWEEN {$stime} AND {$etime}
					AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
					AND cu.cu_type='{$cu_type}'
					{$sqlw}
					GROUP BY t.cu_id
			  ) tmp";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
	}

	/**
	 * 获取到店的(A/B/C/D/E)客户
	 * @param $cu_type
	 * @param $md_id
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getDDCuType($cu_type,$md_id,$stime,$etime)
	{
		$comp_id=\Ivy::app()->user->comp_id;
		$sqlw=" AND ci.store_id in ({$md_id})";
		$sql="SELECT ci.id AS cu_id,ci.name as cu_name,ci.cardid as card_id,ci.operator_id as mrs,ci.counselor_id as gw
				FROM customer_info ci
				WHERE ci.cu_type='{$cu_type}'
				{$sqlw}
				GROUP BY ci.id
				";
		$result=$this->findAllBySql($sql);
		return $result;
	}

	/**
	 * 获取客户的到店次数
	 * @param $cu_id
	 * @param $stime
	 * @param $etime
	 * @return array|string
	 */
	public function getDDCS($cu_id,$stime,$etime)
	{
		$sql="SELECT count(t.cu_id) AS num
				FROM practice_order t
				WHERE t.status=1
				AND t.pay_time BETWEEN {$stime} AND {$etime}
				AND t.cu_id = {$cu_id}
				";
		$result=$this->findBySql($sql);
		$result=$result?$result['num']:"0";
		return $result;
	}

	/**
	 * 获取客户的成交次数和成交金额，排除期初数据
	 * @param $cu_id
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getCJCS($cu_id,$stime,$etime)
	{
		$sql="SELECT COUNT(cu_id) as num,sum(price) as price
			  FROM (
					SELECT t.cu_id, t.md_id,(t.cash+t.cash_pos+t.cash_tra+t.cash_other) as price
					FROM order_sale AS t
					WHERE t.`pay_status`=1
						AND t.`is_init`=0
						AND t.`status`=1
						AND t.`type` in (0,1,2)
						AND t.pay_time BETWEEN {$stime} AND {$etime}
						AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
						AND t.cu_id = {$cu_id}
				) tmp";
		$result=$this->findBySql($sql);
		return $result;
	}

	/**
	 * 门店新客成交次数和金额
	 * @param $dept_id
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getXKCJCS($dept_id,$stime,$etime)
	{
		$sql="SELECT count(cu_xk.cu_id) as num,cu_xk.dept_name as dept_name, sum(cu_xk.cash_total) as price
			  FROM (SELECT t.*,ed.dept_name as dept_name,sum(t.cash+t.cash_pos+t.cash_tra+t.cash_other) as cash_total
			  FROM order_sale AS t
			  LEFT JOIN employ_dept as ed ON t.md_id=ed.id
			  WHERE t.`pay_status`=1
					AND t.`status`=1
					AND t.`is_init`=0
					AND t.`type` in (1,2)
					AND t.pay_time BETWEEN {$stime} AND {$etime}
					AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
					AND t.md_id = {$dept_id}
					AND t.rh_id<>0
			  GROUP BY t.cu_id
			  ) cu_xk
			  ";
		$result=$this->findBySql($sql);
		return $result;
	}

	/**
	 * 门店成交次数和金额
	 * @param $dept_id
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getZCJCS($dept_id,$stime,$etime)
	{
		$sql="SELECT count(zc.cu_id) as num,zc.dept_name as dept_name,sum(zc.price) as price
			  FROM (SELECT t.cu_id as cu_id,t.id as order_id,count(t.cu_id) as num,ed.dept_name as dept_name,sum(t.cash+t.cash_pos+t.cash_tra+t.cash_other) as price,FROM_UNIXTIME(t.pay_time,'%Y%m%d') as time
			  FROM order_sale AS t
			  LEFT JOIN employ_dept as ed ON t.md_id=ed.id
			  WHERE t.`pay_status`=1
					AND t.`status`=1
					AND t.`is_init`=0
					AND t.`type` in (0,1,2)
					AND t.pay_time BETWEEN {$stime} AND {$etime}
					AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
					AND t.md_id = {$dept_id}
			  GROUP BY time,cu_id) zc
			  ";
		$result=$this->findBySql($sql);
		return $result;
	}

	/**
	 * 新客客户
	 * @param $dept_id
	 * @param $stime
	 * @param $etime
	 * @return array
	 */
	public function getXKKH($dept_id,$stime,$etime)
	{
		$sql="SELECT cu.name as cu_name,cm.level_name as level_name,t.id as order_id,t.type as type, ed.dept_name as dept_name,(t.cash+t.cash_pos+t.cash_tra+t.cash_other) as price
			  FROM order_sale AS t
			  LEFT JOIN employ_dept as ed ON t.md_id=ed.id
			  LEFT JOIN customer_info as cu ON t.cu_id=cu.id
			  LEFT JOIN config_membership as cm ON cu.membership_id=cm.id
			  WHERE t.`pay_status`=1
					AND t.`status`=1
					AND t.`type` in (1,2)
					AND t.pay_time BETWEEN {$stime} AND {$etime}
					AND (t.cash+t.cash_pos+t.cash_tra+t.cash_other)>0
					AND t.md_id = {$dept_id}
					AND t.rh_id<>0
			  ";
		$result=$this->findAllBySql($sql);
		return $result;
	}

	/**
	 * 获取订单的销售人员
	 * @param $order_id
	 * @return string
	 */
	public function getOrderSellers($order_id){
		$sql="SELECT eu.netname as netname
			  FROM order_effect_user AS t
			  LEFT JOIN employ_user as eu ON t.user_id=eu.id
			  WHERE t.order_id={$order_id}
			  ";
		$result=$this->findAllBySql($sql);
		foreach($result as $_result){
			$name[] = $_result['netname'];
		}
		return implode(',',$name);
	}

	/**
	 * 获取订单中的项目、产品信息
	 * @param $order_id
	 * @return string
	 */
	public function getOrderProInfo($order_id){
		$sql="SELECT t.*
			  FROM order_sale_detail AS t
			  WHERE t.order_id={$order_id}
			  ";
		$result=$this->findAllBySql($sql);
		foreach($result as $_result){
			$ProInfo = \OrderSale::model()->getProInfo($_result['pro_id'],$_result['pro_type']);
			$proInfo[] = $ProInfo->name.'('.$_result['num'].')';
		}
		return implode(',',$proInfo);
	}

	/**
	 * 客户年消费分析表
	 * @param $dept_id
	 * @param $year
	 * @param $type
	 * @param $xf_begin
	 * @param $xf_end
	 * @param $sh_begin
	 * @param $sh_end
	 * @return array
	 */
	public function geyKHNFXFX($dept_id,$year,$type,$xf_begin,$xf_end,$sh_begin,$sh_end){
		$comp_id=\Ivy::app()->user->comp_id;
		$sql_dept=" AND ca.dept_id={$dept_id}";
		if (strpos($dept_id,',')) {
			$dept_id=explode(',', $dept_id);
		}
		if(is_array($dept_id)) {
			$dept_id = implode(",",$dept_id);
			$sql_dept=" AND ca.dept_id in ({$dept_id})";
		}
		if($dept_id==0){//全连锁
			$sql_dept="";
		}
		$sql_type=" AND ci.cu_type='{$type}'";
		if (strpos($type,',')) {
			$type=explode(',', $type);
		}
		if(is_array($type)) {
			foreach($type as $item){
				$_type[] = "'".$item."'";
			}
			$type = implode(",",$_type);
			$sql_type=" AND ci.cu_type in ({$type})";
		}
		if($type=='0'){//全连锁
			$sql_type="";
		}
		$where = "";
		if($sh_begin!=null){
			$where .= " AND t.sh_money >= {$sh_begin}";
		}
		if($sh_end!=null){
			$where .= " AND t.sh_money <= {$sh_end}";
		}
		if($xf_begin!=null){
			$where .= " AND (t.card_money+t.cash_money) >= {$xf_begin}";
		}
		if($xf_end!=null){
			$where .= " AND (t.card_money+t.cash_money) <= {$xf_end}";
		}
		$sql = "
			SELECT t.*
			FROM (SELECT ca.cu_id as cu_id,ci.cu_type as cu_type,ca.year as year,sum(ca.use_money) as sh_money,sum(ca.card) as card_money,sum(ca.cash) as cash_money
			FROM customer_analysis as ca
			LEFT JOIN customer_info ci ON ci.id=ca.cu_id
			WHERE ca.year={$year} AND ca.comp_id={$comp_id} {$sql_dept} {$sql_type}
			GROUP BY ca.cu_id) t
			WHERE t.year={$year} {$where};
		";
		$_cu_ids = $this->findAllBySql($sql);
		foreach($_cu_ids as $item){
			$cu_ids[] = $item['cu_id'];
			$total[$item['cu_id']] = $item;
		}
		$cu_ids = implode(',',$cu_ids);
		$result = array();
		if(!empty($cu_ids)){
			$sql = "
			SELECT ca.*,ci.name as cu_name,ci.cu_type as cu_type,sum(ca.use_money) as sh_money,sum(ca.card) as card_money,sum(ca.cash) as cash_money
			FROM customer_analysis as ca
			LEFT JOIN customer_info ci ON ci.id=ca.cu_id
			WHERE ca.year={$year} AND ca.comp_id={$comp_id} {$sql_dept} {$sql_type} AND ca.cu_id in ({$cu_ids})
			GROUP BY ca.month,ca.cu_id;
		";
			$result = $this->findAllBySql($sql);
		}

		$data = array(
			'total'=>$total,
			'result'=>$result
		);
		return $data;
	}

	/**
	 * 经营报表--获取门店现金卡扣--购买
	 * @param $dept_id
	 * @param $order_type
	 * @param $pro_type
	 * @param $capital_type
	 * @param $stime
	 * @param $etime
	 * @return string
	 */
	public function getJYBB_KKYJ($dept_id,$order_type,$pro_type,$capital_type,$stime,$etime){
		$comp_id = \Ivy::app()->user->comp_id;
		$sql = "
			SELECT sum(osdm.money) as money
			FROM order_sale_detail_money as osdm
			LEFT JOIN order_sale_detail osd ON osd.id = osdm.detail_id
			LEFT JOIN order_sale os ON osd.order_id = os.id
			LEFT JOIN company_capital cc ON cc.id = osdm.capital_id
			WHERE os.comp_id={$comp_id} AND os.pay_status=1 AND os.type={$order_type} AND osd.pro_type={$pro_type}
				AND cc.type={$capital_type} AND os.md_id={$dept_id} AND os.pay_time BETWEEN {$stime} AND {$etime}
		";
		$result = $this->findBySql($sql);
		return empty($result['money'])?'0.00':$result['money'];
	}

	/**
	 * 经营报表--获取门店现金卡扣--还款
	 * @param $dept_id
	 * @param $order_type
	 * @param $hk_type
	 * @param $capital_type
	 * @param $stime
	 * @param $etime
	 * @return string
	 */
	public function getJYBB_KKYJ_HK($dept_id,$order_type,$hk_type,$capital_type,$stime,$etime){
		$comp_id = \Ivy::app()->user->comp_id;
		$sql_md = " AND os.md_id={$dept_id}";
		if($dept_id=0){
			$sql_md = "";
		}
		$sql = "
			SELECT sum(ord.money) as money
			FROM order_repay_detail as ord
			LEFT JOIN order_sale os ON ord.order_id = os.id
			LEFT JOIN customer_capital cuc ON cuc.id = ord.cu_capital_id
			LEFT JOIN company_capital cc ON cc.id = cuc.capital_id
			WHERE os.comp_id={$comp_id} AND os.pay_status=1 AND os.type={$order_type} AND cc.type={$capital_type} AND ord.type={$hk_type}
				{$sql_md} AND os.pay_time BETWEEN {$stime} AND {$etime}
		";
		$result = $this->findBySql($sql);
		return empty($result['money'])?'0.00':$result['money'];
	}
	
}