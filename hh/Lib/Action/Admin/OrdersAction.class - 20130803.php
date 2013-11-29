<?php 
/**
 * 
 * Orders(产品订单管理类)
 *
 */
class OrdersAction extends AdminbaseAction
{    
    function _initialize()
    {
		parent::_initialize();	
		$info = $this->select_all("Orders",'','complete_time,ctime');
		$arr = array();
		$temp_date = 0;
		$arr_ctime = array();
		$temp_ctime = 0;
		foreach ($info as $key => $value) {
			if($temp_date != $value['complete_time']){
				$temp_date = trim($value['complete_time']);
				if(empty($temp_date)){
					$arr[$key]['title'] = '空';
					$arr[$key]['complete_time'] = ' ';
				}else{
					$arr[$key]['title'] = $temp_date;
					$arr[$key]['complete_time'] = $temp_date;
				}
			}
			if($temp_ctime != $value['ctime']){
				$temp_ctime = trim($value['ctime']);
				$arr_ctime[$key]['title'] = date('Y-m-d',$temp_ctime);
				$arr_ctime[$key]['ctime'] = $temp_ctime;
			}
		}
		$this->assign('arr_ctime', $arr_ctime);
		$this->assign('arr_complete_time', $arr);	
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
		
		$complete_time =$_REQUEST['complete_time'];
		
		$groupid =intval($_REQUEST['groupid']);
		$catid =intval($_REQUEST['catid']);
		$posid =intval($_REQUEST['posid']);
		$typeid =intval($_REQUEST['typeid']);

		if(!empty($keyword) && !empty($searchtype)){
			$map[$searchtype]=array('like','%'.$keyword.'%');
		}
		
		if($complete_time)$map['complete_time']=$complete_time;
		
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
				$orders_info = array();
				foreach ($xvalue as $ykey => $yvalue) {
					$str = 'id,c_sn,sn,p_sn,p_number';
					if(strpos($str, $ykey) !== false){
						$orders_info[$ykey] = $yvalue;
					}
				}
				$voList[$xkey]['ordersinfo'] = serialize($orders_info);
			}*/
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'page', $page );
		}
		return;
	}

	/**
	 * 从订单查看关联模具
	 * */
	function gorelation(){
		$relation = $this->select_all("relation",array('p_sn'=>$_REQUEST['p_sn']),"*",false);
		$this->assign ( 'vo', $relation );
		$id = $_REQUEST ['id'];
		
		$relationinfo = $this->select_all("relation",array('id'=>$relation['id']),"ext1,ext2,ext3,ext4,ext5,ext6,ext7,ext8,ext9,ext10,ext11,ext12,ext13,ext14,ext15,ext16,ext17",false);
		
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
						$temp = $this->select_moldfile("moldfile",array('m_sn'=>$newvalue),"id,m_sn,m_od,mu_total,ms_location,mc_position,mr_number,log_id",true);
						foreach ($temp as $key => $yvalue) {							
							$list[$yvalue['m_sn']]=$yvalue;
						}
					}else{
						$temp = $this->select_all("moldfile",array('m_sn'=>$value),"id,m_sn,m_od,mu_total,ms_location,mc_position,mr_number,log_id",false);
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
	 * 设置当前模具使用登记
	 * */
	function logging(){
		$id = intval ( $_REQUEST ['id'] );
		$do = $_REQUEST ['do'];
		$isajax = $_REQUEST ['isajax'];
		$type = $_REQUEST['type'];
		$ordersid = $_REQUEST['ordersid'];
		$this->assign ( 'ordersid', $ordersid );
		$this->assign ( 'isajax', $isajax );
		$this->assign ( 'do', $do );
		$this->assign ( 'type', $type );
		$this->assign ( 'id', $id );
		if ($_REQUEST ['dosubmit']) {
			switch ($do) {
				case 'logging' :
					if($type=='insert'){
						$n_return = $_POST['n_return'];
						$ga_id = $_POST['ga_id'];
						$mf_id = $_POST['mf_id'];
				    	if(!empty($_POST['n_return'])){
				        	$_POST['rtime'] = strtotime($_POST['rtime']);
				        }else{
				        	$_POST['rtime'] = 0;
				        }
						$_POST ['ctime'] = strtotime($_POST['ctime']);
						$orders_info = $_POST['orders_info'];
						$_POST['orders_info'] = serialize($_POST['orders_info']);
						$log_id = $this->_add('moldlogging',$_POST);
						if ($log_id !==false) {
				        	if(!empty($n_return)){
					        	$_POST['rtime'] = strtotime($_POST['rtime']);
								$this->_save('moldfile',array('id'=>$mf_id,'mc_position'=>'','log_id'=>0));
					        }else{
					        	$_POST['rtime'] = 0;
					        	$m_info = $this->select_all("moldfile",array('id'=>$mf_id),"id,log_id,mc_position,mr_number,mc_total",false);
					        	if(empty($m_info['mc_position'])){
					        		$info = $this->select_all("grantaddress",array('id'=>$ga_id),"id,address",false);
									if(!empty($info)){
										if($m_info['mr_number'] < $_POST['consume']){
											$this->error (L('模具使用次数不足'));
										}
										$_POST['total'] = $m_info['mr_number'];
										$mc_total = $m_info['mc_total'] + $_POST['consume'];
										$mr_number = $m_info['mr_number'] - $_POST['consume'];
										$this->_save('moldlogging',array('id'=>$log_id,'total'=>$_POST['total']));
										$this->_save('moldfile',array('id'=>$mf_id,'mc_position'=>$info['address'],'log_id'=>$log_id,'mr_number'=>$mr_number,'mc_total'=>$mc_total));
									}
					        	}else{
					        		$this->error (L('模具没空'));
					        	}
					        }
				            //dump($model->getLastSql());
				            $this->assign ( 'dialog', '1' );
				            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index',array('p_sn'=>$orders_info['p_sn'],'ordersid'=>$ordersid)) );
				            $this->success (L('add_ok'));
				        } else {
				            $this->error (L('add_error').': '.$model->getDbError());
				        }
					}
					if($type=='update'){
						if(!empty($_POST['n_return'])){
				        	$_POST['rtime'] = strtotime($_POST['rtime']);
							$info = $this->select_all("grantaddress",array('id'=>$_POST['ga_id']),"id,address",false);
							if(!empty($info)){
								$this->_save('moldfile',array('id'=>$_POST['mf_id'],'mc_position'=>'','log_id'=>0));
							}
				        }else{
				        	$_POST['rtime'] = 0;
							$m_info = $this->select_all("moldfile",array('id'=>$_POST['mf_id']),"id,log_id,mc_position,mr_number,mc_total",false);
				        	if(empty($m_info['mc_position']) && $m_info['log_id']==0){
				        		$info = $this->select_all("grantaddress",array('id'=>$_POST['ga_id']),"id,address",false);
								if(!empty($info)){
									if($m_info['mr_number'] < $_POST['consume']){
										$this->error (L('模具使用次数不足'));
									}
									$_POST['total'] = $m_info['mr_number'];
									$mc_total = $m_info['mc_total'] + $_POST['consume'];
									$mr_number = $m_info['mr_number'] - $_POST['consume'];
									$this->_save('moldfile',array('id'=>$_POST['mf_id'],'mc_position'=>$info['address'],'log_id'=>$_POST['id'],'mr_number'=>$mr_number,'mc_total'=>$mc_total));
								}
				        	}else{
				        		if($m_info['log_id'] != $_POST['id']){
				        			$this->error (L('模具没空'));
				        		}
				        	}
				        }
						$_POST ['ctime'] = strtotime($_POST['ctime']);
						$orders_info = $_POST['orders_info'];
						$_POST['orders_info'] = serialize($_POST['orders_info']);
				       	$log_id = $this->_save('moldlogging',$_POST);
				        if (false !== $log_id) {
							$this->assign ( 'dialog', '1' );
				            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index',array('p_sn'=>$orders_info['p_sn'],'ordersid'=>$ordersid)) );
				            $this->success (L('edit_ok'));
				        } else {
				            $this->success (L('edit_error').': '.$model->getDbError());
				        }
					}
					break;
			}
			exit ();
		}
		switch ($do) {
			case 'logging';
		        $vo = $this->select_all("moldlogging",array('id'=>$id),"*",false);
				if($type=='insert'){
					$ordersinfo = $this->select_all("orders",array('id'=>$ordersid),"id,c_sn,sn,p_sn,p_model,p_number",false);
					$vo['orders_info_show'] = $ordersinfo;
					$vo['orders_info'] = $ordersinfo;
					$vo['consume'] = $ordersinfo['p_number'];
				}
				if($type=='update'){
					$vo['orders_info_show'] = unserialize($vo['orders_info']);
					$vo['orders_info'] = $vo['orders_info_show'];
				}
		        $this->assign ( 'vo', $vo );
				$this->assign('grantaddress', $this->select_all("grantaddress","","id,address"));
				$this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn,mr_number"));
		        $this->assign('granttype', $this->select_all("granttype","","id,title"));
		        $this->assign('orders', $this->select_all("orders",array('id'=>$ordersid),"id,c_sn,sn,p_sn,p_model,p_number"));
				break;
		}
		$this->display ();
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
					$this->update();
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
						for($x = 3; $x <= $highestRow + 1; $x ++) {
							for($i=0;$i<=26;$i++){
								$row = '';
								//$row = $sheet->getCellByColumnAndRow ( $i, $x )->getValue ();
								$row = $sheet->getCellByColumnAndRow ( $i, $x );
								$value=$row->getValue();
								//echo "{$arr[$i+1]}{$x}:".$row.'<br>';
								if($row->getDataType()==PHPExcel_Cell_DataType::TYPE_NUMERIC){  
								       $cellstyleformat=$row->getParent()->getStyle( $row->getCoordinate() )->getNumberFormat();  
								       $formatcode=$cellstyleformat->getFormatCode();  
								       if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {  
								             $value=gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));  
								       }else{  
								             $value=PHPExcel_Style_NumberFormat::toFormattedString($value,$formatcode);  
								       }  
								} 
								$data[$x][$i]=trim($value);
								//$data[$x][$i]=trim($row);
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
							
  							$str['month_plan']=$value['0'];//月上线计划
							$str['day_plan']=$value['1'];//日上线计划
							$str['add_date']=$value['2'];//增加时间
							$str['z_team']=$value['3'];//扎线班
							$str['b_team']=$value['4'];//包装班
							$str['complete_time']=$value['5'];//出货日期
							$str['pass']=$value['6'];//出/验
							$str['c_sn']=$value['7'];//客户代码
							$str['sn']=$value['8'];//订单号 
							$str['sap']=$value['9'];//SAP号
							$str['po_date']=$value['10'];//po#/日期贴
							$str['p_model']=$value['11'];//客户型号
							$str['p_sn']=$value['12'];//产品编号
							$str['p_format']=$value['13'];//产品规格
							$str['p_type']=$value['14'];//产品种类 
							$str['bale_mode']=$value['15'];//包装类型
							$str['p_number']=$value['16'];//订单数量
							$str['enter_number']=$value['17'];//入库数
							$str['ctime']=time();//注册时间
							
							$sql[$key]['data']=$str;
							$sql[$key]['act']='INSERT';
							$geshu ++;
						}
						$_SESSION ['hash'] = $t;
						F ( 'orders_excel_temp' . $t, $sql );
						echo "<a href='?g=Admin&m=orders&a=writedata&hash={$t}'>共（{$geshu}）条数据需要插入，</a><br>";
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
		$data = F ( 'orders_excel_temp' . $_GET ['hash'] );
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
			F ( 'orders_excel_temp' . $_SESSION ['hash'], null );
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
        //$this->assign('relation', $this->select_all("relation"));
        $this->display ($name.'_edit');
    }
    function insert() {
		$_POST ['ctime'] = strtotime($_POST['ctime']);
		//$_POST ['complete_time'] = strtotime($_POST['complete_time']);
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
        //$this->assign('relation', $this->select_all("relation"));
        $this->display ();
    }
    function update() {
        $_POST ['ctime'] = strtotime($_POST['ctime']);
		//$_POST ['complete_time'] = strtotime($_POST['complete_time']);
        $name = MODULE_NAME;
        $model = D ( $name );
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if (false !== $model->save ()) {
            if(in_array($name,$this->cache_model)) savecache($name);
			$this->assign ( 'dialog', '1' );
            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
            $this->success (L('edit_ok'));
        } else {
            $this->success (L('edit_error').': '.$model->getDbError());
        }
    }
	public function delorders(){
		if($_POST ['d_time']==0){
			exit(json_encode(array('status'=>0,'msg'=>L('delete_error'))));
		}
		$ret = $this->_del_data("DELETE FROM `iic_orders` WHERE ctime = ".$_POST ['d_time']);
		if (false !== $ret) {
			exit(json_encode(array('status'=>1,'msg'=>L('delete_ok'))));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>L('delete_error'))));
		}
	}
}