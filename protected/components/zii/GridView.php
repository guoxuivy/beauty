<?php
namespace zii;
use Ivy\core\CException;
use Ivy\core\CComponent;
/**
 * 表格插件
 * 数据列value构造说明:
 * 'value'=>array('{$model->getType($data["type"])}','model'=>$model),可传入依赖变量 ｛内容为php解析｝
 * 'value'=>"{\In::model()->getType($data["type"])}"
 *
 */
class GridView extends CComponent
{
	static $_tables=array();//支持单页面多table
	public $conf=null;
	public $pager=null;
	public $list=null;
	public $columns=null;
	private static $_counter=0;

	public static function run($conf,&$template){

		$tables=self::$_tables;
		$conf['id']=$t_id=$conf['id']?$conf['id']:'table_default'.self::$_counter++;
		if(isset($tables[$t_id]) && $tables[$t_id] instanceof GridView){
			$class=$tables[$t_id];
		}else{
			$class = new GridView($conf,$template);
			self::$_tables[$t_id]=$class;
		}
		return $class->_init();
	}
	public function __construct($conf,&$template){
		$this->attachBehavior($template);//模版类注入
		$this->conf=$conf;
		$this->pager=$conf['dataProvider']['page_code'];
		$this->list=$conf['dataProvider']['list'];
		$this->columns=$conf['columns'];
	}
	
	public function _init(){
		$lastFloat= ($this->conf['lastFloat']===false)?'false':'true'; //默认开启
		$str = "
			<!--table_table-->	
			<div id='".$this->conf['id']."' class='table_table table_container'>
			<!--<div class='table_title_tj'>...</div>-->
			
			<div class='table_hiddle'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0' last-float='".$lastFloat."'>";
		$str .= "<thead>";
		$str .=		$this->buildHeader();

		$str .=		$this->buildSearch();
		$str .= "</thead>";
		$str .= "<tbody>";
		$str .=		$this->buildRow();
		$str .= "</tbody>";
		$str .= "
			</table>
			</div><!--table_hiddle!end-->
			".$this->pager."</div><!--table_table!end-->";
		
		echo  $str;
	}

	/**
	 * 构造表头
	 * @return [type] [description]
	 */
	public function buildHeader(){
		$str_check="";
		if(isset($this->conf['check'])){
			$str_check="<th class='table_check'><input type='checkbox'></th>";
		}
		$str  =	"<tr class='table_title'>".$str_check;
			foreach ($this->columns as $key => $col) {
				$style = isset($col['headerCss'])?$style=" style='".$col['headerCss']."'":"";
				$str.="<th {$style}>{$col['header']}</th>";
			}
		$str  .="</tr>";
		return $str;
	}

	/**
	 * 构造表搜索
	 * @return [type] [description]
	 */
	public function buildSearch(){
		$li=array();
		$has_search=false;//默认没有search数据
		if(isset($this->conf['check'])){
			$li[]="<td style='height:1px;'></td>";
		}
		foreach ($this->columns as $key => $col) {
			if(!isset($col['filter'])){
				$li[]="<td style='height:1px;'></td>";
				continue;
			}
			$has_search=true;
			if(is_array($col['filter'])){
				$data_col=$col['filter'][0];//字段名
				$data_list=$col['filter'][1];//下拉列表
				$data_def=$col['filter'][2];//默认值

				$li[]="<td>".$this->dropDownList('',$data_list,$data_def,array('data-col'=>$data_col))."</td>";

			}else{
				$li[]="<td><input data-col='".$col['filter']."' type='text' /></td>";
			}
		}
		if($li&&$has_search){
			if ($this->conf['search']===true){
				@setcookie('show_search','true'); //强制打开
				$str=  '<tr class="table_search" style="display:table-row;">';
			}elseif($this->conf['search']===false){
				@setcookie('show_search','false'); //强制打开
				$str=  $str=  '<tr class="table_search" style="display:none;">';
			}else{
				$str=  '<tr class="table_search">';
			}
			
			$str.=	implode("",$li);
						
			$str.= '</tr>';
			return $str;
		}
	}


