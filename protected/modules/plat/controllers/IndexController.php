<?php
namespace plat;
use Ivy\core\CException;
class IndexController extends \SController
{
	public function actionBefore() {
		parent::actionBefore();
		if(\Ivy::app()->user->position_id!=11)
			throw new CException("非法访问！");
	}

	// 列表
	public function indexAction() {
		$this->view->assign()->display();
	}

	
}