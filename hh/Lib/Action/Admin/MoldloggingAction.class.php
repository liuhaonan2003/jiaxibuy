<?php 
/**
 * 
 * Moldlogging(收发记录管理类)
 *
 */
class MoldloggingAction extends AdminbaseAction
{
    function _initialize()
    {
		parent::_initialize();
		$this->assign('granttype', $this->select_all("granttype","","id,title"));
		$this->assign('grantaddress', $this->select_all("grantaddress","","id,address"));
		//dump(strtotime("-360 day",strtotime(date('Y-m-d'))));
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
		
		$gt_id =intval($_REQUEST['gt_id']);
		$ga_id =intval($_REQUEST['ga_id']);
		
		$return_time =$_REQUEST['return_time'];
		$arr = split("-", $return_time);
		//day month year
		$date_start = 0;
		$date_end = 0;
		//date("Y-m-d",strtotime("+".$arr[0]." day",strtotime(date('Y-m-d'))));
		if(!empty($arr[0]) && $arr[0]>0){
			$date_start=strtotime("-".$arr[0]." day",strtotime(date('Y-m-d')));
			if(!empty($arr[1]) && $arr[1]>0){
				$date_end=strtotime("-".$arr[1]." day",strtotime(date('Y-m-d')));
			}
		}
		//dump(date("Y-m-d",$date_start).'-'.$date_start.'/'.date("Y-m-d",$date_end).'-'.$date_end);
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
		
		if($date_start > 0 && $date_end > 0){
			$map['ctime'] = array( array('ELT', $date_start) ,array('EGT',$date_end),'AND');
			$map['rtime'] = array('EQ', 0);
		}
		
		if($date_start > 0 && $date_end == 0){
			$map['ctime'] = array('ELT', $date_start);
			$map['rtime'] = array('EQ', 0);
		}
		
		if($gt_id)$map['gt_id']=$gt_id;
		if($ga_id)$map['ga_id']=$ga_id;
		
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
					if($ykey=='mf_id'){
						$arr = $this->select_all("moldfile",array('id'=>$yvalue),"m_title,m_od,m_sn",false);
						$xvalue[$ykey] = $arr['m_sn'];
						$xvalue['m_od'] = $arr['m_od'];
					}elseif($ykey=='gt_id'){
						$arr = $this->select_all("granttype",array('id'=>$yvalue),"title",false);
						$xvalue[$ykey] = $arr['title'];
					}elseif($ykey=='ga_id'){
						$arr = $this->select_all("grantaddress",array('id'=>$yvalue),"address",false);
						$xvalue[$ykey] = $arr['address'];
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
		        $this->assign('granttype', $this->select_all("granttype","","id,title"));
		        $this->assign('grantaddress', $this->select_all("grantaddress","","id,address"));
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
        $this->assign('granttype', $this->select_all("granttype","","id,title"));
        $this->assign('grantaddress', $this->select_all("grantaddress","","id,address"));
        $this->display ($name.'_edit');
    }
    function insert() {
    	$n_return = $_POST['n_return'];
		$ga_id = $_POST['ga_id'];
		$mf_id = $_POST['mf_id'];
    	if(!empty($_POST['n_return'])){
        	$_POST['rtime'] = strtotime($_POST['rtime']);
        }else{
        	$_POST['rtime'] = 0;
        }
		$_POST ['ctime'] = strtotime($_POST['ctime']);		
		$_POST['orders_info'] = serialize($_POST['orders_info']);
        $name = MODULE_NAME;
        $model = D ($name);
        if (false === $model->create ()) {
            $this->error ( $model->getError () );
        }
        $log_id = $model->add();
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
		$vo['orders_info_show'] = unserialize($vo['orders_info']);
		$vo['orders_info'] = $vo['orders_info_show'];
        $this->assign ( 'vo', $vo );
        $this->assign('moldfile', $this->select_all("moldfile","","id,m_title,m_sn"));
        $this->assign('granttype', $this->select_all("granttype","","id,title"));
        $this->assign('grantaddress', $this->select_all("grantaddress","","id,address"));
        $this->display ();
    }
    function update() {
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
		$_POST['orders_info'] = serialize($_POST['orders_info']);
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
}