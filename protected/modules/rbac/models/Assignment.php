<?php
namespace rbac;
use \Ivy\core\CException;
use \Ivy\core\ActiveRecord;
class Assignment extends ActiveRecord
{
    /**
     * 必须方法
     **/
    public function tableName()
	{
		return 'auth_assignment';
	}


	public function addRoles($user_id,$roles){
		$sql = 'insert into `'.$this->tableName().'` (`itemname`,`userid`) values ';
		foreach ($roles as $v) {
			$sql.= "( '{$v}' , {$user_id} ),";
		}
		$sql=substr($sql,0,-1);
		return $this->exec($sql);
	}

	public function removeRoles($user_id,$roles){
		//$roles=implode(',', $roles);
		$sql = "DELETE FROM `".$this->tableName()."` WHERE `userid`=$user_id AND `itemname` IN(";
		foreach ($roles as $v) {
			$sql.= " '{$v}',";
		}
		$sql=substr($sql,0,-1);
		$sql.= ")";
		return $this->exec($sql);
	}

    
}