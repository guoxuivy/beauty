<?php
namespace rebook;
use Ivy\core\CException;
/**
 * report 邀约本
 */
class IndexController extends \SController
{
    /**
     * 邀约本首页
     */
    public function indexAction(){
        if($this->is_store==true){
            $dept_id = \Ivy::app()->user->dept_id;
            $depts[$dept_id] = \EmployDept::model()->findByPk($dept_id)->dept_name;
        }else{
            $depts = \EmployDept::model()->getList($where='status=1 AND type=2');
            if(empty($depts)) {
                throw new CException("门店信息不全！请管理员设定！");
            }
            if(empty($_GET['dept_id'])){
                foreach($depts as $key=>$dept){
                    $dept_id = $key;
                    break;
                }
            }else{
                $dept_id = $_GET['dept_id'];
            }
        }
        $company_id = \Ivy::app()->user->comp_id;
        $rooms = \CompanyRoom::model()->where("comp_id={$company_id} and store_id={$dept_id} and status=1")->findAll();
        $dept_model = \EmployDept::model()->findByPk($dept_id);
        $times = \ReBook::model()->getTimes();
        $time = $_GET['time']?date('Ymd',strtotime($_GET['time'])):date('Ymd');


        $wx_book_date = date('Y-m-d',strtotime($time));
        $wx_list = \WxBook::model()
            ->field("t.*,cu.name,cu.cardid")
            ->join('customer_info cu on cu.id=t.cu_id')
            ->where(array(
                't.com_id' =>  array('eq',$company_id),
                't.dept_id' =>  array('eq',$dept_id),
                't.book_date' =>  array('eq',$wx_book_date),
                't.type' =>  array('neq','-1')
            ))
            ->limit(100)
            ->order('t.id desc')
            ->findAll();

        $this->view->assign(array(
            'rooms'=>$rooms,
            'wx_list'=>$wx_list,
            'times'=>$times,
            '_time'=>$time,
            'depts'=>$depts,
            'dept_id'=>$dept_id,
            'dept_name'=>$dept_model->dept_name,
            'zhanYong'=>count(\ReBook::model()->getRebookList("t.status=2 and t.dept_id={$dept_id} and t.ymd={$time}")),
            'yuYue'=>count(\ReBook::model()->getRebookList("t.status=1 and t.dept_id={$dept_id} and t.ymd={$time}")),
            'cancel'=>count(\ReBook::model()->getRebookList("t.status=0 and t.dept_id={$dept_id} and t.ymd={$time}")),
            'FWZ'=>count(\ReBook::model()->getRebookList("t.status=2 and t.dept_id={$dept_id} and t.ymd={$time}")),
            'YDD'=>count(\ReBook::model()->_getPractice($time,'0,1',$dept_id)),
            'cuRebook'=>\ReBook::model()->getCuRebook("t.status in (1,2) and t.dept_id={$dept_id} and t.ymd={$time}"),
        ))->display();
    }

    /**
     * 微信预约通过 
     * 回调微信接口 待续
     */
    public function weixinAction(){
        $id = (int)$_GET['id'];
        $rebook = \WxBook::model()->findByPk($id);
        $rebook->type = 2;
        $rebook->act_time = time();
        if($rebook->save()){
            $EmployDept=\EmployDept::model()->findByPk($rebook->dept_id);
            \WemallApi::sendText($rebook->com_id,$rebook->cu_id,'您的'.$rebook->book_date.' '.$rebook->book_times.'预约已接受！请按时到达'.$EmployDept->dept_name.'!');
            $this->ajaxReturn(200,'ok');
        }else{
            $this->ajaxReturn(400,'修改失败');
        }
    }

    /**
     * 房间的床位数渲染
     * @param $room_id
     */
    public function getBedsAction($room_id,$time_id){
        $dept_id = $_POST['dept_id'];
        $room = \CompanyRoom::model()->findByPk($room_id);
        $beds = \CompanyRoomBed::model()->where("room_id={$room_id}")->findAll();
        $where=" AND position_id=7 AND dept_id={$dept_id}";
        $mrs = \EmployUser::model()->getList($where);
        $times = \ReBook::model()->getTimes();
        $date = $_POST['date'];
        $ymd = date('Ymd',strtotime($date));
        $rebook = \ReBook::model()->where("ymd={$ymd} and room_id={$room_id} and find_in_set({$time_id},times) and status in (1,2)")->findAll();
        $rebooks = array();
        $status = 1;
        foreach($rebook as $_rebook){
            $rebooks[$_rebook['bed_id']] = $_rebook;
            if($_rebook['status']==2){
                $status = 2;
            }
        }
        $list = \ReBook::model()->getRebookList("t.status in (1,2) and t.dept_id={$dept_id} and t.ymd={$ymd} and find_in_set({$time_id},t.times)");
        $mrs_zy = array();
        foreach($list as $_list){
            foreach(explode(',',$_list['operator_ids']) as $operator_id){
                $mrs_zy[] = $operator_id;
            }
        }
        echo $this->view->assign(array(
            'beds' =>$beds,
            'room' =>$room,
            'mrs' =>$mrs,
            'times'=>$times,
            'time_id'=>$time_id,
            'rebooks'=>$rebooks,
            'mrs_zy'=>$mrs_zy,
            'status'=>$status,
        ))->render();
    }

