<?php
namespace top;
use Ivy\core\Widget;
use Ivy\core\CException;
class NavWidget extends Widget
{
    
	public function run($light_nav=null) {
		$res = $this->_show($this->getNav(),$light_nav);
		return $res;
	}

    private function getNav(){
        $user=\Ivy::app()->user;
        switch ($user->position_id) {
            case '1'://公司总经理
                $nav = $this->ZJLNav();
                if(\Ivy::app()->user->getState('show_position') == 10)
                    $nav = $this->AdminNav();
                break;
            case '5'://门店经理
                $nav = $this->JLNav();
                break;
            case '8'://门店前台
                $nav = $this->QTNav();
                break;
            case '10'://公司管理员
                $nav = $this->AdminNav();
                break;
            case '11'://平台管理员
                $nav = $this->PlatNav();
                break;
            default:
                $nav = $this->AdminNav();
                break;
        }
        return $nav;
    }

	/**
     * 公司管理员导航数据
     *@return array 
     */
    public function AdminNav(){
        return $_nav=array(
            '首页'=>$this->url('admin/admin/index'),
            '部门管理'=>$this->url('admin/dept/index'),
            '门店管理'=>$this->url('admin/store/index'),
            '房间管理'=>$this->url('admin/room/index'),
            '会员卡管理'=>$this->url('admin/membership/index'),
            //'供应商管理'=>$this->url('admin/supplier/index'),
            '员工管理'=>$this->url('admin/employ/index'),
            '供应商管理'=>array(
                '我的供应商'=>$this->url('admin/supplier/index'),
                '供应商申请'=>$this->url('admin/supplier/appli'),
            ),


            '商品管理'=>array(
                '商品分类'=>$this->url('admin/product/cate'),
                '商品列表'=>$this->url('admin/product/index'),
            ),
            '项目管理'=>array(
                '项目分类'=>$this->url('admin/project/cate'),
                '项目列表'=>$this->url('admin/project/index'),
            ),
			'活动管理'=>$this->url('admin/salenotice/index'),
            '卡券管理'=>$this->url('admin/voucher/index'),
            '功能设置'=>array(
                //'单据显示设置'=>$this->url('admin/config/index'),
                '积分设置'=>$this->url('admin/config/score'),
                '会员账户设置'=>$this->url('admin/config/capital'),
                '收款账户设置'=>$this->url('admin/config/payee'),
                '销售权限设置'=>$this->url('admin/config/saleaccess'),
                '有效会员设置'=>$this->url('admin/config/memberconfig'),
            ),
        );
    }

    /**
     * 平台管理员导航数据
     *@return array
     */
    public function PlatNav(){
        return $_nav=array(
            '首页'=>$this->url('/'),
            '公司管理'=>$this->url('plat/company/index'),
            '发送消息'=>$this->url('sms/message/outList'),
        );
    }

    /**
     * 前台导航数据
     *@return array 
     */
    public function QTNav(){
        $_nav=array(
            '首页'=>$this->url('pos/index/index'),
            'POS开单'=>$this->url('pos/index/nav'),
            '邀约本'=>$this->url('rebook/index/index'),
            '客户管理'=>array(
                '客户列表'=>$this->url('customer/index/index'),
                '期初/开账'=>$this->url('customer/init/index')
            ),
            '交易记录'=>$this->url('pos/sale/index'),
            '数据报表'=>$this->url('report/index/index'),

            '仓库管理'=>array(
                '商品列表'=>$this->url('invo/index/index'),
                '出库流水'=>$this->url('invo/out/index'),
                '入库流水'=>$this->url('invo/in/index'),
                '损溢流水'=>$this->url('invo/loss/index'),
                '盘库记录'=>$this->url('invo/inventory/index'),
                '库存报告'=>$this->url('invo/report/index'),
                '出入库记录'=>$this->url('invo/report/list'),
                '采购记录'=>$this->url('invo/purchase/index'),
            ), 
            '项目管理'=>array(
                '项目列表'=>$this->url('admin/project/index'),
            ),
            '寄存管理'=>$this->url('noinvo/report/JCGL'),
            '库存消耗管理'=>array(
                '领用单'=>$this->url('noinvo/recipients/index'),
                '出库记录'=>$this->url('noinvo/report/CKJL'),
                '消耗报表'=>$this->url('noinvo/report/XHBB'),
                '寄存汇总表'=>$this->url('noinvo/report/JCHZ'),
            )
        );
        $company_info=\Ivy::app()->user->getState('company_info');
        if ($company_info['has_invo']!='Y') 
            unset($_nav['仓库管理']);
        else
            unset($_nav['库存消耗管理']);
        return $_nav;
    }


