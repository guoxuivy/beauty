<?php
namespace h5;
use Ivy\core\CException;
/**
 * 我
 */
class MeController extends BaseController {
    /**
     * 我
     */
    public function indexAction(){
        $this->view
        ->assign('name',\Ivy::app()->user->netname)
        ->assign('position',\EmployPositionInfo::model()->findByPk(\Ivy::app()->user->position_id)->position_name)
        ->display();
    }
	

	public function aboutAction(){
        $this->view->assign()->display();
    }

    //修改密码
    public function passAction(){
        if($this->isAjax){

            if(\Ivy::app()->user->getIsGuest()) {
                $this->redirect('h5/login/login');
            } else {
                $old_pw = trim($_POST['old_pw']);
                $new_pw = trim($_POST['new_pw']);
                if(\Ivy::app()->user->password != md5($old_pw)) {
                    $this->ajaxReturn('400', '原始密码不正确！');
                }
                $userModel = \EmployUser::model()->findByPk(\Ivy::app()->user->id);
                $userModel->password = md5($new_pw);
                $userModel->save();
                \Ivy::app()->user->logout();
                \Ivy::app()->user->login($userModel);
                $this->ajaxReturn('200', '修改成功！');
            }
            
        }
        $this->view->assign()->display();
    }

    
}