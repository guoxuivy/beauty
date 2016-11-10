<?php
namespace common;
use Ivy\core\Widget;
use Ivy\core\CException;
/**
 * 项目选择弹出框部件封装
 */
class SelectProjectWidget extends Widget
{
	public function run($param="simple"){
		if(is_array($param))
		{
			foreach ($param as $key => $value) {
				$$key=$value;
			}
		}
		//默认简单级别显示
		//  simple  medium  rich
		return $this->assign(array('level'=>$param,'show_del'=>(int)$show_del))->render();
		
	}


}