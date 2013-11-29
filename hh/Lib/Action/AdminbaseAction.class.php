<?php
if(!defined("YOURPHP")) exit("Access Denied");
class AdminbaseAction extends Action
{
	protected   $mod,$siteConfig,$sysConfig , $nav , $menudata , $cache_model,$categorys,$module,$moduleid,$Type;
	function _initialize()
	{
		$this->siteConfig = F('Config');
		$this->sysConfig = F('sys.config');
		$this->menudata = F('Menu');
		$this->categorys = F('Category');
		$this->module = F('Module');
		$this->mod = F('Mod');
		$this->assign('module_name',MODULE_NAME);
		$this->assign('action_name',ACTION_NAME);
		$this->cache_model=array('Menu','Config','Module','Role','Category','Posid','Field','Type');

		C('ADMIN_ACCESS',$this->sysConfig['ADMIN_ACCESS']);
		// 用户权限检查
		if (C ( 'USER_AUTH_ON' ) && !in_array(MODULE_NAME,explode(',',C('NOT_AUTH_MODULE')))) {
			import ( '@.ORG.RBAC' );
			if (! RBAC::AccessDecision ('Admin')) {
				//检查认证识别号

				if (! $_SESSION [C ( 'USER_AUTH_KEY' )]) {
					//跳转到认证网关
					redirect ( PHP_FILE . C ( 'USER_AUTH_GATEWAY' ) );
				}
				// 没有权限 抛出错误
				if (C ( 'RBAC_ERROR_PAGE' )) {
					// 定义权限错误页面
					redirect ( C ( 'RBAC_ERROR_PAGE' ) );
				} else {
					if (C ( 'GUEST_AUTH_ON' )) {
						$this->assign ( 'jumpUrl', PHP_FILE . C ( 'USER_AUTH_GATEWAY' ) );
					}
					// 提示错误信息
					$this->error ( L ( '_VALID_ACCESS_' ) );
				}
			}
		}

	 	$menuid = intval($_GET['menuid']);
		if(empty($menuid)) $menuid = cookie('menuid');
		if(!empty($menuid)){
			$this->nav = $this->getnav($menuid,1);
			if($this->nav)$this->assign('nav', $this->nav);
		}

		if($this->mod[MODULE_NAME]){
			$this->moduleid = $this->mod[MODULE_NAME];
			$this->m = $this->module[$this->moduleid];
			$this->assign('moduleid',$this->moduleid);
			$this->Type = F('Type');
			$this->assign('Type',$this->Type);

			if($this->module[$this->moduleid]['type']==1 && ACTION_NAME=='index'){

				if($this->categorys){
					foreach ($this->categorys as $r){
						if($r['type']==1) continue;
						if($r['moduleid'] != $this->moduleid || $r['child']){
							$arr= explode(",",$r['arrchildid']);
							$show=0;
							foreach((array)$arr as $rr){
								if($this->categorys[$rr]['moduleid'] ==$this->moduleid) $show=1;
							}
							if(empty($show))continue;
							if($r['child']){
								$r['disabled'] = ' disabled';
							}else{
								$r['disabled'] = ' ';
							}
						}else{
							$r['disabled'] = '';
						}
						$array[] = $r;
					}
					import ( '@.ORG.Tree' );
					$str  = "<option value='\$id' \$disabled \$selected>\$spacer \$catname</option>";
					$tree = new Tree ($array);
					$select_categorys = $tree->get_tree(0, $str);
					$this->assign('select_categorys', $select_categorys);
					$this->assign('categorys', $this->categorys);
				}
				$this->assign('posids', F('Posid'));
			}
		}
		import("@.ORG.Form");
		import("@.TagLib.TagLibYP");
	}

