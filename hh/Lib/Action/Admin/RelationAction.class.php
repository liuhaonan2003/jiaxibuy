<?php 
/**
 * 
 * Relation(产品对应模具关系类)
 *
 */
class RelationAction extends AdminbaseAction
{
	protected $arrStatus;
    function _initialize()
    {
		parent::_initialize();
		//判定结果  1自制 2外购
		$this->arrStatus = array(1=>"自制 ",2=>"外购 ");
    }
    /**
     * 主页
     */
    public function index()
    {
    	$this->_list(MODULE_NAME,'','',false,10);
        $this->display();
    }
	protected function _list($modelname, $map = '', $sortBy = '', $asc = false ,$listRows = 15) {
		$model = M($modelname);
		$id=$model->getPk ();
		$this->assign ( 'pkid', $id );

		if (isset ( $_REQUEST ['order'] )) {
			$order = $_REQUEST ['order'];
		} else {
			$order = ! empty ( $sortBy ) ? $sortBy : $id;
		}
		if (isset ( $_REQUEST ['sort'])) {
			$_REQUEST ['sort']=='asc' ? $sort = 'asc' : $sort = 'desc';
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}


		$_REQUEST ['sort'] = $sort;
		$_REQUEST ['order'] = $order;

		$keyword=$_REQUEST['keyword'];
		$searchtype=$_REQUEST['searchtype'];
		$groupid =intval($_REQUEST['groupid']);
		$catid =intval($_REQUEST['catid']);
		$posid =intval($_REQUEST['posid']);
		$typeid =intval($_REQUEST['typeid']);

		if(!empty($keyword) && !empty($searchtype)){
			if($searchtype == 'mf_sn'){
				$arr = split(",", "ext1,ext2,ext3,ext4,ext5,ext6,ext7,ext8,ext9,ext10,ext11,ext12,ext13,ext14,ext15,ext16,ext17");
				$arr_where = array();
				foreach ($arr as $key => $value) {
					$arr_where[$value]=array('like','%'.$keyword.'%');
					$arr_where['_logic'] = 'or';
				}
				$map['_complex'] = $arr_where;
			}else{
				$map[$searchtype]=array('like','%'.$keyword.'%');
			}
		}
		if($groupid)$map['groupid']=$groupid;
		if($catid)$map['catid']=$catid;
		if($posid)$map['posid']=$posid;
		if($typeid) $map['typeid']=$typeid;
		if(isset($_REQUEST['status']) && ( $_REQUEST['status']==='0' || $_REQUEST['status']>0)){
			$map['status']=intval($_REQUEST['status']);
		}else{
			unset($_REQUEST['status']);
		}

		$this->assign($_REQUEST);
		//取得满足条件的记录总数
		$count = $model->where ( $map )->count ( $id );
		//F ( '_sql_' . $t, $model->getLastSql() );
		if ($count > 0) {
			import ( "@.ORG.Page" );
			//创建分页对象
			if (! empty ( $_REQUEST ['listRows'] )) {
				$listRows = $_REQUEST ['listRows'];
			}
			$p = new Page ( $count, $listRows );
			//分页查询数据

			$field=$this->module[$this->moduleid]['listfields'];
			$voList = $model->field($field)->where($map)->order( "`" . $order . "` " . $sort)->limit($p->firstRow . ',' . $p->listRows)->findAll ( );
			//dump($model->getLastSql());
			//分页跳转的时候保证查询条件
			foreach ( $map as $key => $val ) {
				if (! is_array ( $val )) {
					$p->parameter .= "$key=" . urlencode ( $val ) . "&";
				}
			}

			$map[C('VAR_PAGE')]='{$page}';
			$page->urlrule = U($modelname.'/index', $map);


			//分页显示
			$page = $p->show ();
			//列表排序显示
			$sortImg = $sort; //排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列'; //排序提示
			$sort = $sort == 'desc' ? 1 : 0; //排序方式
			
			/*foreach ($voList as $xkey => $xvalue) {
				foreach ($xvalue as $ykey => $yvalue) {
					if($ykey=='mf_id'){
						$arr = $this->select_all("moldfile",array('id'=>$yvalue),"m_title,mc_id",false);
						$xvalue[$ykey] = $arr['m_title'];
						if(!empty($arr['mc_id'])){
							$arr = $this->select_all("moldcategory",array('id'=>$arr['mc_id']),"title",false);
							$xvalue['mc_id'] = $arr['title'];
						}
					}elseif($ykey=='p_type'){
						$xvalue[$ykey] = $this->arrStatus[$yvalue];
					}
				}
				$voList[$xkey] = $xvalue;
			}*/
			
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'page', $page );
		}
		return;
	}
	/**
	 * 关联模具
	 * */
	function moldfile(){
		$id = $_REQUEST ['id'];
		$relationinfo = $this->select_all("relation",array('id'=>$id),"ext1,ext2,ext3,ext4,ext5,ext6,ext7,ext8,ext9,ext10,ext11,ext12,ext13,ext14,ext15,ext16,ext17",false);
		
		$str = 'ext1,ext2,ext6,ext10,ext14';
		$list = array();
		foreach ($relationinfo as $key => $value) {
			if(strpos($str, $key) !== false){
				//dump($key);
			}else{
				if(!empty($value)){
					$str = $value;
					$star = substr($str,strlen($str)-1,1);
					$newvalue = substr($str,0,strlen($str)-1);
					if($star == '*'){
						$temp = $this->select_moldfile("moldfile",array('m_sn'=>$newvalue),"id,m_sn,m_od,mu_total,ms_location,mc_position,mr_number",true);
						foreach ($temp as $key => $yvalue) {							
							$list[$yvalue['m_sn']]=$yvalue;
						}
					}else{
						$temp = $this->select_all("moldfile",array('m_sn'=>$value),"id,m_sn,m_od,mu_total,ms_location,mc_position,mr_number",false);
						if(!empty($temp)){
							$list[$value] = $temp;
						}
					}
				}
			}
		}
		$this->assign('moldfile', $list);
		$this->display();
	}
	/**
	 * 申请模具入库
	 * */
	function addmoldlogging(){
  		if(empty($_POST['parent'])){
  			exit(json_encode(array('status'=>0,'msg'=>'数据ID不可为空!','debug'=>__LINE__)));
  		}
		$m_info = $this->select_all("moldfile",array('id'=>$mf_id),"id,mr_number",false);
		
		$_POST ['mf_id'] = $_POST['parent'];
		$_POST ['total'] = $m_info['mr_number'];
    	$_POST ['ctime'] = time();
		
		$result = $this->_add('moldlogging',$_POST);
		
		if ($result !==false) {
			exit(json_encode(array('status'=>1,'msg'=>'成功导入!')));
        } else {
			exit(json_encode(array('status'=>0,'msg'=>'导入数据有误!')));
        }
	}
	function show(){
		$id = intval ( $_REQUEST ['id'] );
		$do = $_REQUEST ['do'];
		$isajax = $_REQUEST ['isajax'];
		$this->assign ( 'isajax', $isajax );
		$this->assign ( 'do', $do );
		$this->assign ( 'id', $id );
		if ($_REQUEST ['dosubmit']) {
			switch ($do) {
				case 'show' :
					//$this->update();
					$this->assign ( 'dialog', '1' );
					$this->success (L('正在关闭窗口...'));
					break;
			}
			exit ();
		}
		switch ($do) {
			case 'show';
				$name = MODULE_NAME;
		        $model = M ( $name );
		        $pk=ucfirst($model->getPk ());
		        $id = $_REQUEST [$model->getPk ()];
		        if(empty($id))   $this->error(L('do_empty'));
		        $do='getBy'.$pk;
		        $vo = $model->$do ( $id );
		        if($vo['setup']) $vo['setup']=string2array($vo['setup']);
		        $this->assign ( 'vo', $vo );
				break;
		}
		$this->display ();
	}
	/**
	 * 批量导入
	 * */
	function batch(){
		$do = $_REQUEST ['do'];
		$this->assign ( 'do', $do );
		if ($_REQUEST ['dosubmit']) {
			switch ($do) {
				case 'batch' :
					if ( $_FILES ['data_xls']['size'] && ($filePath = $this->_upload()) ) {						
						import("@.ORG.PHPExcel",'','.php');
						//import("@.Com.PHPExcel.IOFactory",'','.php');
						import("@.ORG.PHPExcel.Reader.Excel5", '','.php');
						import("@.ORG.PHPExcel.Reader.Excel2007",'','.php');
						if (! file_exists ( $filePath )) {
							exit ( "文件'$filePath'不存在.\n" );
						}						
						$PHPExcel = new PHPExcel ();
						$PHPReader = new PHPExcel_Reader_Excel2007 ();
						if (! $PHPReader->canRead ( $filePath )) {
							$PHPReader = new PHPExcel_Reader_Excel5 ();
							if (! $PHPReader->canRead ( $filePath )) {
								echo 'no Excel';
								return;
							}
						}
						
						$objPHPExcel = $PHPReader->load ( $filePath );
						$sheet = $objPHPExcel->getSheet ( 0 ); // 读取第一個工作表(编号从 0 开始)
						//$sheet = $objPHPExcel->getSheetByName ( $sheet ); // 读取第一個工作表(编号从 0 开始)
						$highestRow = $sheet->getHighestRow (); // 取得总行数
						$highestColumn = $sheet->getHighestColumn (); // 取得总列数
						$arr = array (
								1 => 'A',
								2 => 'B',
								3 => 'C',
								4 => 'D',
								5 => 'E',
								6 => 'F',
								7 => 'G',
								8 => 'H',
								9 => 'I',
								10 => 'J',
								11 => 'K',
								12 => 'L',
								13 => 'M',
								14 => 'N',
								15 => 'O',
								16 => 'P',
								17 => 'Q',
								18 => 'R',
								19 => 'S',
								20 => 'T',
								21 => 'U',
								22 => 'V',
								23 => 'W',
								24 => 'X',
								25 => 'Y',
								26 => 'Z'
						);
						$column = array_search ( $highestColumn, $arr );
						//读取表中的数据
						$data = array ();
						for($x = 5; $x <= $highestRow + 1; $x ++) {
							for($i=0;$i<=26;$i++){
								$row = '';
								$row = $sheet->getCellByColumnAndRow ( $i, $x )->getValue ();
								//echo "{$arr[$i+1]}{$x}:".$row.'<br>';
								$data[$x][$i]=trim($row);
							}
						}
						$t = time ();
						echo "核对数据：<br/>";
						$sql = array ();
						$geshu = 0;//统计需要更新的个数
						//$msg = '';//审核信息
						//$only = short ( $t );
						//$only = $t;
						//$del=0;
						foreach ($data as $key => $value) {
							$str = array();//临时记录sql语句的
							$str['p_sn']=$value['0'];
							$str['p_title_format']=$value['1'];
							$str['c_sn']=$value['3'];
							$str['p_model']=$value['2'];
							$str['p_type']=$value['4'];
							
							$str['ext1']=$value['5'];
							$str['ext2']=$value['6'];
							$str['ext3']=$value['7'];
							$str['ext4']=$value['8'];
							$str['ext5']=$value['9'];
							$str['ext6']=$value['10'];
							$str['ext7']=$value['11'];
							$str['ext8']=$value['12'];
							$str['ext9']=$value['13'];
							$str['ext10']=$value['14'];
							$str['ext11']=$value['15'];
							$str['ext12']=$value['16'];
							$str['ext13']=$value['17'];
							$str['ext14']=$value['18'];
							$str['ext15']=$value['19'];
							$str['ext16']=$value['20'];
							$str['ext17']=$value['21'];
							
							$str['remark']=$value['22'];
							$sql[$value['0']]['data']=$str;
							$sql[$value['0']]['act']='INSERT';
							$geshu ++;
						}
						$_SESSION ['hash'] = $t;
						F ( 'relation_excel_temp' . $t, $sql );
						echo "<a href='?g=Admin&m=Relation&a=writedata&hash={$t}'>共（{$geshu}）条数据需要插入，</a><br>";
						//echo $msg;
						//echo "<br><a href='?g=Admin&m=Relation&a=writerelation&hash={$t}'>共（{$geshu}）条数据需要更新，其中{$del}条数据会被删除</a>";
					}else {
						$this->error ( '文件上传失败' );
					}
					break;
			}
			exit ();
		}
		switch ($do) {
			case 'batch';
				break;
		}
		$this->display ();
	}
	function writedata(){
		if ($_SESSION ['hash'] != $_GET ['hash'] || empty ( $_GET ['hash'] )) {
			exit ( 'hash错误！' );
		}
		$name = MODULE_NAME;
		$dao=M($name);
		$data = F ( 'relation_excel_temp' . $_GET ['hash'] );
		if ($data) {
			$err = 0;
			$suc = 0;
			$msg = '';
			foreach ( $data as $v ) {
				$str='';
				$sql =array();
				if($v['act']=='INSERT'){
					$sql =$v['data'];
					$return=$dao->add($sql);
					//$msg .="\n\n{$dao->getLastSql()}\n";
					//$this->tags_status_all(3,$sql['id'],$sql['status']);//
					$str='插入';
				}
				if ($return) {
					$suc ++;
					$msg .= "\n\n{$v['data']['p_sn']}   ---{$str}成功！\n";
				} else {
					$err ++;
					$msg .= "\n\n{$v['data']['p_sn']}   ---{$str}失败！\n";
				}
				$msg .= $dao->getLastSql();
			}
			echo "共插入操作 :" . ($err + $suc) . "次       成功：$suc 次 ；  失败：$err 次  <br><br>";
			echo '<textarea cols="80" rows="50">' . htmlspecialchars ( $msg ) . '</textarea>';
			F ( 'relation_excel_temp' . $_SESSION ['hash'], null );
			unset ( $_SESSION ['hash'] );
		} else {
			echo "<br>没有可导入的数据！";
		}
	}
    
    /**
     * 添加
     *
     */

    function add() {
        $name = MODULE_NAME;        
        //$this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn"));
        $this->display ($name.'_edit');
    }
    function insert() {        
        $name = MODULE_NAME;
        $model = D ($name);
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if ($model->add() !==false) {
            //dump($model->getLastSql());
            if(in_array($name,$this->cache_model)) savecache($name);
            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
            $this->success (L('add_ok'));
        } else {
            $this->error (L('add_error').': '.$model->getDbError());
        }
    }
    
    /**
     * 更新
     *
     */

    function edit() {
        $name = MODULE_NAME;
        $model = M ( $name );
        $pk=ucfirst($model->getPk ());
        $id = $_REQUEST [$model->getPk ()];
        if(empty($id))   $this->error(L('do_empty'));
        $do='getBy'.$pk;
        $vo = $model->$do ( $id );
        if($vo['setup']) $vo['setup']=string2array($vo['setup']);
        $this->assign ( 'vo', $vo );
        //$this->assign('moldfile', $this->select_all("moldfile","","id,m_title"));
        $this->display ();
    }
    function update() {
        $name = MODULE_NAME;
        $model = D ( $name );
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if (false !== $model->save ()) {
            if(in_array($name,$this->cache_model)) savecache($name);
            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
            $this->success (L('edit_ok'));
        } else {
            $this->success (L('edit_error').': '.$model->getDbError());
        }
    }
}