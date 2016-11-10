<?php
/**
 * @Author: K
 * @Date:   2015-03-31 12:01:41
 * @Last Modified by:   K
 * @Last Modified time: 2015-04-29 16:44:30
 */
/**
 * 商品信息
 */
use Ivy\core\ActiveRecord;
use \Ivy\core\CException;
class ProductInfo extends ActiveRecord
{
	public function tableName() {
		return 'product_info';
	}

	/**
	 * 验证器
	 * @return [type] [description]
	 */
	public function validate(){
		if(empty($this->name)){
			$this->_error[]='商品名称不能为空！';
			return false;
		}
		return true;
	}
	/**
	 * 数据保存
	 * @param  array $Data  [description]
	 * @param  object $model 
	 * @return 
	 */
	public function saveData($data)
	{
		$this->attributes=$data;
		$company_info=Ivy::app()->user->getState('company_info');
		$this->comp_id=$company_info['id'];
		//商品编码重复检测
		if(isset($data['code'])&&!empty($data['code'])){
			$map['code']=array('eq',$data['code']);
			$map['status']=array('neq','-1');
			$map['comp_id']=array('eq',$company_info['id']);
			if($data['id'])
				$map['id']=array('neq',$data['id']);
			$d_m = $this->where($map)->find();
			if($d_m){
				throw new CException("商品编码重复！");
			}
		}
		return $this->save();
	}
	/**
	 *  更新状态(删除)
	 * @param  integer $status 
	 * @return 
	 */
	public function updateStatus($status=-1){
		$this->status=$status;
		return $this->update();
	}
	/**
	 * 状态的停用启用
	 * @return 
	 */
	public function updateUseStatus()
	{
		if(!in_array($this->status, array(1,0)))
			return false;
		$this->status=$this->status==0?1:0;
		return $this->updateStatus($this->status);
	}
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function  getStatus($Pk=null){
		$Array = array(
			'1'		=> '正常',
			'0'		=> '停用',
			'-1'	=> '删除',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}
			else {
				return false;
			}
		}
	}
	/**
	 * 逻辑删除
	 * @return 
	 */
	public function delete(){
		return $this->updateStatus();
	}
	/**
	 * 获取状态
	 * @param  $Pk 
	 * @return 
	 */
	public static function getType($Pk=null){
		$Array = array(
			'1'		=> '院装',
			'2'		=> '家装',
			'3'		=> '通用',
		);
		if($Pk === null) {
			return $Array;
		}
		else {
			if(array_key_exists($Pk, $Array)) {
				return $Array[$Pk];
			}
			else {
				return false;
			}
		}
	}

	/**
	 * 批量插入数据 模版插入
	 * @return 
	 */
	public static function insertFile($file_name) {
		$data = self::readExcel($file_name);
		array_shift($data);
		$count = count($data);
		$i = 0;
		//商品分类数组
		//$levelArr = \CommonConfig::getProductLevel();
		//插入数据时出错的行
		$errorLineArr = array();
		@set_time_limit(0);
		foreach($data as $key => $val) {
			$io = $val[0];
			$top_cate = $val[1];
			$cate = empty($val[2]) ? '未分配' : $val[2];
			$name = $val[3];
			$price = empty($val[4]) ? 0 : $val[4];
			$unit = $val[5];
			$specs = $val[6];
			$number = empty($val[7]) ? 1 : $val[7];
			$code = $val[8];


			if(empty($top_cate)  || empty($name)){
				$errorLineArr[] = $io;
				continue;
			}
			$productModel = new \ProductInfo();
			$productModel->comp_id = \Ivy::app()->user->comp_id;
			$TopProCate=\ProCate::model()->find("comp_id=0 AND name='{$top_cate}' AND status=1 AND type=2 AND level=1");
			if(empty($TopProCate)) {
				$errorLineArr[] = $io;
				continue;
			}
			
			$ProCate=\ProCate::model()->find("comp_id={$productModel->comp_id} AND fid={$TopProCate->id} AND name='{$cate}'  AND status=1 AND type=2 AND level=2");
			if (empty($ProCate)) {
				$ProCate=new \ProCate;
				$ProCate->fid=$TopProCate->id;
				$ProCate->comp_id=$productModel->comp_id;
				$ProCate->name=$cate;
				$ProCate->level=2;
				$ProCate->type=2;
				$ProCate->save();
			}
			$productModel->cate=$ProCate->id;
			$productModel->name = $name;
			$productModel->price = $price;
			$productModel->unit = $unit;
			$productModel->specs = $specs;
			$productModel->num = $number;

			$productModel->type = 3;
			$productModel->code = $code;
			
			if($productModel->save()) {
				$i++;
			} else {
				$errorLineArr[] = $io;
			}
		}
		$arr = array(
			'total' => $count,
			'success' => $i,
			'error_num'=>$count-$i,
			'error' => $errorLineArr,
		);
		return $arr;
	}
	/**
	 * 读取Excel，分析数据
	 * @param string $file_name 导入数据用的Excel文件位置
	 * @return array|boolean
	 */
	public static function readExcel($file_name) {
		if(!isset($file_name)) {
			return false;
		}
		\Ivy::importExt("excel/Spreadsheet_Excel_Reader");
		$data = new \Spreadsheet_Excel_Reader();
		// Set output Encoding.
		$data->setOutputEncoding('UTF-8');
		$data->read($file_name);
		
		$excel_data = array();
		for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
			$row_data = array();
			for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
				$row_data[] = trim($data->sheets[0]['cells'][$i][$j]);
			}
			$excel_data[] = $row_data;
		}
		return $excel_data;
	}


	/**
	 * 批量更新供应商
	 * @return [type] [description]
	 */
	public function cleanSupplier($map){
		$num = $this->updateData($this->tableName(),$map,array('supp_id'=>0));
		if($this->_cache)
			$this->getBehavior('ARcache')->flush();
		$this->lastSql=$this->db->lastSql;
		return $num;
	}
}