	public function getnav($menuid,$isnav=0){

			if($menuid){
				$bnav = $this->menudata[$menuid];
				if(empty($bnav['action']))$bnav['action'] ='index';
				$array = array('menuid'=> $bnav['id']);
				parse_str($bnav['data'],$c);
				$bnav['data'] = $c + $array;
			}

			if($this->menudata){
				$accessList = $_SESSION['_ACCESS_LIST'];
				foreach($this->menudata as $key=>$module) {
					if($module['parentid'] != $menuid || $module['status']==0) continue;
					if(isset($accessList[strtoupper('Admin')][strtoupper($module['model'])]) || $_SESSION[C('ADMIN_AUTH_KEY')]) {
						//设置模块访问权限$module['access'] =   1;
						if(empty($module['action'])) $module['action']='index';
						//检测动作权限
						if(isset($accessList[strtoupper('Admin')][strtoupper($module['model'])][strtoupper($module['action'])]) || $_SESSION[C('ADMIN_AUTH_KEY')]){
							$nav[$key]  = $module;
							if($isnav){
								$array=array('menuid'=> $nav[$key]['parentid']);
								cookie('menuid',$nav[$key]['parentid']);
								//$_SESSION['menuid'] = $nav[$key]['parentid'];
							}else{
								 $array=array('menuid'=> $nav[$key]['id']);
							}
							if(empty($menuid) && empty($isnav)) $array=array();
							$c=array();
							parse_str($nav[$key]['data'],$c);
							$nav[$key]['data'] = $c + $array;
						}
					}
				}
			}
			$navdata['bnav']=$bnav;
			$navdata['nav']=$nav;
			return $navdata;
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
		$this->display ($name.'_edit');
	}


	function insert() {

		if($_POST['setup']) $_POST['setup']=array2string($_POST['setup']);
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
		$this->display ();
	}
	function update() {
		if($_POST['setup']) $_POST['setup']=array2string($_POST['setup']);
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

	/**
     * 删除
     *
     */
	function delete(){
		$name = MODULE_NAME;
		$model = M ( $name );
		$pk = $model->getPk ();
		$id = $_REQUEST [$pk];
		if (isset ( $id )) {
			if(false!==$model->delete($id)){
				if(in_array($name,$this->cache_model)) savecache($name);
				$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error').': '.$model->getDbError());
			}
		}else{
			$this->error (L('do_empty'));
		}
	}

	/**
     * 批量删除
     *
     */

	function deleteall(){

		$name = MODULE_NAME;
		$model = M ( $name );
		$ids=$_POST['ids'];
		if(!empty($ids) && is_array($ids)){
			$id=implode(',',$ids);
			if(false!==$model->delete($id)){
				if(in_array($name,$this->cache_model)) savecache($name);
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error').': '.$model->getDbError());
			}
		}else{
			$this->error(L('do_empty'));
		}
	}

	/**
     * 批量操作
     *
     */
	public function listorder()
	{
		$name = MODULE_NAME;
		$model = M ( $name );
		$pk = $model->getPk ();
		$ids = $_POST['listorders'];
		foreach($ids as $key=>$r) {
			$data['listorder']=$r;
			$model->where($pk .'='.$key)->save($data);
		}
		if(in_array($name,$this->cache_model)) savecache($name);
		$this->success (L('do_ok'));

	}

	/*状态*/

	public function status(){
		$name = MODULE_NAME;
		$model = D ($name);
		if($model->save($_GET)){
			savecache(MODULE_NAME);
			$this->success(L('do_ok'));
		}else{
			$this->error(L('do_error'));
		}
	}

	/**
     * 默认操作
     *
     */
	public function index() {
        $name = MODULE_NAME;
		$model = M ($name);
        $list = $model->where($_REQUEST['where'])->select();
        $this->assign('list', $list);
        $this->display();
    }


	public function create_show($id,$module)
    {
		C('HTML_FILE_SUFFIX',$this->sysConfig['HTML_FILE_SUFFIX']);
		C('TMPL_FILE_NAME',str_replace('Default/Admin',$this->sysConfig['DEFAULT_THEME'].'/Home',C('TMPL_FILE_NAME')));
		$p =1;
		$id=intval($id);
		if(empty($id)) $this->success (L('do_empty'));;
		$this->assign($this->siteConfig);
		$this->assign('Categorys',$this->categorys);
		$dao= M($module);
		$data = $dao->find($id);

		$catid = $data['catid'];
		$this->assign('catid',$catid);
		$cat = $this->categorys[$data['catid']];
		$this->assign ($cat);
		$bcid = explode(",",$cat['arrparentid']);
		$bcid = $bcid[1];
		if($bcid == '') $bcid=intval($catid);
		$this->assign('bcid',$bcid);

		$seo_title = $data['title'].'-'.$cat['catname'];
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$data['keywords']);
		$this->assign ('seo_description',$data['description']);

		$fields = F($this->mod[$module].'_Field');
		foreach($data as $key=>$c_d){
			$setup='';
			$fields[$key]['setup'] =$setup=string2array($fields[$key]['setup']);
			if($setup['fieldtype']=='varchar' && $fields[$key]['type']!='text'){
				$data[$key] = implode(',',$data[$key]);
			}elseif($fields[$key]['type']=='images' || $fields[$key]['type']=='files'){
				$p_data=explode(':::',$data[$key]);
				$data[$key]=array();
				foreach($p_data as $k=>$res){
					$p_data_arr=explode('|',$res);
					$data[$key][$k]['filepath'] = $p_data_arr[0];
					$data[$key][$k]['filename'] = $p_data_arr[1];
				}
				unset($p_data);
				unset($p_data_arr);
			}
			unset($setup);
		}
		$this->assign('fields',$fields);


		//$dir = './'.dirname($data['url']).'/';
		$dir = preg_replace('/\\'.__ROOT__.'/','.', dirname($data['url']).'/', 1);
		$filename = basename($data['url'],C('HTML_FILE_SUFFIX'));


		if(!empty($data['template'])){
			$template = $data['template'];
		}elseif(!empty($cat['template_show'])){
			$template = $cat['template_show'];
		}else{
			$template = $cat['module'].'_show';
		}
		//手动分页
		$CONTENT_POS = strpos($data['content'], '[page]');
		if($CONTENT_POS !== false){
				$urlrule = geturl($cat,$data['id']);
				$contents = array_filter(explode('[page]',$data['content']));
				$pagenumber = count($contents);
				for($i=1; $i<=$pagenumber; $i++) {
					$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
				}
				//生成分页
				foreach ($pageurls as $p=>$urls) {
					$pages = content_pages($pagenumber,$p, $pageurls);
					$this->assign ('pages',$pages);
					$data['content'] = $contents[$p-1];
					$this->assign ($data);
					if($p > 1)$filename = basename($pageurls[$p]['1'],C('HTML_FILE_SUFFIX'));
					//$this->buildHtml($filename,$dir,'Home/'.$template);
					$this->buildHtml($filename,$dir,'hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/'.$template);
				}
		}else{
				$this->assign ($data);
				//$this->buildHtml($filename,$dir,'Home/'.$template);
				$this->buildHtml($filename,$dir,'hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/'.$template);
		}
    }

