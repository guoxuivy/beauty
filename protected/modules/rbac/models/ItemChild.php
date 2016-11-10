<?php
namespace rbac;
use \Ivy\core\CException;
use \Ivy\core\ActiveRecord;
class ItemChild extends ActiveRecord
{
    /**
     * 必须方法
     **/
    public function tableName()
	{
		return 'auth_item_child';
	}

	public function addChilds($parent,$childs){
		$sql = 'insert into `'.$this->tableName().'` (`parent`,`child`) values ';
		foreach ($childs as $v) {
			$sql.= "( '{$parent}' , '{$v}' ),";
		}
		$sql=substr($sql,0,-1);
		return $this->exec($sql);
	}

	public function removeChilds($parent,$childs){
		$sql = "DELETE FROM `".$this->tableName()."` WHERE `parent`='{$parent}' AND `child` IN(";
		foreach ($childs as $v) {
			$sql.= " '{$v}',";
		}
		$sql=substr($sql,0,-1);
		$sql.= ")";
		return $this->exec($sql);
	}

    
}