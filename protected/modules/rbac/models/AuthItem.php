<?php
namespace rbac;
use \Ivy\core\CException;
use \Ivy\core\ActiveRecord;
class AuthItem extends ActiveRecord
{

	/**
	 * 所有字符串必须小写
	 */

    /**
     * 必须方法
     **/
    public function tableName()
	{
		return 'auth_item';
	}


	/**
	 * 批量收录操作
	 * @param array $list 方法名数组
	 * @return  boolen
	 */
	public function addMethods($list){
		$sql = 'insert into `'.$this->tableName().'` (`name`,`type`) values ';
		foreach ($list as $v) {
			$sql.= "( '{$v}' , 0 ),";
		}
		$sql=substr($sql,0,-1);
		return $this->exec($sql);
	}

	/**
	 * 更新
	 * @param array $list 方法名数组
	 * @return  boolen
	 */
	public function edit($name,$new_name){
		return $this->db->updateDataByCondition($this->tableName(),"name='{$name}'",array('name'=>$new_name));
	}
}