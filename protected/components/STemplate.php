<?php
use Ivy\core\Template;
use Ivy\core\CException;
/**
 * 项目自定义模引擎
 */
class STemplate extends Template
{

	public function init(){
		//默认电亮当前action导航 无bar扩展
		$uri=implode('/', $this->controller->getRouter());
		$this->assign('light_nav',$this->url($uri));
	}

	/**
	 * [datagrid description]
	 * @param  [type] $conf [description]
	 * @return [type]       [description]
	 */
	public function datagrid($conf){
		return \zii\GridView::run($conf,$this);
	}

	/**
	 * 行数据eval结果
	 * @param  [type] $_expression_ [description]
	 * @param  array  $_data_       [description]
	 * @return [type]               [description]
	 */
	public function evalStr($_expression_,$_data_=array()){
		if(is_string($_expression_)){
			extract($_data_);
			return @eval('return '.$_expression_.';');
		}else{
			$_data_[]=$this;
			return call_user_func_array($_expression_, $_data_);
		}
	}
	/**
	 * 标准下拉框格式
	 * @param  [type] &$arr [description]
	 * @param  [type] $k    [description]
	 * @param  [type] $v    [description]
	 * @return [type]       [description]
	 */
	public function optArr(&$arr,$k,$v){
		$res=array();
		if(empty($arr)) return;
		foreach ($arr as $data) {
			$res[$data[$k]]=$data[$v];
		}
		return $res;
	}


	/**
	 * HTML select 简写
	 * @param [type] $arr    [description]
	 * @param [type] $get    [description]
	 * @param [type] $config [description]
	 */
	public function dropDownList($name=null,$arr,$get=null,$config=null)
	{
		if($name)
			$config['name']=$name;
		$sel_html='<select {sel_config}>{op_html}</select>';
		$op_html='<option value="">--请选择--</option>';
		foreach ((array)$arr as $key => $value) {
			$op_html.="<option value=\"{$key}\" ".(($get==$key)?'selected':'')." {op_config}>{$value}</option>";
		}
		$op_config='';
		if (isset($config['op_config'])) {
			foreach ((array)$config['op_config'] as $key => $value) {
				$op_config.=$key.'="'.$value.'" ';
			}
			unset($config['op_config']);
		}
		$op_html=@str_replace('{op_config}',$op_config,$op_html);
		$sel_config='';
		if (isset($config)) {
			foreach ((array)$config as $key => $value) {
				$sel_config.=$key.'="'.$value.'" ';
			}
			unset($config);
		}
		$sel_html=@str_replace('{sel_config}',$sel_config,$sel_html);
		$sel_html=@str_replace('{op_html}',$op_html,$sel_html);
		return $sel_html;
	}
	/**
	 * HTML checkBox 简写
	 * @param [type] $arr    [description]
	 * @param [type] $get    [description]
	 * @param [type] $config [description]
	 */
	public function checkBox($name=null,$arr,$get,$config=null)
	{
		if($name)
			$config['name']=$name;
		$in_html='';
		foreach ((array)$arr as $key => $value) {
			$in_html.="<input value=\"{$key}\"  type=\"radio\" ".(($get==$key)?'checked="checked"':'')." {html_config}>{$value}";
		}
		$html_config='';
		if (isset($config)) {
			foreach ((array)$config as $key => $value) {
				$html_config.=$key.'="'.$value.'" ';
			}
			unset($config);
		}
		$in_html=@str_replace('{html_config}',$html_config,$in_html);
		return $in_html;
	}
	
}