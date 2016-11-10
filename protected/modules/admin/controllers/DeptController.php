<?php
/**
 * @Author: K
 * @Date:   2015-04-02 16:17:19
 * @Last Modified by:   K
 * @Last Modified time: 2015-04-09 11:24:53
 */
//组织结构管理 部门管理
namespace admin;
use Ivy\core\CException;
class DeptController extends \SController
{

	//部门管理
	public function indexAction() {
		$pager= \ProjectInfo::model()->page(1,10)->getPagener();
		//部门数组
		$deptArr = \CommonConfig::getDeptArr();
		//职位数组
		$positionArr = \CommonConfig::getPositionArr();
		//高级版的职位数组
		$seniorPositionArr = \CommonConfig::getSeniorPositionArr();
		//高级版才可用的职位（财务经理）
		$disabledArr = array(
			'6'
		);
		
		$this->view->assign(array(
			'deptArr' => $deptArr,
			'positionArr' => $positionArr,
			'disabledArr' => $disabledArr,
			'seniorPositionArr' => $seniorPositionArr,
			'company'=>$this->company,
			
		))->display();
	}

}