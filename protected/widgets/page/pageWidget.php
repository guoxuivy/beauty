<?php
namespace page;
use Ivy\core\Widget;
use Ivy\core\CException;
class PageWidget extends Widget
{
    
	public function run($data) {
        $tag='page';//分页参数
		$res = $this->_show($data,$tag);
		return $res;
	}
    /**
     * 分页渲染
     * @param  array &$data [description]
     * @param  string $tag   [description]
     * @return string
     */
    public function _show($data,$tag){
        if((!is_array($data)) || (empty($tag))) throw new CException(500,'分页无效参数！');
        $g_row=@(int)$_GET['row'];
        $data=$data['data']['pagener'];
        $str='<div class="row">';

        $str.='<div class="col-sm-12">';
            if ($data['pageNums']>1) {

                $str.='<span style="padding: 8px 12px;display:inline-block">共 '.$data['recordsTotal'].' 条</span><div class="dataTables_paginate paging_simple_numbers"><ul class="pagination">';

                $str.='<li class="paginate_button previous " aria-controls="sample_1" tabindex="0" id="sample_1_previous"><a href="'.$this->getCUrl($tag,$data['currentPage']-1).'"><i class="fa fa-angle-left"></i></a></li>';
                
                foreach ($data['linkList'] as $key => $value) {
                    $str.='<li class="paginate_button '.($data['currentPage']==$value?'active':'').'" aria-controls="sample_1" tabindex="0"><a href="'.$this->getCUrl($tag,$value).'">'.$value.'</a></li>';
                }
                if (end($data['linkList'])<$data['pageNums']) {
                    $str.='<li class="paginate_button " aria-controls="sample_1" tabindex="0"><a href="javascript:;">...</a></li>';
                    $str.='<li class="paginate_button " aria-controls="sample_1" tabindex="0"><a href="'.$this->getCUrl($tag,$data['pageNums']).'">'.$data['pageNums'].'</a></li>';
                }
                if ($data['pageNums']>$data['currentPage']) {
                    $str.='<li class="paginate_button next" aria-controls="sample_1" tabindex="0" id="sample_1_next"><a href="'.$this->getCUrl($tag,$data['currentPage']+1).'"><i class="fa fa-angle-right"></i></a></li>';
                }
                $str.='</ul></div>';
            }
            $str.='</div></div>';

        return $str;
    }

    /**
     * 获取当前url并替换
     * @return [type] [description]
     */
    public function getCUrl($tag,$page=1){
        $param=(array)$_GET;
        unset($param['r']);
        $param[$tag]=$page;
        if(isset($param['t_search'])){
            foreach ($param['t_search'] as $key => $value) {
                $param['t_search['.$key.']']=$value;
            }
            unset($param['t_search']);
        }
    	return $this->url(implode('/',\Ivy::app()->_route->getRouter()),$param);
    }


}