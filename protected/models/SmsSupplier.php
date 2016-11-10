<?php
/**
 * 站内信
 * @Author: guojh
 * @Date:   2016-03-09
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class SmsSupplier extends ActiveRecord
{
    protected $_cache = false;  

    //配置供应商远程数据库
    public function __construct($config=null){
        $this->_config = \Ivy::app()->C('supplier_pdo');
        parent::__construct($config);
    }

    public function tableName() {
        return 'sms_message';
    }
    //将采购单发给供应商
    public function send($data){
        $beauty = \CompanyInfo::model()->findByPk($data['comp_id']);
        // $supply = \SupplierInfo::model()->findByPk($data['supply_id']);
        $sql = "SELECT id FROM `employ_user` WHERE status = 1 AND position_id = '老板' AND comp_id = ".$data['supply_id'];
        $boss = $this->findBySql($sql);
        // exit("sss");
        $content = "<table width=\"100%\" class=\"table_table\"><thead><tr><th> 采购分类 </th><th> 品牌或系列 </th><th> 商品名称 </th><th> 规格 </th><th> 单位 </th><th> 数量 </th></tr></thead>
                <tbody>";
        foreach ($data['product_info'] as $key => $value) {
            $tmp[$value['id']] = $value;
        }
        foreach ($data['detail'] as $val) {
            $content .= "<tr><td>{$tmp[$val['pro_id']]['tcname']}</td>";
            $content .= "<td>{$tmp[$val['pro_id']]['cname']}</td>";
            $content .= "<td>{$tmp[$val['pro_id']]['name']}</td>";
            $content .= "<td>{$tmp[$val['pro_id']]['specs']}</td>";
            $content .= "<td>{$tmp[$val['pro_id']]['unit']}</td>";
            $content .= "<td>{$val['num']}</td></tr>";
        }

        $content .= "</tbody></table><p>备注：".$data['note']."</p>";
        $mdata  = array(
            "create_time"=>time(),
            "type"=>"inbox",
            "status"=>1,
            "title"=>$beauty->comp_name.\EmployDept::getSName(\Ivy::app()->user->dept_id).\Ivy::app()->user->netname."向您发起了采购订单",
            "to_uid"=>$boss['id'],
            "to_uids"=>$boss['id'],
            "content"=> $content,
            "url"=>\Ivy::app()->user->id
        );
       try{
            // var_dump($data);
            //开启事务处理  
            $this->db->beginT();
            $this->attributes=$mdata;
            $this->save();
            $this->db->commitT();
        }catch(CException $e){
            $this->_error[]=$e->getMessage();
            $this->db->rollbackT();
            return false;
        }
        return $this->id;
    }
}