<?php
/**
 * @Author: K
 * @Date:   2015-03-31 13:42:15
 * @Last Modified by:   Administrator
 * @Last Modified time: 2015-08-31 15:31:55
 */
/**
 * 客户基本信息
 */
use Ivy\core\ActiveRecord;
class CustomerInfo extends ActiveRecord
{
	public function tableName() {
		return 'customer_info';
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
		if ($this->birthday && is_string($this->birthday)) {
			$this->birthday=strtotime($this->birthday);
		}
		if ($this->zh_birthday && is_string($this->zh_birthday)) {
			$this->zh_birthday=strtotime($this->zh_birthday);
		}
		if ($this->last_time && is_string($this->last_time)) {
			$this->last_time=strtotime($this->last_time);
		}
		if ($this->enrollment_time && is_string($this->enrollment_time)) {
			$this->enrollment_time=strtotime($this->enrollment_time);
		}
		$company_info=Ivy::app()->user->getState('company_info');
		$this->company_id=$company_info['id'];
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
	 *  更新最后到店时间
	 * @param  integer $status 
	 * @return 
	 */
	public function updateLastTime(){
		$this->last_time=time();
		return $this->update();
	}
	/**
	 * 获取性别
	 * @param  $Pk 
	 * @return 
	 */
	public static function getSex($Pk=null){
		$Array = array(
			'男' => '男',
			'女' => '女',
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
	 * 获取属相
	 * @param  $Pk 
	 * @return 
	 */
	public static function getZhZodiac($Pk=null){
		$Array = array(
			'鼠' => '鼠',
			'牛' => '牛',
			'虎' => '虎',
			'兔' => '兔',
			'龙' => '龙',
			'蛇' => '蛇',
			'马' => '马',
			'羊' => '羊',
			'猴' => '猴',
			'鸡' => '鸡',
			'狗' => '狗',
			'猪' => '猪',
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
	 * 获取星座 '','','','','','','','','','','',''
	 * @param  $Pk 
	 * @return 
	 */
	public static function getZodiak($Pk=null){
		$Array = array(
			'水瓶座' => '水瓶座',
			'双鱼座' => '双鱼座',
			'白羊座' => '白羊座',
			'金牛座' => '金牛座',
			'双子座' => '双子座',
			'巨蟹座' => '巨蟹座',
			'狮子座' => '狮子座',
			'处女座' => '处女座',
			'天秤座' => '天秤座',
			'天蝎座' => '天蝎座',
			'射手座' => '射手座',
			'摩羯座' => '摩羯座',
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
	 * 获取职业类型(1全职,2兼职,3退休,4家庭妇女5.自由职业6.企业主)
	 * @param  $Pk 
	 * @return 
	 */
	public static function getProfessionType($Pk=null){
		$Array = array(
			'1' => '全职',
			'2' => '兼职',
			'3' => '退休',
			'4' => '家庭妇女',
			'5' => '自由职业',
			'6' => '企业主',
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
	 * 获取婚姻状况(1未婚,2已婚,3离异,4丧偶)
	 * @param  $Pk 
	 * @return 
	 */
	public static function getMarriage($Pk=null){
		$Array = array(
			'1' => '未婚',
			'2' => '已婚',
			'3' => '离异',
			'4' => '丧偶',
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
	 * 获取客户来源(1.自上门.2.外联3.嘉宾4.拓客5.广告6.其他7.系统会员)
	 * @param  $Pk 
	 * @return 
	 */
	public static function getComeType($Pk=null){
		$Array = array(
			'1' => '自上门',
			'2' => '外联',
			'3' => '嘉宾',
			'4' => '拓客',
			'5' => '广告',
			'6' => '其他',
			'7' => '系统会员',
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
	 * 获取客户类型(ABCD...)
	 * @param  $Pk 
	 * @return 
	 */
	public static function getCuType($Pk=null){
		$Array = array(
			'A'  => 'A',
			'B'  => 'B',
			'C'  => 'C',
			'D'  => 'D',
			'E'  => 'E',
			'未分类' =>'未分类',
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
	 * 获取客户状态（活跃1、久档2、死档3、无效-1）
	 * @param  $Pk 
	 * @return 
	 */
	public static function getStatus($Pk=null){
		$Array = array(
			'1'  => '活跃',
			'2'  => '久档',
			'3'  => '死档',
			'-1' => '无效',
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
	 * 获取客户状态（活跃1、久档2、死档3、无效-1）(单字)
	 * @param  $Pk 
	 * @return 
	 */
	public static function getSmallStatus($Pk=null){
		$Array = array(
			'1'  => '活',
			'2'  => '久',
			'3'  => '死',
			'-1' => '无',
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
	 * 累加欠款
	 * @param [type] $Pk [description]
	 */
	public function addArrears($Pk=null,$money){
		$info = \CustomerInfo::model()->findByPk($Pk);

		$info->arrears=$info->arrears+$money;

		if(!$info->save()) throw new CException("个人欠款更新失败！");
	}
	/**
	 * 累加积分
	 * @param [type] $Pk [description]
	 */
	public function addScore($Pk=null,$score,$rel_id,$pos_dir='in',$rel_type=1,$rel_ext=0){
		$info = \CustomerInfo::model()->findByPk($Pk);
		$CustomerScoreLog=new \CustomerScoreLog();
		$CustomerScoreLog->cu_id=$Pk;
		$CustomerScoreLog->before_score=$info->score;
		$info->score=$info->score+$score;
		$CustomerScoreLog->score=$score;
		$CustomerScoreLog->after_score=$CustomerScoreLog->before_score+$CustomerScoreLog->score;
		$CustomerScoreLog->rel_id=$rel_id;
		$CustomerScoreLog->pos_dir=$pos_dir;
		$CustomerScoreLog->rel_type=$rel_type;
		$CustomerScoreLog->rel_ext=$rel_ext;
		if(!$info->save()) throw new CException("个人积分更新失败！");
		if(!$CustomerScoreLog->save()) throw new CException("个人积分日志更新失败！");
	}

	//流失客户数量统计（30天内未实操客户）
	public function outlistnum($store_id=null,$cu_type=null) {
		$map=array();
		$map['t.company_id']		=	array('eq',\Ivy::app()->user->comp_id);
		if($store_id){
			$map['t.store_id']		=	array('eq',$store_id);
		}
		if($cu_type){
			$map['t.cu_type']		=	array('eq',$cu_type);
		}
		$end_time = strtotime(date("Ymd"))-3600*24*30;
		$map['t.last_time'] = array('lt',$end_time);
		return $this->where($map)->count();
	}

	/**
	 * 获取客户期初订单审核页面url
	 * @param $cu_id
	 * @return string
	 */
	public function getPayUrlForInit($cu_id){
		$order = \OrderSale::model()->find("cu_id={$cu_id} and is_init=1 and pay_status=0");
		$url = $this->url('pos/init/pay',array('id'=>$order['id']));
		$html = '<a href="'.$url.'" class="khca">开账</a>';
		return $html;
	}

	/**
	 * 获取客户头像
	 * @param  [type] $Pk [description]
	 * @return [type]     [description]
	 */
	public static function getAvatar($id=null){
		$model_exten = \CustomerInfoExten::model()->find('cu_id='.$id);
		$cu_avatar = SITE_URL."/public/images/kh_06.png";
		if($model_exten&&$model_exten->avatar)
			$cu_avatar=$model_exten->avatar;
		return $cu_avatar;
	}

	


}