	public function create_list($catid,$p=1)
    {
		C('HTML_FILE_SUFFIX',$this->sysConfig['HTML_FILE_SUFFIX']);
		C('TMPL_FILE_NAME',str_replace('Default/Admin',$this->sysConfig['DEFAULT_THEME'].'/Home',C('TMPL_FILE_NAME')));

		$this->assign($this->siteConfig);
		$this->assign('Categorys',$this->categorys);
		$catid =intval($catid);
		if(empty($catid)) $this->success (L('do_empty'));

		$cat = $this->categorys[$catid];
		$this->assign('catid',$catid);
		if($cat['type']) return;
		if(empty($cat['ishtml'])) return;
		unset($cat['id']);
		$this->assign($cat);
		$cat['id']=$catid;
		$bcid = explode(",",$cat['arrparentid']);
		$bcid = $bcid[1];
		if($bcid == '') $bcid=intval($catid);
		$this->assign('bcid',$bcid);



		if($cat['moduleid']==1){
			$cat['listtype']=2;
			$module = $cat['module'];
			$dao= M($module);
			$data = $dao->find($catid);
			$seo_title = $cat['title'] ? $cat['title'] : $data['title'];
			$this->assign ('seo_title',$seo_title);
			$this->assign ('seo_keywords',$data['keywords']);
			$this->assign ('seo_description',$data['description']);

			unset($data['id']);
			$dir = preg_replace('/\\'.__ROOT__.'/','./', $cat['url'], 1);
			$filename = 'index';
			$template = $cat['template_list']? $cat['template_list'] : $cat['module'];
			//手动分页
			$CONTENT_POS = strpos($data['content'], '[page]');
			if($CONTENT_POS !== false){
					$urlrule = geturl($cat);
					$contents = array_filter(explode('[page]',$data['content']));
					$pagenumber = count($contents);
					for($i=1; $i<=$pagenumber; $i++) {
						$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
					}
					//生成分页
					foreach ($pageurls as $p=>$urls) {
						$pages = content_pages($pagenumber,$p, $pageurls);
						$this->assign ('pages',$pages);
						$data['content'] = $contents[$p-1];
						$this->assign ($data);
						if($p > 1)$filename = basename($pageurls[$p]['1'],C('HTML_FILE_SUFFIX'));
						//$this->buildHtml($filename,$dir,'Home/'.$template);
						$r=$this->buildHtml($filename,$dir,'hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/'.$template);
					}
			}else{
					$this->assign ($data);
					//$r=$this->buildHtml($filename,$dir,'Home/'.$template);
					$r=$this->buildHtml($filename,$dir,'hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/'.$template);
			}
			return true;
		}

		$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$cat['keywords']);
		$this->assign ('seo_description',$cat['description']);


