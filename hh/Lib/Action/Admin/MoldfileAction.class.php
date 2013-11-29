<?php
/**
 *
 * Moldfile(模具档案类)
 *
 */
class MoldfileAction extends AdminbaseAction {
	function _initialize() {
		parent::_initialize();
		$status = array(1 => '正常使用', 2 => '返厂维修 ', 3 => '模具报废');
		$this -> assign('arr_status', $status);
		$this -> assign('moldtype', $this -> select_all("moldtype"));
		$this -> assign('moldfactory', $this -> select_all("moldfactory"));
	}

	/**
	 * 主页
	 */
	public function index() {
		$this -> _list(MODULE_NAME, '', '', false, 10);
		$this -> display();
	}

	protected function _list($modelname, $map = '', $sortBy = '', $asc = false, $listRows = 15) {
		$model = M($modelname);
		$id = $model -> getPk();
		$this -> assign('pkid', $id);

		if (isset($_REQUEST['order'])) {
			$order = $_REQUEST['order'];
		} else {
			$order = !empty($sortBy) ? $sortBy : $id;
		}
		if (isset($_REQUEST['sort'])) {
			$_REQUEST['sort'] == 'asc' ? $sort = 'asc' : $sort = 'desc';
		} else {
			$sort = $asc ? 'asc' : 'desc';
		}

		$_REQUEST['sort'] = $sort;
		$_REQUEST['order'] = $order;

		$keyword = $_REQUEST['keyword'];
		$searchtype = $_REQUEST['searchtype'];

		$mt_id = intval($_REQUEST['mt_id']);
		$mf_id = intval($_REQUEST['mf_id']);

		$groupid = intval($_REQUEST['groupid']);
		$catid = intval($_REQUEST['catid']);
		$posid = intval($_REQUEST['posid']);
		$typeid = intval($_REQUEST['typeid']);

		if (!empty($keyword) && !empty($searchtype)) {
			$map[$searchtype] = array('like', '%' . $keyword . '%');
		}

		if ($mt_id)
			$map['mt_id'] = $mt_id;
		if ($mf_id)
			$map['mf_id'] = $mf_id;

		if ($groupid)
			$map['groupid'] = $groupid;
		if ($catid)
			$map['catid'] = $catid;
		if ($posid)
			$map['posid'] = $posid;
		if ($typeid)
			$map['typeid'] = $typeid;
		if (isset($_REQUEST['status']) && ($_REQUEST['status'] === '0' || $_REQUEST['status'] > 0)) {
			$map['status'] = intval($_REQUEST['status']);
		} else {
			unset($_REQUEST['status']);
		}

		$this -> assign($_REQUEST);

		//取得满足条件的记录总数
		$count = $model -> where($map) -> count($id);
		if ($count > 0) {
			import("@.ORG.Page");
			//创建分页对象
			if (!empty($_REQUEST['listRows'])) {
				$listRows = $_REQUEST['listRows'];
			}
			$p = new Page($count, $listRows);
			//分页查询数据

			$field = $this -> module[$this -> moduleid]['listfields'];
			$voList = $model -> field($field) -> where($map) -> order("`" . $order . "` " . $sort) -> limit($p -> firstRow . ',' . $p -> listRows) -> findAll();
			//分页跳转的时候保证查询条件
			foreach ($map as $key => $val) {
				if (!is_array($val)) {
					$p -> parameter .= "$key=" . urlencode($val) . "&";
				}
			}

			$map[C('VAR_PAGE')] = '{$page}';
			$page -> urlrule = U($modelname . '/index', $map);

			//分页显示
			$page = $p -> show();
			//列表排序显示
			$sortImg = $sort;
			//排序图标
			$sortAlt = $sort == 'desc' ? '升序排列' : '倒序排列';
			//排序提示
			$sort = $sort == 'desc' ? 1 : 0;
			//排序方式

			foreach ($voList as $xkey => $xvalue) {
				foreach ($xvalue as $ykey => $yvalue) {
					if ($ykey == 'mt_id') {
						$arr = $this -> select_all("moldtype", array('id' => $yvalue), "title", false);
						$xvalue[$ykey] = $arr['title'];
					}/* elseif($ykey=='mc_id'){
					 $arr = $this->select_all("moldcategory",array('id'=>$yvalue),"title",false);
					 $xvalue[$ykey] = $arr['title'];
					 }*/
					elseif ($ykey == 'mf_id') {
						$arr = $this -> select_all("moldfactory", array('id' => $yvalue), "id,sn,title", false);
						$xvalue[$ykey] = $arr['id'];
						$xvalue['mf_title'] = $arr['title'];
						$xvalue['sn'] = $arr['sn'];
					}
					/*elseif($ykey=='mv_id'){
					 $arr = $this->select_all("moldvesting",array('id'=>$yvalue),"title",false);
					 $xvalue[$ykey] = $arr['title'];
					 }*/
				}
				$voList[$xkey] = $xvalue;
			}

			//模板赋值显示
			$this -> assign('list', $voList);
			$this -> assign('page', $page);
		}
		return;
	}

