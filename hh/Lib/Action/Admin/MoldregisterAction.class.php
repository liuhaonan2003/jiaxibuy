<?php 
/**
 * 
 * Moldregister(新模具登记管理类)
 *
 */
class MoldregisterAction extends AdminbaseAction
{  
    function _initialize()
    {
		parent::_initialize();
		//$status = array(1=>'模具申请',2=>'申请通过 ',3=>'厂商生产 ',4=>'模具返厂',5=>'模具入库',6=>'申请作废',7=>'模具作废');
		$status = array(1=>'模具申请',2=>'模具入库',3=>'模具作废');
		//$store = array(0=>'等待入库',1=>'完成入库 ');
		$this->assign ( 'arr_status', $status );
		//$this->assign ( 'arr_store', $store );
		$this->assign('moldfactory', $this->select_all("moldfactory"));
		$dt = array();
		for ($i=1; $i <= 12; $i++) { //date('t', strtotime("".date('y')."-".$i."-1"))
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
		$ctime =$_REQUEST['ctime'];
		$complete_time =$_REQUEST['complete_time'];
		$arr_ctime = split("=", $ctime);
		$arr_complete_time = split("=", $complete_time);
		
		$groupid =intval($_REQUEST['groupid']);
		$catid =intval($_REQUEST['catid']);
		$posid =intval($_REQUEST['posid']);
		$typeid =intval($_REQUEST['typeid']);

		if(!empty($keyword) && !empty($searchtype)){
			$map[$searchtype]=array('like','%'.$keyword.'%');
		}
		
		if($mfa_id)$map['mfa_id']=$mfa_id;
		if($arr_ctime[0] > 0 && $arr_ctime[1] > 0){
			$map['ctime'] = array( array('EGT', strtotime($arr_ctime[0])) ,array('ELT',strtotime($arr_ctime[1])),'AND');
		}
		if($arr_complete_time[0] > 0 && $arr_complete_time[1] > 0){
			$map['complete_time'] = array( array('EGT', strtotime($arr_complete_time[0])) ,array('ELT',strtotime($arr_complete_time[1])),'AND');
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
		$map['isstore']=0;
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
			
			foreach ($voList as $xkey => $xvalue) {
				foreach ($xvalue as $ykey => $yvalue) {
					if($ykey=='mfa_id'){
						$arr = $this->select_all("moldfactory",array('id'=>$yvalue),"title",false);
						$xvalue[$ykey] = $arr['title'];
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
		        $this->assign('moldfactory', $this->select_all("moldfactory"));
				$this->assign('moldvesting', $this->select_all("moldvesting"));
				$this->assign('moldfile', $this->select_all("moldfile",array('m_sn'=>$vo['m_sn']),"id",false));
				break;
		}
		$this->display ();
	}
	
	/**
	 * 申请模具入库
	 * */
	function addmoldfile($id=0){
		if($id == 0){
			if(empty($_POST['parent'])){
	  			exit(json_encode(array('status'=>0,'msg'=>'数据ID不可为空!','debug'=>__LINE__)));
	  		}
	  		
			$info = $this->select_all("moldregister",array('id'=>$_POST['parent']),"id,m_sn,m_title,m_number,department,mfa_id,isstore",false);
			
			if($info['isstore']==1){
				exit(json_encode(array('status'=>0,'msg'=>'当前信息模具库已存在,请不要重复入库!','debug'=>__LINE__)));
			}
			
			$_POST ['m_sn'] = $info['m_sn'];
	    	$_POST ['m_title'] = $info['m_title'];
			$_POST ['mu_total'] = $info['m_number'];
			$_POST ['mc_total'] = 0;
			$_POST ['mr_number'] = $info['m_number'];
			$_POST ['mv_id'] = $info['department'];
			$_POST ['mf_id'] = $info['mfa_id'];
			
			$result = $this->_add('moldfile',$_POST);
			
			if ($result !==false) {
				$this->_save('moldregister',array('id'=>$info['id'],'isstore'=>1));
				exit(json_encode(array('status'=>1,'msg'=>'成功导入数据!')));
	        } else {
				exit(json_encode(array('status'=>0,'msg'=>'导入数据有误!')));
	        }
		}else{
			if(empty($id)){
	  			return false;
	  		}
	  		
			$info = $this->select_all("moldregister",array('id'=>$id),"id,m_sn,m_title,m_number,department,mfa_id,isstore",false);
			
			if($info['isstore']==1){
				return false;
			}
			
			$_POST ['m_sn'] = $info['m_sn'];
	    	$_POST ['m_title'] = $info['m_title'];
			$_POST ['mu_total'] = $info['m_number'];
			$_POST ['mc_total'] = 0;
			$_POST ['mr_number'] = $info['m_number'];
			$_POST ['mv_id'] = $info['department'];
			$_POST ['mf_id'] = $info['mfa_id'];
			
			$result = $this->_add('moldfile',$_POST);
			
			if ($result !==false) {
				$this->_save('moldregister',array('id'=>$info['id'],'isstore'=>1));
				return true;
	        } else {
				return false;
	        }
		}
  		
		/*$name = 'moldfile';
        $model = M($name);
        if ($model->add() !==false) {
            if(in_array($name,$this->cache_model)) savecache($name);
			exit(json_encode(array('status'=>1,'msg'=>'成功导入数据!','debug'=>$model->getError())));
        } else {
			exit(json_encode(array('status'=>0,'msg'=>'导入数据有误!','debug'=>$model->getError())));
        }*/
		/*$data = array();	
		$data ['m_sn'] = $info['m_sn'];
    	$data ['m_title'] = $info['m_title'];
		$data ['mu_total'] = $info['m_number'];
		$data ['mv_id'] = $info['department'];
		$data ['mf_id'] = $info['mfa_id'];
		C('TOKEN_ON',FALSE);
        if (false === $model->create()) {
			exit(json_encode(array('status'=>0,'msg'=>'模型有误！','debug'=>$model->getError())));
        }
		$model->data($data)->add();*/
		/***************************************************************************************/
		/*C('TOKEN_ON',FALSE);
        if (false === $model->create()) {
			exit(json_encode(array('status'=>0,'msg'=>'模型有误！','debug'=>$model->getError())));
        }
		$model->m_sn = $info['m_sn'];
		$model->m_title = $info['m_title'];
		$model->mu_total = $info['m_number'];
		$model->mv_id = $info['department'];
		$model->mf_id = $info['mfa_id'];
		$model->add();*/
	}
	function issn(){
		if(empty($_POST['sn'])){
  			exit(json_encode(array('status'=>0,'msg'=>'模具编号不可为空','debug'=>__LINE__)));
  		}
		$result = true;
		$moldfile = $this->select_all("moldfile",array('m_sn'=>$_POST['sn']),"id",false);
		$moldregister = $this->select_all("moldregister",array('m_sn'=>$_POST['sn']),"id",false);
  		if(!empty($_POST['mfid'])){
			if(!empty($moldfile['id']) && $moldfile['id'] !=$_POST['mfid']){
				$result = false;
			}
  		}else{
  			if(!empty($moldfile['id'])){
  				$result = false;
  			}
  		}
		
		if(!empty($_POST['mrid'])){
			if(!empty($moldregister['id']) && $moldregister['id'] !=$_POST['mrid']){
				$result = false;
			}
  		}else{
  			if(!empty($moldregister['id'])){
  				$result = false;
  			}
  		}
		
		if ($result !==false) {
			exit(json_encode(array('status'=>1,'msg'=>'模具编号可用')));
        } else {
			exit(json_encode(array('status'=>0,'msg'=>'模具编号已存在')));
        }
	}
	
    /**
     * 添加
     *
     */

    function add() {
        $name = MODULE_NAME;
        $this->assign('moldfactory', $this->select_all("moldfactory"));
		$this->assign('moldvesting', $this->select_all("moldvesting"));
        $this->display ($name.'_edit');
    }
    function insert() {
    	$moldfile = $this->select_all("moldfile",array('m_sn'=>$_POST['m_sn']),"id",false);
		$moldregister = $this->select_all("moldregister",array('m_sn'=>$_POST['m_sn']),"id",false);
		
		if(!empty($moldfile['id']) || !empty($moldregister['id'])){
			$this->error (L('模具编号已存在,不可重复使用'));
		}
		$status = $_POST['status'];
    	$_POST ['ctime'] = strtotime($_POST['ctime']);
    	$_POST ['complete_time'] = strtotime($_POST['complete_time']);
        $name = MODULE_NAME;
        $model = D ($name);
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
		$ret = $model->add();
        if ($ret !==false) {
            //dump($model->getLastSql());
            if($status == 2){
            	$this->addmoldfile($ret);
            }
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
        $this->assign('moldfactory', $this->select_all("moldfactory"));
		$this->assign('moldvesting', $this->select_all("moldvesting"));
		$this->assign('moldfile', $this->select_all("moldfile",array('m_sn'=>$vo['m_sn']),"id",false));
			
        $this->display ();
    }
    function update() {
    	$moldfile = $this->select_all("moldfile",array('m_sn'=>$_POST['m_sn']),"id",false);
		$moldregister = $this->select_all("moldregister",array('m_sn'=>$_POST['m_sn']),"id",false);
		
		if(!empty($moldfile['id']) || !empty($moldregister['id'])){
			if($moldfile['id'] !=$_POST['mfid']){
				$this->error (L('模具编号模具档中已存在,不可重复使用'));
			}
			if($moldregister['id'] !=$_POST['id']){
				$this->error (L('模具编号申请档案中已存在,不可重复使用'));
			}
		}
		$id =  $_POST['id'];
		$status = $_POST['status'];
    	$_POST ['ctime'] = strtotime($_POST['ctime']);
    	$_POST ['complete_time'] = strtotime($_POST['complete_time']);
        $name = MODULE_NAME;
        $model = D ( $name );
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        if (false !== $model->save ()) {
        	if($status == 2){
            	$this->addmoldfile($id);
            }
            if(in_array($name,$this->cache_model)) savecache($name);
			$this->assign ( 'dialog', '1' );
            $this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
            $this->success (L('edit_ok'));
        } else {
            $this->success (L('edit_error').': '.$model->getDbError());
        }
    }	
}