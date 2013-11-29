<?php 
/**
 * 
 * Moldservice(模具维修管理类)
 *
 */
class MoldserviceAction extends AdminbaseAction
{
	protected $arrStatus;
    function _initialize()
    {
		parent::_initialize();
		//判定结果  1正常使用 2返厂维修 3模具报废
		$this->arrStatus = array(1=>"正常使用 ",2=>"返厂维修 ",3=>"模具报废");
		$this->assign ( 'arr_status', $this->arrStatus );
		
		$this->assign('moldfactory', $this->select_all("moldfactory"));
		$dt = array();
		for ($i=1; $i <= 12; $i++) {
			$d = date('t', strtotime("".date('y')."-".$i."-1"))-1;
			$date_start = date('Y-m-d',strtotime("".date('y')."-".$i."-1"));
			$date_end = date("Y-m-d",strtotime("+".$d." day", strtotime($date_start)));
			$dt[$i]['title'] = $i.'月';
			$dt[$i]['date'] = $date_start.'='.$date_end;
		}
		$this->assign ( 'dt', $dt );
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
		
		$mfa_id =intval($_REQUEST['mfa_id']);
		$s_time =$_REQUEST['s_time'];
		$rc_time =$_REQUEST['rc_time']; 
		$ac_time =$_REQUEST['ac_time'];
		
		$arr_s_time = split("=", $s_time);
		$arr_rc_time = split("=", $rc_time);
		$arr_ac_time = split("=", $ac_time);
		
		$groupid =intval($_REQUEST['groupid']);
		$catid =intval($_REQUEST['catid']);
		$posid =intval($_REQUEST['posid']);
		$typeid =intval($_REQUEST['typeid']);

		if(!empty($keyword) && !empty($searchtype)){
			if($searchtype == 'mf_id'){
				$moldfile = $this->select_all("moldfile",array('m_sn'=>$keyword),"id",false);
				$map[$searchtype]=$moldfile['id'];
			}else{
				$map[$searchtype]=array('like','%'.$keyword.'%');
			}
		}
		
		if($mfa_id)$map['mfa_id']=$mfa_id;
		if($arr_s_time[0] > 0 && $arr_s_time[1] > 0){
			$map['s_time'] = array( array('EGT', strtotime($arr_s_time[0])) ,array('ELT',strtotime($arr_s_time[1])),'AND');
		}
		if($arr_rc_time[0] > 0 && $arr_rc_time[1] > 0){
			$map['rc_time'] = array( array('EGT', strtotime($arr_rc_time[0])) ,array('ELT',strtotime($arr_rc_time[1])),'AND');
		}
		if($arr_ac_time[0] > 0 && $arr_ac_time[1] > 0){
			$map['ac_time'] = array( array('EGT', strtotime($arr_ac_time[0])) ,array('ELT',strtotime($arr_ac_time[1])),'AND');
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
			
			foreach ($voList as $xkey => $xvalue) {
				foreach ($xvalue as $ykey => $yvalue) {
					if($ykey=='mf_id'){
						$arr = $this->select_all("moldfile",array('id'=>$yvalue),"m_title,mr_number,m_sn",false);
						$xvalue[$ykey] = $arr['m_sn'];
						//$xvalue['mr_number'] = $arr['mr_number'];
					}/*elseif($ykey=='department'){
						$arr = $this->select_all("moldvesting",array('id'=>$yvalue),"title",false);
						$xvalue[$ykey] = $arr['title'];
					}*/elseif($ykey=='mfa_id'){
						$arr = $this->select_all("moldfactory",array('id'=>$yvalue),"title",false);
						$xvalue[$ykey] = $arr['title'];
					}elseif($ykey=='m_status'){
						$xvalue[$ykey] = $this->arrStatus[$yvalue];
					}
				}
				$voList[$xkey] = $xvalue;
			}
			
			//模板赋值显示
			$this->assign ( 'list', $voList );
			$this->assign ( 'page', $page );
		}
		return;
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
		        $this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn"));
		        $this->assign('moldfactory', $this->select_all("moldfactory"));
				$this->assign('moldvesting', $this->select_all("moldvesting"));
				break;
		}
		$this->display ();
	}
    
    /**
     * 添加
     *
     */

    function add() {
        $name = MODULE_NAME;
		$this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn"));
        $this->assign('moldfactory', $this->select_all("moldfactory"));
		$this->assign('moldvesting', $this->select_all("moldvesting"));
        $this->display ($name.'_edit');
    }
    function insert() {
    	$mf_id = $_POST['mf_id'];
		$m_status = $_POST['m_status'];
    	$_POST ['s_time'] = strtotime($_POST['s_time']);
		$_POST ['ctime'] = strtotime($_POST['ctime']);
		$_POST ['rc_time'] = strtotime($_POST['rc_time']);
		$_POST ['ac_time'] = strtotime($_POST['ac_time']);
		$_POST ['t_time'] = strtotime($_POST['t_time']);
        $name = MODULE_NAME;
        $model = D ($name);
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if ($model->add() !==false) {
        	//mf_id
        	$this->_save('moldfile',array('id'=>$mf_id,'m_status'=>$m_status));
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
        $this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn"));
        $this->assign('moldfactory', $this->select_all("moldfactory"));
		$this->assign('moldvesting', $this->select_all("moldvesting"));
        $this->display ();
    }
    function update() {
    	$mf_id = $_POST['mf_id'];
		$m_status = $_POST['m_status'];
    	$_POST ['s_time'] = strtotime($_POST['s_time']);
		$_POST ['ctime'] = strtotime($_POST['ctime']);
		$_POST ['rc_time'] = strtotime($_POST['rc_time']);
		$_POST ['ac_time'] = strtotime($_POST['ac_time']);
		$_POST ['t_time'] = strtotime($_POST['t_time']);
        $name = MODULE_NAME;
        $model = D ( $name );
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if (false !== $model->save ()) {
        	$this->_save('moldfile',array('id'=>$mf_id,'m_status'=>$m_status));
            if(in_array($name,$this->cache_model)) savecache($name);
			$this->assign ( 'dialog', '1' );
            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
            $this->success (L('edit_ok'));
        } else {
            $this->success (L('edit_error').': '.$model->getDbError());
        }
    }	
}