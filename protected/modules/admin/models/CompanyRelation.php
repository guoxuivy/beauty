<?php
namespace admin;
use Ivy\core\ActiveRecord;
class CompanyRelation extends ActiveRecord
{
	protected $_cache = false;	

	//配置供应商远程数据库
	// public function __construct($config=null){
	// 	$this->_config = \Ivy::app()->C('supplier_pdo');
	// 	parent::__construct($config);
	// }


	public function tableName() {
		return 'sup_company_relation';
	}


	/**
	 * 公司绑定关系 行数据
	 * @return [type] [description]
	 */
	public static function getPager(){
		$map['t.beauty_id'] = array('eq',\Ivy::app()->user->comp_id);
		$pager= self::model()->field("t.* ,ci.comp_name as name, ci.phone")
			->join("company_info as ci ON ci.id=t.comp_id")
			->where($map)->getPagener();
		return $pager;
	}


	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function getStatus($Pk=null){
		$Array = array(
			'1'		=> '<font color="#08a565">同意查看销售数据</font>',
			'0'		=> '<font color="#09f">请求查看数据...</font>',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}else{
				return false;
			}
		}
	}
		
	

}	