	function show() {
		$id = intval($_REQUEST['id']);
		$do = $_REQUEST['do'];
		$isajax = $_REQUEST['isajax'];
		$this -> assign('isajax', $isajax);
		$this -> assign('do', $do);
		$this -> assign('id', $id);
		if ($_REQUEST['dosubmit']) {
			switch ($do) {
				case 'show' :
					$this -> update();
					break;
			}
			exit();
		}
		switch ($do) {
			case 'show' :
				$name = MODULE_NAME;
				$model = M($name);
				$pk = ucfirst($model -> getPk());
				$id = $_REQUEST[$model -> getPk()];
				if (empty($id))
					$this -> error(L('do_empty'));
				$do = 'getBy' . $pk;
				$vo = $model -> $do($id);
				if ($vo['setup'])
					$vo['setup'] = string2array($vo['setup']);
				$this -> assign('vo', $vo);
				$this -> assign('moldtype', $this -> select_all("moldtype"));
				$this -> assign('moldcategory', $this -> select_all("moldcategory"));
				$this -> assign('moldfactory', $this -> select_all("moldfactory"));
				$this -> assign('moldvesting', $this -> select_all("moldvesting"));
				$this -> assign('moldregister', $this -> select_all("moldregister", array('m_sn' => $vo['m_sn']), "id", false));
				break;
		}
		$this -> display();
	}

	function issn() {
		if (empty($_POST['sn'])) {
			exit(json_encode(array('status' => 0, 'msg' => '模具编号不可为空', 'debug' => __LINE__)));
		}
		$result = true;
		$moldfile = $this -> select_all("moldfile", array('m_sn' => $_POST['sn']), "id", false);
		$moldregister = $this -> select_all("moldregister", array('m_sn' => $_POST['sn']), "id", false);
		if (!empty($_POST['mfid'])) {
			if (!empty($moldfile['id']) && $moldfile['id'] != $_POST['mfid']) {
				$result = false;
			}
		} else {
			if (!empty($moldfile['id'])) {
				$result = false;
			}
		}

		if (!empty($_POST['mrid'])) {
			if (!empty($moldregister['id']) && $moldregister['id'] != $_POST['mrid']) {
				$result = false;
			}
		} else {
			if (!empty($moldregister['id'])) {
				$result = false;
			}
		}

		if ($result !== false) {
			exit(json_encode(array('status' => 1, 'msg' => '模具编号可用')));
		} else {
			exit(json_encode(array('status' => 0, 'msg' => '模具编号已存在')));
		}
	}