    /**
     * 门店经理导航数据
     *@return array 
     */
    public function JLNav(){
        return $_nav=array(
            '首页'=>$this->url('manager/index/index'),
            '客户管理'=>$this->url('customer/index/index'),
            '邀约本'=>$this->url('rebook/index/index'),
            '交易记录'=>$this->url('pos/sale/index'),
            '数据报表'=>$this->url('report/index/index'),
            '房间管理'=>$this->url('admin/room/index'),
            //'业绩分配'=>$this->url('manager/index/target'),
        );
    }
    /**
     * 总经理导航数据  （未开发）
     *@return array 
     */
    public function ZJLNav(){
        return $_nav=array(
            '首页'=>$this->url('boss/index/index'),
            '客户管理'=>$this->url('customer/index/index'),
            '邀约本'=>$this->url('rebook/index/index'),
            '交易记录'=>$this->url('pos/sale/index'),
            '数据报表'=>$this->url('report/index/index'),
            '业绩分配'=>$this->url('boss/index/target'),
            '续费升级'=>$this->url('sms/upgrade/index'),
        );
    }


	/**
     * 导航icon
     *@return string 
     */
    public static function icon($name){
        $list=array(
            '首页'   =>'fa-th-large',
			'POS开单'  =>'fa-money',
			'交易记录' =>'fa-table',
			'客户管理'=>'fa-user',
            '部门管理'=>'fa-sitemap',
            '门店管理'=>'fa-files-o',
            '房间管理'=>'fa fa-columns',
            '会员卡管理'=>'fa-book',
            '供应商管理'=>'fa-cutlery',
            '员工管理'=>'fa-comments',
            '商品管理'=>'fa-flask',
            '项目管理'=>'fa-magic',
            '活动管理'=>'fa-building-o',
            '卡券管理'=>'fa-edit',
            '功能设置'=>'fa-leaf',
            '数据报表'=>'fa-bar-chart-o',
            '寄存管理'=>'fa-eject',
            '发送消息'=>'fa-envelope',
            
            
            
        );
        if(in_array($name,array_keys($list))){
            return $list[$name];
        }
        return 'fa-retweet';
    }
    
    /**
     * 导航渲染
     *@param array $_nav 导航数组，中文索引，支持2级
     *@return string 
     */ 
    public function _show($_nav,$light_url){
        if(!is_array($_nav)) throw new CException(500,'导航无效参数！');
        //需要被点亮的URL
        $light_url = is_null($light_url)?$this->getCUrl():$light_url;
        $nav_list = array();
        foreach ($_nav as $key => $nav) {
            $tmp=[];
            $tmp["t_name"]  =$key;
            $tmp["t_icon"]   =self::icon($key);

            if(!is_array($nav)){
                $tmp["t_url"]   =$nav;
                $tmp["t_active"]= $nav==$light_url?"active":"";
                $tmp["child"]   =[];
            }else{
                $tmp["t_url"]   ='javascript:;';
                $tmp["t_active"]= "";
                $tmp["child"]   =[];
                foreach ($nav as $k_c => $n_c) {
                    $t_c=["name"=>$k_c,"url"=>$n_c,"active"=>""];
                    if($n_c==$light_url){
                        $tmp["t_active"]=$t_c["active"]="active";
                    }
                    $tmp["child"][]=$t_c;
                }
            }
            $nav_list[]= $tmp;
        }
        return $this->assign(array('nav_list'=>$nav_list))->render();
    }

    /**
     * 获取当前url
     * @return [type] [description]
     */
    public function getCUrl(){
    	return $this->url(implode('/',\Ivy::app()->_route->getRouter()));
    }


}