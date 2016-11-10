<?php
/**
 * @Author: sam
 */
/**
 * 项目、产品分类模型
 */
use Ivy\core\ActiveRecord;
class ProCate extends ActiveRecord
{
	public function tableName() {
		return 'pro_cate';
	}

	/**
	 * 商品顶级分类固定 所以公司id为0
	 * @param  string $id [description]
	 * @return [type]     [description]
	 */
	// $arr = array(
	// 	'1' => '可售商品',
	// 	'2' => '礼品赠品',
	// 	'3' => '油粉胶膜消耗品',
	// 	'4' => '其他',
	// );
	public static function getProductTopCate($id = '',$show_del=0){
		static $arr=null;
		if($show_del)
			$sqlw="";
		else
			$sqlw=" and status=1";
		if(is_null($arr)){
			$res = self::model()->field('id,name,status')->where('comp_id=0 and level=1 and type=2'.$sqlw)->findAll();
			foreach ($res as $key => $value) {
				$arr[$value['id']]=$value['name'];
				if($value['status']<0)
					$arr[$value['id']]=$arr[$value['id']].'(删除)';
			}
		}
		if($id === '') {
			return $arr;
		}
		if(array_key_exists($id, $arr)) {
			return $arr[$id];
		} else {
			return false;
		}

	}

	/**
	 * 项目顶级分类 可自定义
	 * @param  string $id [description]
	 * @return [type]     [description]
	 */
	public static function getProjectTopCate($id = '',$show_del=0){
		static $arr=null;
		if($show_del)
			$sqlw="";
		else
			$sqlw=" and status=1";
		if(is_null($arr)){
			$comp_id = \Ivy::app()->user->comp_id;
			$res = self::model()->field('id,name,status')->where('comp_id='.$comp_id.' and level=1 and type=1'.$sqlw)->findAll();
			foreach ($res as $key => $value) {
				$arr[$value['id']]=$value['name'];
				if($value['status']<0)
					$arr[$value['id']]=$arr[$value['id']].'(删除)';
			}
		}
		if($id === '') {
			return $arr;
		}
		if(array_key_exists($id, $arr)) {
			return $arr[$id];
		} else {
			return false;
		}

	}

	/**
	 * 可售商品类型固定
	 * @param  string $id [description]
	 * @return [type]     [description]
	 */
	public static function getProductType($id = ''){
		$arr = array(
			'1' => '院装',
			'2' => '家装',
			'3' => '通用',
		);
		if($id === '') {
			return $arr;
		}
		if(array_key_exists($id, $arr)) {
			return $arr[$id];
		} else {
			return false;
		}
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

}