	/**
	 * 批量导入
	 * */
	function batch() {
		$do = $_REQUEST['do'];
		$this -> assign('do', $do);
		if ($_REQUEST['dosubmit']) {
			switch ($do) {
				case 'batch' :
					if ($_FILES['data_xls']['size'] && ($filePath = $this -> _upload())) {
						import("@.ORG.PHPExcel", '', '.php');
						//import("@.Com.PHPExcel.IOFactory",'','.php');
						import("@.ORG.PHPExcel.Reader.Excel5", '', '.php');
						import("@.ORG.PHPExcel.Reader.Excel2007", '', '.php');
						if (!file_exists($filePath)) {
							exit("文件'$filePath'不存在.\n");
						}
						$PHPExcel = new PHPExcel();
						$PHPReader = new PHPExcel_Reader_Excel2007();
						if (!$PHPReader -> canRead($filePath)) {
							$PHPReader = new PHPExcel_Reader_Excel5();
							if (!$PHPReader -> canRead($filePath)) {
								echo 'no Excel';
								return;
							}
						}

						$objPHPExcel = $PHPReader -> load($filePath);
						$sheet = $objPHPExcel -> getSheet(0);
						// 读取第一個工作表(编号从 0 开始)
						//$sheet = $objPHPExcel->getSheetByName ( $sheet ); // 读取第一個工作表(编号从 0 开始)
						$highestRow = $sheet -> getHighestRow();
						// 取得总行数
						$highestColumn = $sheet -> getHighestColumn();
						// 取得总列数
						$arr = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 15 => 'O', 16 => 'P', 17 => 'Q', 18 => 'R', 19 => 'S', 20 => 'T', 21 => 'U', 22 => 'V', 23 => 'W', 24 => 'X', 25 => 'Y', 26 => 'Z');
						$column = array_search($highestColumn, $arr);
						//读取表中的数据
						$data = array();
						for ($x = 3; $x <= $highestRow + 1; $x++) {
							for ($i = 0; $i <= 26; $i++) {
								$row = '';
								//$row = $sheet->getCellByColumnAndRow ( $i, $x )->getValue ();
								$row = $sheet -> getCellByColumnAndRow($i, $x);
								$value = $row -> getValue();
								//echo "{$arr[$i+1]}{$x}:".$row.'<br>';
								if ($row -> getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
									$cellstyleformat = $row -> getParent() -> getStyle($row -> getCoordinate()) -> getNumberFormat();
									$formatcode = $cellstyleformat -> getFormatCode();
									if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
										$value = gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
									} else {
										$value = PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
									}
								}
								$data[$x][$i] = trim($value);
								//$data[$x][$i]=trim($row);
							}
						}
						$t = time();
						echo "核对数据：<br/>";
						$sql = array();
						$geshu = 0;
						//统计需要更新的个数
						//$msg = '';//审核信息
						//$only = short ( $t );
						//$only = $t;
						//$del=0;
						foreach ($data as $key => $value) {
							if(!empty($value)){
								$str = array();
								$str_reg = array();
								//临时记录sql语句的
								$m_id = $this -> select_all("moldfile",array('m_sn' => trim($value['1'])), "id", false);
								$mr_id = $this -> select_all("moldregister",array('m_sn' => trim($value['1'])), "id", false);
								
	 							$str['m_sn'] = trim($value['1']);
								$str['m_title'] = $value['3'];
								
								$str_reg['m_sn'] = trim($value['1']);
								$str_reg['m_title'] = $value['3'];								
								$str_reg['applicant'] = $value['16'];
								$str_reg['status'] = 2;
								$str_reg['isstore'] = 1;
								$str_reg['reason'] = $value['4'];
								
								//模具类型
								if(!empty($value['5'])){
									$mt_id = $this -> select_all("moldtype",array('title' => trim($value['5'])), "id", false);
									if(empty($mt_id['id'])){
										$_arr['title'] = trim($value['5']);
										$mt_id['id'] = $this->_add('moldtype',$_arr);
									}
									$str['mt_id'] = $mt_id['id'];
								}							
								
								//厂商编号
								if(!empty($value['14'])){
									$mf_id = $this -> select_all("moldfactory",array('title' => trim($value['14'])), "id", false);
									if(empty($mf_id['id'])){
										$_arr['title'] = trim($value['14']);
										$mf_id['id'] = $this->_add('moldfactory',$_arr);
									}
									$str['mf_id'] = $mf_id['id'];
									
									$str_reg['mfa_id'] = $mf_id['id'];
								}
								
								//模具归属
								if(!empty($value['18'])){
									$mv_id = $this -> select_all("moldvesting",array('title' => trim($value['18'])), "id", false);
									if(empty($mv_id['id'])){
										$_arr['title'] = trim($value['18']);
										$mv_id['id'] = $this->_add('moldvesting',$_arr);
									}
									$str['mv_id'] = $mv_id['id'];
								}
															
								//模具类别
								if(!empty($value['6'])){
									$mc_id = $this -> select_all("moldcategory",array('title' => trim($value['6'])), "id", false);
									if(empty($mc_id['id'])){
										$_arr['title'] = trim($value['6']);
										$mc_id['id'] = $this->_add('moldcategory',$_arr);
									}
									$str['mc_id'] = $mc_id['id'];
								}
									
								$str['m_price'] = $value['15'];
								$str['mc_number'] = $value['7'];
								$str['m_od'] = $value['8'];
								$str['ms_location'] = $value['19'];
								if(!empty($value['17'])){
									$str['mc_time'] = strtotime($value['17']);
								}							
								$str['m_plastic'] = $value['9']+$value['10'];
								$str['remark'] = $value['4'];
								$str['mql'] = $value['9'];
								$str['skl'] = $value['10'];
								$str['mfsn'] = $value['2'];
								
								if(!empty($m_id['id']) && !empty($mr_id['id'])){
									$str['id'] = $m_id['id'];								
									$str_reg['id'] = $mr_id['id'];
									$sql[$key]['act'] = 'UPDATE';
								}else{
									$sql[$key]['act'] = 'INSERT';
								}
								$sql[$key]['data'] = $str;
								$sql[$key]['data_reg'] = $str_reg;
								
								$geshu++;
							}
						}
						$_SESSION['hash'] = $t;
						F('moldfile_excel_temp' . $t, $sql);
						echo "<a href='?g=Admin&m=moldfile&a=writedata&hash={$t}'>共（{$geshu}）条数据需要插入或更新，</a><br>";
						//echo $msg;
						//echo "<br><a href='?g=Admin&m=Relation&a=writerelation&hash={$t}'>共（{$geshu}）条数据需要更新，其中{$del}条数据会被删除</a>";
					} else {
						$this -> error('文件上传失败');
					}
					break;
			}
			exit();
		}
		switch ($do) {
			case 'batch' :
				break;
		}
		$this -> display();
	}

	function writedata() {
		if ($_SESSION['hash'] != $_GET['hash'] || empty($_GET['hash'])) {
			exit('hash错误！');
		}
		$name = MODULE_NAME;
		$dao = M($name);
		$data = F('moldfile_excel_temp' . $_GET['hash']);
		if ($data) {
			$err = 0;
			$suc = 0;
			$msg = '';
			foreach ($data as $v) {
				$str = '';
				$sql = array();
				$sql_reg = array();
				if ($v['act'] == 'INSERT') {
					$sql = $v['data'];
					
					$sql_reg = $v['data_reg'];
					$this->_add('moldregister',$sql_reg);
					
					$return = $dao -> add($sql);
					//$msg .="\n\n{$dao->getLastSql()}\n";
					//$this->tags_status_all(3,$sql['id'],$sql['status']);//
					$str = '插入';
				}
				if ($v['act'] == 'UPDATE') {
					$sql = $v['data'];
					
					$sql_reg = $v['data_reg'];
					$this->_save('moldregister',$sql_reg);
					
					$return = $dao -> save($sql);
					//$msg .="\n\n{$dao->getLastSql()}\n";
					//$this->tags_status_all(3,$sql['id'],$sql['status']);//
					$str = '更新';
				}
				if ($return) {
					$suc++;
					$msg .= "\n\n{$v['data']['p_sn']}   ---{$str}成功！\n";
				} else {
					$err++;
					$msg .= "\n\n{$v['data']['p_sn']}   ---{$str}失败！\n";
				}
				$msg .= $dao -> getLastSql();
			}
			echo "共插入操作 :" . ($err + $suc) . "次       成功：$suc 次 ；  失败：$err 次  <br><br>";
			echo '<textarea cols="80" rows="50">' . htmlspecialchars($msg) . '</textarea>';
			F('moldfile_excel_temp' . $_SESSION['hash'], null);
			unset($_SESSION['hash']);
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
		$this -> assign('moldtype', $this -> select_all("moldtype"));
		$this -> assign('moldcategory', $this -> select_all("moldcategory"));
		$this -> assign('moldfactory', $this -> select_all("moldfactory"));
		$this -> assign('moldvesting', $this -> select_all("moldvesting"));
		$this -> display($name . '_edit');
	}

	function insert() {
		$moldfile = $this -> select_all("moldfile", array('m_sn' => $_POST['m_sn']), "id", false);
		$moldregister = $this -> select_all("moldregister", array('m_sn' => $_POST['m_sn']), "id", false);

		if (!empty($moldfile['id']) || !empty($moldregister['id'])) {
			$this -> error(L('模具编号已存在,不可重复使用'));
		}

		if ($_POST['setup'])
			$_POST['setup'] = array2string($_POST['setup']);
		if ($_POST['m_status'] != 3) {
			$_POST['ms_time'] = 0;
			$_POST['ms_remark'] = "";
		} else {
			$_POST['ms_time'] = strtotime($_POST['ms_time']);
		}
		$_POST['mc_time'] = strtotime($_POST['mc_time']);
		$name = MODULE_NAME;
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		if ($model -> add() !== false) {
			//dump($model->getLastSql());
			if (in_array($name, $this -> cache_model))
				savecache($name);
			$this -> assign('jumpUrl', U(MODULE_NAME . '/index'));
			$this -> success(L('add_ok'));
		} else {
			$this -> error(L('add_error') . ': ' . $model -> getDbError());
		}
	}

	/**
	 * 更新
	 *
	 */

	function edit() {
		$name = MODULE_NAME;
		$model = M($name);
		$pk = ucfirst($model -> getPk());
		$id = $_REQUEST[$model -> getPk()];
		if (empty($id))
			$this -> error(L('do_empty'));
		$do = 'getBy' . $pk;
		$vo = $model -> $do($id);
		if ($vo['setup'])
			$vo['setup'] = string2array($vo['setup']);
		$this -> assign('vo', $vo);
		$this -> assign('moldtype', $this -> select_all("moldtype"));
		$this -> assign('moldcategory', $this -> select_all("moldcategory"));
		$this -> assign('moldfactory', $this -> select_all("moldfactory"));
		$this -> assign('moldvesting', $this -> select_all("moldvesting"));
		$this -> assign('moldregister', $this -> select_all("moldregister", array('m_sn' => $vo['m_sn']), "id", false));

		$this -> display();
	}

	function update() {
		$moldfile = $this -> select_all("moldfile", array('m_sn' => $_POST['m_sn']), "id", false);
		$moldregister = $this -> select_all("moldregister", array('m_sn' => $_POST['m_sn']), "id", false);

		if (!empty($moldfile['id']) || !empty($moldregister['id'])) {
			if ($moldfile['id'] != $_POST['id']) {
				$this -> error(L('模具编号模具档中已存在,不可重复使用'));
			}
			if ($moldregister['id'] != $_POST['mrid']) {
				$this -> error(L('模具编号申请档案中已存在,不可重复使用'));
			}
		}

		if ($_POST['setup'])
			$_POST['setup'] = array2string($_POST['setup']);
		if ($_POST['m_status'] != 3) {
			$_POST['ms_time'] = 0;
			$_POST['ms_remark'] = "";
		} else {
			$_POST['ms_time'] = strtotime($_POST['ms_time']);
		}
		$_POST['mc_time'] = strtotime($_POST['mc_time']);
		$name = MODULE_NAME;
		$model = D($name);
		if (false === $model -> create()) {
			$this -> error($model -> getError());
		}
		if (false !== $model -> save()) {
			if (in_array($name, $this -> cache_model))
				savecache($name);
			$this -> assign('dialog', '1');
			$this -> assign('jumpUrl', U(MODULE_NAME . '/index'));
			$this -> success(L('edit_ok'));
		} else {
			$this -> success(L('edit_error') . ': ' . $model -> getDbError());
		}
	}

}
