<?php
namespace invo;
use Ivy\core\Model;
use Ivy\core\CException;
/**
 * 库存报告 查询模型
 */
class ReportModel extends Model
{

	/**
	 * 库存报告--商品详细
	 * @param $dept_id
	 * @param $start_time
	 * @param $end_time
	 * @param $product_id
	 * @return array
	 */
	public function getKCBG_XX($dept_id,$start_time,$end_time,$product_id){
		$sql = "
			SELECT *
			FROM (
				SELECT '客户存' as type,FROM_UNIXTIME(UNIX_TIMESTAMP(cps.create_time),'%Y-%m-%d') as create_time,ed.dept_name as dept_name,
				'/' as rk,'/' as ck,'/' as baos,'/' as baoy,'/' as jc_num,cpsd.total_num as khc_num,'/' as khq_num,'/' as kht_num,
				(cpsd.total_num) as jcjc_num,UNIX_TIMESTAMP(cps.create_time) as time
				FROM customer_prod_storage_detail as cpsd
				LEFT JOIN customer_prod_storage cps ON cps.id = cpsd.storage_id
				LEFT JOIN employ_dept ed ON cps.dept_id = ed.id
				LEFT JOIN invo_dept i_d ON i_d.product_id = cpsd.product_id
				WHERE UNIX_TIMESTAMP(cps.create_time) BETWEEN {$start_time} AND {$end_time}
					AND cps.dept_id={$dept_id} AND cps.status=1 AND cpsd.product_id={$product_id} AND i_d.dept_id={$dept_id}
				UNION ALL
				SELECT '客户取' as type,FROM_UNIXTIME(UNIX_TIMESTAMP(cpr.create_time),'%Y-%m-%d') as create_time,ed.dept_name as dept_name,
					'/' as rk,'/' as ck,'/' as baos,'/' as baoy,'/' as jc_num,'/' as khc_num,cprd.num as khq_num,'/' as kht_num,
					(-cprd.num) as jcjc_num,UNIX_TIMESTAMP(cpr.create_time) as time
				FROM customer_prod_receive_detail as cprd
				LEFT JOIN customer_prod_receive cpr ON cpr.id = cprd.order_id
				LEFT JOIN employ_dept ed ON cpr.dept_id = ed.id
				LEFT JOIN invo_dept i_d ON i_d.product_id = cprd.product_id
				WHERE UNIX_TIMESTAMP(cpr.create_time) BETWEEN {$start_time} AND {$end_time}
					AND cpr.dept_id={$dept_id} AND cpr.re_status='1' AND cpr.status=1 AND cprd.product_id={$product_id} AND i_d.dept_id={$dept_id}
				UNION ALL
				SELECT '客户退' as type,FROM_UNIXTIME(o_r.pay_time,'%Y-%m-%d') as create_time,ed.dept_name as dept_name,
					'/' as rk,'/' as ck,'/' as baos,'/' as baoy,'/' as jc_num,'/' as khc_num,'/' as khq_num,ord.num as kht_num,
					(-ord.num) as jcjc_num,o_r.pay_time as time
				FROM order_refund_detail as ord
				LEFT JOIN order_refund o_r ON o_r.id = ord.refund_id
				LEFT JOIN employ_dept ed ON o_r.md_id = ed.id
				LEFT JOIN customer_prod_storage_detail cpsd ON cpsd.id = ord.re_pro_id AND ord.pro_type=2
				LEFT JOIN invo_dept i_d ON i_d.product_id = cpsd.product_id
				WHERE o_r.pay_time BETWEEN {$start_time} AND {$end_time}
					AND o_r.md_id={$dept_id} AND o_r.status=1 AND cpsd.product_id={$product_id} AND i_d.dept_id={$dept_id}
				UNION ALL
				SELECT '入库' as type,FROM_UNIXTIME(i_i.in_time,'%Y-%m-%d') as create_time,ed.dept_name as dept_name,
					i_i_d.in_num as rk,'/' as ck,'/' as baos,'/' as baoy,i_i_d.after_num as jc_num,'/' as khc_num,'/' as khq_num,'/' as kht_num,
					'/' as jcjc_num,i_i.in_time as time
				FROM invo_in_detail as i_i_d
				LEFT JOIN invo_in i_i ON i_i.id = i_i_d.order_id
				LEFT JOIN employ_dept ed ON i_i.dept_id = ed.id
				LEFT JOIN invo_dept i_d ON i_d.product_id = i_i_d.product_id
				WHERE i_i.in_time BETWEEN {$start_time} AND {$end_time}
					AND i_i.dept_id={$dept_id} AND i_i.status=1 AND i_i_d.product_id={$product_id} AND i_d.dept_id={$dept_id}
				UNION ALL
				SELECT '出库' as type,FROM_UNIXTIME(i_o.out_time,'%Y-%m-%d') as create_time,ed.dept_name as dept_name,
					'/' as rk,i_o_d.out_num as ck,'/' as baos,'/' as baoy,i_o_d.after_num as jc_num,'/' as khc_num,'/' as khq_num,'/' as kht_num,
					'/' as jcjc_num,i_o.out_time as time
				FROM invo_out_detail as i_o_d
				LEFT JOIN invo_out i_o ON i_o.id = i_o_d.order_id
				LEFT JOIN employ_dept ed ON i_o.dept_id = ed.id
				LEFT JOIN invo_dept i_d ON i_d.product_id = i_o_d.product_id
				WHERE i_o.out_time BETWEEN {$start_time} AND {$end_time}
					AND i_o.dept_id={$dept_id} AND i_o.status=1 AND i_o_d.product_id={$product_id} AND i_d.dept_id={$dept_id}
				UNION ALL
				SELECT IF(igl.type=1,'报损', '报溢') as type,FROM_UNIXTIME(UNIX_TIMESTAMP(igl.create_time),'%Y-%m-%d') as create_time,
					ed.dept_name as dept_name,
					'/' as rk,'/' as ck,IF(igl.type=1,igld.num,'/') as baos,IF(igl.type=1,'/',igld.num) as baoy,igld.after_num as jc_num,
					'/' as khc_num,'/' as khq_num,'/' as kht_num,'/' as jcjc_num,UNIX_TIMESTAMP(igl.create_time) as time
				FROM invo_gain_loss_detail as igld
				LEFT JOIN invo_gain_loss igl ON igl.id = igld.order_id
				LEFT JOIN employ_dept ed ON igl.dept_id = ed.id
				LEFT JOIN invo_dept i_d ON i_d.product_id = igld.product_id
				WHERE UNIX_TIMESTAMP(igl.create_time) BETWEEN {$start_time} AND {$end_time}
					AND igl.dept_id={$dept_id} AND igl.status=1 AND igld.product_id={$product_id} AND i_d.dept_id={$dept_id}
			) total
			ORDER BY time ASC
			";
		$result=$this->findAllBySql($sql);
		return $result;
	}

	/**
	 * 库存报告--商品详细--期初库存
	 * @param $product_id
	 * @param $dept_id
	 * @param $start_time
	 * @return array
	 */
	public function getProductQCKC($product_id,$dept_id,$start_time){
		$sql = "
			SELECT i_d_l.num as num,i_d_l.storage_num as storage_num
			FROM invo_dept_log as i_d_l
			WHERE UNIX_TIMESTAMP(i_d_l.create_time)<{$start_time} AND i_d_l.dept_id={$dept_id} AND i_d_l.product_id={$product_id}
			ORDER BY UNIX_TIMESTAMP(i_d_l.create_time) DESC
		";
		$result=$this->findBySql($sql);
		if(empty($result)){
			$result = array(
				'num'=>0,
				'storage_num'=>0,
			);
		}
		return $result;
	}
}