		if($cat['listtype']==1){
			$template_r = '_index';
		}else{
			$where = " status=1 ";
			if($cat['child']){
				$where .= " and catid in(".$cat['arrchildid'].")";
			}else{
				$where .=  " and catid=".$catid;
			}

			$module = $cat['module'];
			$dao= M($module);
			$count = $dao->where($where)->count();
			if($count){
				import ( "@.ORG.Page" );
				$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');
				$page = new Page ( $count, $listRows ,$p );
				$page->urlrule = geturl($cat);
				$pages = $page->show();
				if($cat['field']) $field='id,catid,userid,url,username,title,title_style,keywords,description,thumb,createtime,hits';
				$list = $dao->field($field)->where($where)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
				$this->assign('pages',$pages);
				$this->assign('list',$list);
			}
			$template_r = '_list';
		}


		$dir = preg_replace('/\\'.__ROOT__.'/','.', $cat['url'], 1);
		$template = $cat['template_list']? $cat['template_list'] : $cat['module'].$template_r;
		$filename = ($p > 1 ) ? $p :  'index';


		//$r=$this->buildHtml($filename,$dir,'Home/'.$template);
		$r=$this->buildHtml($filename,$dir,'hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/'.$template);
		if($r) return true;
	}

	public function create_index()
    {
		C('HTML_FILE_SUFFIX',$this->sysConfig['HTML_FILE_SUFFIX']);
		C('TMPL_FILE_NAME',str_replace('Default/Admin',$this->sysConfig['DEFAULT_THEME'].'/Home',C('TMPL_FILE_NAME')));
		//cookie('think_template',$this->sysConfig['DEFAULT_THEME']);
		if(!$this->sysConfig['HOME_ISHTML']) $this->error(L('NO_HOME_ISHTML'));
		$this->assign('bcid',0);
		$this->assign('Module',$this->module);
		$this->assign($this->siteConfig);
		$this->assign('Categorys',$this->categorys);
 		//$r=$this->buildHtml('index','./','Home/Index_index');
		$r=$this->buildHtml('index','./','hh/Tpl/'.$this->sysConfig['DEFAULT_THEME'].'/Home/Index_index');
		if($r) return true;
    }

	function clisthtml($id){
			$pagesize= 10;
			$p = max(intval($p), 1);
			$j = 1;
			do {
				$this->create_list($id,$p);
				$j++;
				$p++;
				$pages = isset($pages) ? $pages : PAGESTOTAL;

			} while ($j <= $pages && $j < $pagesize);
	}
    /**
     * 自定义查询条件查询数据
     * @param array $field_value 自定义查询条件
     * @param $field 自定义数据字段
     * @param $isall 是否全部查询  是：true 否：false
     * @return 如果存在返回所有信息，不存在返回false
     */   
    public function select_all($dao,$field_value = array(),$field = "*",$isall = true){
        $model = M($dao);
        $where = "";
        foreach ($field_value as $key => $value) {
            if(empty($where)){
                $where = $key."='".$value."'";
            }else{
                $where .= " AND ".$key."='".$value."'";
            }
        }
        $rel = array();
        if($isall == true){
            $rel = $model->field($field)->where($where)->select();
        }else{
            $rel = $model->field($field)->where($where)->find();
			//dump($model->getLastSql());
        }
        if($rel !== false || !empty($rel)){
            return $rel;
        }else{
            return false;
        }
    }
	/**
     * 自定义查询条件查询模具数据
     * @param array $field_value 自定义查询条件
     * @param $field 自定义数据字段
     * @param $isall 是否全部查询  是：true 否：false
     * @return 如果存在返回所有信息，不存在返回false
     */   
    public function select_moldfile($dao,$field_value = array(),$field = "*",$isall = true){
        $model = M($dao);
        $where = "";
        foreach ($field_value as $key => $value) {
            if(empty($where)){//select * from test where name like '%111%'
                $where = $key." like '".$value."%'";
            }else{
                $where .= " AND ".$key." like '".$value."%'";
            }
        }
        $rel = array();
        if($isall == true){
            $rel = $model->field($field)->where($where)->select();
        }else{
            $rel = $model->field($field)->where($where)->find();
        }
        if($rel !== false || !empty($rel)){
            return $rel;
        }else{
            return false;
        }
    }
	/**
	 * 操作插入数据表
	 * */
	public function _add($dao,$post=array()){
		$model = D($dao);
		$_POST = $post;
		C('TOKEN_ON',FALSE);
		if (false === $model->create ()) {
			return false;
		}
		$result = $model->add ();
		if ($result !== false) {
			if(in_array($dao,$this->cache_model)) savecache($dao);
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * 操作更新数据表
	 * */
	public function _save($dao,$post=array()){
		$model = D($dao);
		C('TOKEN_ON',FALSE);
		if (false === $model->create ($post)) {
			return false;
		}
		$result = $model->save ();
		if (false !== $result) {
			if(in_array($dao,$this->cache_model)) savecache($dao);
			return $result;
		} else {
			return false;
		}
	}
	/**
	 * 操作删除数据表
	 * */
	public function _del($dao,$id){
		$model = D($dao);
		C('TOKEN_ON',FALSE);
		$result = $model->delete ($id);		
		if (false !== $result) {
			if(in_array($dao,$this->cache_model)) savecache($dao);
			return $result;
		} else {
			return false;
		}
	}
	
	/**
	 * 操作sql删除数据表
	 * */
	public function _del_data($sql){
		$Model = new Model();
		$result = $Model->query($sql);
		if (false !== $result) {
			return $result;
		} else {
			return false;
		}
	}
	/**
     * 处理文件上传
     * @param $config array 上传参数一些设置
     * @return string 图片文件的路径
     */
    function _upload($config=array()) {
    	// 引入上传类，并设置参数值
    	import("@.ORG.UploadFile"); 
        $upload = new UploadFile(); 
		//$upload->supportMulti = false;
        //设置上传文件大小 
        $upload->maxSize = $this->siteConfig['attach_maxsize']; 
		$upload->autoSub = true; 
		$upload->subType = 'date';
		$upload->dateFormat = 'Ym';
        //设置上传文件类型 
        $upload->allowExts = explode(',', $this->siteConfig['attach_allowext']); 
        //设置附件上传目录 
        $upload->savePath = UPLOAD_PATH;
		 //设置上传文件规则 
        $upload->saveRule = uniqid; 

       
        //删除原图 
        $upload->thumbRemoveOrigin = true; 
        if (!$upload->upload()) { 
			$this->error($upload->getErrorMsg ());
        } else { 
            //取得成功上传的文件信息 
            $uploadList = $upload->getUploadFileInfo(); 
			$imgpath= $upload->savePath . $uploadList [0] ['savename'];
        }
    	
    	return $imgpath;
    }
}
?>