	/**
	 * 构造表数据行
	 * @return [type] [description]
	 */
	public function buildRow(){
		$str="";
		foreach ($this->list as $key=>$data){
			$str .=   "<tr class='table_list clear'>";
					if(isset($this->conf['check'])){
						$str.="<td><input value='".$data[$this->conf['check']]."' name='table_check' type='checkbox' /></td>";
					}
					foreach ($this->columns as $col) {
						$str.=$this->buildDataCell($data,$col);
					}
			$str .=   "</tr>";
			
		}
		return $str;
	}

	/**
	 * 构造表数单元格据体
	 * @return [type] [description]
	 */
	public function buildDataCell($data,$col){
		if(isset($col['value'])){
			//内部方法执行
			if(is_array($col['value'])){
				$str=array_shift($col['value']);
				$parm=array_merge($col['value'],array('data'=>$data,'col'=>$col));
				return "<td>".$this->doString($str,$parm)."</td>";
			}else{
				return "<td>".$this->doString($col['value'],array('data'=>$data,'col'=>$col))."</td>";
			}
		}elseif(isset($col['template'])){
			//非数据单元格，操作子类的
			return "<td title='操作' rel='".$data[$col['rel']]."'>".$this->buildAct($data,$col)."</td>";
		}else{
			return "<td>{$data[$col['name']]}</td>";
		}

	}

	/**
	 * 带php执行语句的字符串 执行替换
	 * @param  [type] $str  [description]
	 * @param  [type] $parm [description]
	 * @return [string]       [description]
	 */
	public function doString($str,$parm){
		$matches=array();
		preg_match_all('/{(.*)}/isU', $str, $matches);
		if(isset($matches[0]) && $matches[0]){
			foreach ($matches[0] as $k=>$eval_str) {
				$str = str_replace($eval_str, $this->evalStr($matches[1][$k],$parm), $str);
			}
		}
		return $str;
	}

	

	/**
	 * 操作单元格 （模版+自定义）注意逗号转义
	 * @return [type] [description]
	 */
	public function buildAct($data,$col){
		
		$act_list = explode(',', $col['template']);
		$str="";
		foreach ($act_list as $act){
			$tmp = $this->getButton($act,$data,$col);
			if($tmp){
				$str.=$tmp;//模版
			}else{
				$str.=$this->doString($act,array('data'=>$data,'col'=>$col));//自定义
			}
		}
		return $str;
	}

	/**
	 * 按钮模版
	 * @param  [type] $case [description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function getButton($case,$data,$col){
		switch ($case) {
			case 'view':
				$str='<a class="view khca" href="javascript:;"><span class="khca_son"><img src="'.$this->basePath('public').'/images/kh_05.png"></span>查看</a>';
				break;
			case 'view+':
				$str='<a href="'.$this->url('view',array($col['rel']=>$data[$col['rel']])).'" class="view khca"><span class="khca_son"><img src="'.$this->basePath('public').'/images/kh_05.png"></span>查看</a>';
				break;
			case 'edit':
				$str='<a class="edit khca" href="javascript:;"><span class="khca_son"><img src="'.$this->basePath('public').'/images/kh_05.png"></span>查看</a>';
				break;
			case 'edit+':
				$str='<a href="'.$this->url('edit',array($col['rel']=>$data[$col['rel']])).'" class="view khca"><span class="khca_son"><img src="'.$this->basePath('public').'/images/kh_05.png"></span>查看</a>';
				break;
			case 'delete':
				$str='<a class="delete" href="javascript:;">删除</a>';
				break;
			case 'delete+':
				$str='<a href="'.$this->url('delete',array($col['rel']=>$data[$col['rel']])).'" class="delete">删除</a>';
				break;
			case 'change':
				$str='<a class="edit" href="javascript:;">启用</a>';
				break;
			default:
				$str="";
				break;
		}
		return $str;

	}

	
}