<?php
/**
 * 站内信
 * @Author: guox
 * @Date:   2016-1-12 14:15:00
 * @Last Modified time: 2016-01-14 15:48:59
 */
use Ivy\core\ActiveRecord;
use Ivy\core\CException;
class SmsMessage extends ActiveRecord
{

	//关闭缓存
	protected $_cache = false;
	public function tableName() {
		return 'sms_message';
	}
	/**
	 * 发站内信
	 * $arr=array(
		'to_uids'=>,
		'title'=>,
		'content'=>,
		);
	 */
	public static function send($arr){
		$out = new \SmsMessage;
		$out->uid = $arr['uid']?$arr['uid']:\Ivy::app()->user->id;
		//$out->to_uid=$arr['to_uid'];
		if (!$arr['to_uids']) {
			throw new CException("收件人必填！");
		}
		$out->to_uids=is_array($arr['to_uids'])?implode(',',$arr['to_uids']) : $arr['to_uids']; //必填
		
		$out->title=$arr['title'];
		$out->content=$arr['content'];
		$out->url=$arr['url'];
		$out->status=$arr['status']?$arr['status']:1;//默认已发送
		$out->type='outbox';
		$out->create_time=time();
		$out->note=$arr['note'];
		if($out->save()&&$out->status==1){
			foreach (explode(',', $out->to_uids) as $touid) {
				//收件箱写入
				$in = new \SmsMessage;
				$in->uid = $out->uid;
				$in->to_uid = $touid;
				$in->to_uids = $out->to_uids;
				$in->title = $out->title;
				$in->content = $out->content;
				$in->url = $out->url;
				$in->status = 1;//未阅读
				$in->type='inbox'; //收件箱
				$in->create_time = $out->create_time;
				$out->note = $out->note;
				$in->save();
				//不检测发送失败
			}
		}
	}

	/**
	 * 标记已读
	 * @return [type] [description]
	 */
	public function read(){
		if(is_null($this->read_time)){
			$this->status = 0;
			$this->read_time = time();
			$this->save();
		}
	}

	/**
	 * 收件箱信箱list
	 * 默认未读(status=1)
	 * @return [type] [description]
	 */
	public static function getInList($status=1,$limit=3){
		$map['t.to_uid'] = array('eq',\Ivy::app()->user->id);
		$map['t.type'] = array('eq','inbox');
		$map['t.status'] = array('eq',1);
		
		$mes_list = \SmsMessage::model()
			->field("t.*,eu.netname as send_name")
			->join("employ_user eu ON eu.id=t.uid")
			->limit($limit)->where($map)->order('create_time DESC')->findAll();
		return $mes_list;
	}

	/**
	 * 获取为阅读数量
	 * 默认未读
	 * @return [type] [description]
	 */
	public static function getNotNum(){
		$map['t.to_uid'] = array('eq',\Ivy::app()->user->id);
		$map['t.type'] = array('eq','inbox');
		$map['t.status'] = array('eq',1);
		$num = \SmsMessage::model()->where($map)->count();
		return $num;
	}

	/**
	 * 获取多收件人
	 * @return [type] [description]
	 */
	public function getToUids(){
		$map['t.id'] = array('in',$this->to_uids);
		$list = \EmployUser::model()->field("t.netname,ci.comp_name,ep.position_name")
		->join('company_info ci ON ci.id=t.comp_id')
		->join('employ_position ep ON ep.id=t.position_id')
		->where($map)->findAll();
		return $list;
	}




}