    /**
     * 预约列表渲染
     * @param $status
     */
    public function rebookListsAction($status){
        $dept_id = $_POST['dept_id'];
        $time = $_POST['time']?date('Ymd',strtotime($_POST['time'])):date('Ymd');
        $list = \ReBook::model()->getRebookList("t.status in ({$status}) and t.dept_id={$dept_id} and t.ymd={$time}");
        $times = \ReBook::model()->getTimes();
        echo $this->view->assign(array(
            'list'=>$list,
            'times'=>$times,
        ))->render();
    }

    /**
     * 实操列表渲染
     * @param $status
     */
    public function practiceListsAction($status){
        $dept_id = $_POST['dept_id'];
        $time = $_POST['time']?date('Ymd',strtotime($_POST['time'])):date('Ymd');
        $list = \ReBook::model()->getPractice($time,$status,$dept_id);
        echo $this->view->assign(array(
            'list'=>$list,
        ))->render();
    }

    /**
     * 获取美容师下拉选项
     */
    public function getOperatorsAction(){
        $dept_id = $_POST['dept_id'];
        $where=" AND position_id=7 AND dept_id={$dept_id}";
        $mrs = \EmployUser::model()->getList($where);
        $time = $_POST['start_time'];
        $date = $_POST['date'];
        $ymd = date('Ymd',strtotime($date));
        $list = \ReBook::model()->getRebookList("t.status in (1,2) and t.dept_id={$dept_id} and t.ymd={$ymd} and find_in_set({$time},t.times)");
        foreach($list as $_list){
            foreach(explode(',',$_list['operator_ids']) as $operator_id){
                $mrs_zy[] = $operator_id;
            }
        }
        $option = '<option value="">--请选择美容师--</option>';
        foreach($mrs as $mrs_id=>$mrs_name){
            if(in_array($mrs_id,$mrs_zy)){
                $option .= '<option value="'.$mrs_id.'">'.$mrs_name.'<span style="color:red;">(占用)</span></option>';
            }else{
                $option .= '<option value="'.$mrs_id.'">'.$mrs_name.'</option>';
            }
        }
        echo $option;
    }


    /**
     * 取消预约
     */
    public function cancelAction(){
        $rebook_id = $_POST['rebook_id'];
        $rebook = \ReBook::model()->findByPk($rebook_id);
        $rebook->status = 0;
        if($rebook->save()){
            echo 'true';
        }else{
            echo 'false';
        }
    }

    /**
     * 检测预约时间段
     */
    public function checkTimeAction(){
        $date = $_POST['date'];
        $ymd = date('Ymd',strtotime($date));
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $rebook_id = $_POST['rebook_id'];
        $bed_id = $_POST['bed_id'];
        $times = array();
        for($i=$start_time;$i<$end_time;$i++){
            $times[] = $i;
        }
        $dept_id = \Ivy::app()->user->dept_id;
        $where = '';
        if(!empty($rebook_id)){
            $where = "and t.id<>{$rebook_id}";
        }
        $list = \ReBook::model()->getRebookList("t.status in (1,2) and t.dept_id={$dept_id} and t.ymd={$ymd} and t.bed_id={$bed_id} {$where}");

        foreach($list as $_list){
            $jj = array_intersect($times,explode(',',$_list['times']));
            if(!empty($jj)){
                echo 'false';
                return;
            }
        }
        echo 'true';
    }

    /**
     * 保存邀约本
     */
    public function saveAction(){
        $data = $_POST;
        $beds = $data['beds'];
        foreach($beds as $bed){
            $_data = array();
            $_data = $bed;
            $_data['room_id'] = $data['room_id'];
            $_data['status'] = $data['status'];
            $_data['ymd'] = $data['ymd'];
            $_data['dept_id'] = $data['dept_id'];
            $flat = \ReBook::model()->addNew($_data);
            if(!$flat){
                echo 'false';
                exit;
            }
        }
        echo 'true';
    }

}