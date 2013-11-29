<?php 
/**
 * 
 * Process(模具使用过程类)
 *
 */
class ProcessAction extends AdminbaseAction
{    
    function _initialize()
    {
		parent::_initialize();		
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
			$map[$searchtype]=array('like','%'.$keyword.'%');
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
					if($ykey=='mc_id'){
						$arr = $this->select_all("moldcategory",array('id'=>$yvalue),"title",false);
						$xvalue[$ykey] = $arr['title'];
					}elseif($ykey=='pd_id'){
						$arr = $this->select_all("processdetail",array('id'=>$yvalue),"title",false);
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
    
    /**
     * 添加
     *
     */

    function add() {
        $name = MODULE_NAME;        
        $this->assign('moldcategory', $this->select_all("moldcategory"));
        $this->assign('processdetail', $this->select_all("processdetail"));
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
        $this->assign('moldcategory', $this->select_all("moldcategory"));
        $this->assign('processdetail', $this->select_all("processdetail"));
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