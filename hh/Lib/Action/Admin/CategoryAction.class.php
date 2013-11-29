<?php 
/**
 * 
 * Category(分类)
 *
 */
class CategoryAction extends AdminbaseAction
{
    protected $dao, $categorys , $module;
    function _initialize()
    {		
        parent::_initialize();    
        foreach ((array)$this->module as $rw){
			if($rw['type']==1 && $rw['status']==1)  $data['module'][$rw['id']] = $rw;
        }
		$this->module=$data['module'];
        $this->assign($data);
		unset($data);
        $this->dao = D('Admin.category');		
    }

    /**
     * 列表
     *
     */
    public function index()
    {
        if($this->categorys){
			foreach($this->categorys as $r) {
				if($r['module']=='Page'){
					$r['str_manage'] = '<a href="?g=Admin&m=Page&a=edit&id='.$r['id'].'">'.L('edit_page').'</a> | ';
				}else{
					$r['str_manage'] = '';
				}
				$r['str_manage'] .= '<a href="'.U('Category/add',array( 'parentid' => $r['id'],'type'=>$r['type'])).'">'.L('add_catname').'</a> | <a href="'.U('Category/edit',array( 'id' => $r['id'],'type'=>$r['type'])).'">'.L('edit').'</a> | <a href="javascript:confirm_delete(\''.U('Category/delete',array( 'id' => $r['id'])).'\')">'.L('delete').'</a> ';
				$r['modulename']=$this->module[$r['moduleid']]['title'];
				$r['dis'] =  $r['ismenu'] ? '<font color="green">'.L('display_yes').'</font>' : '<font color="red">'.L('display_no').'</font>' ;				
				$array[] = $r;			
			}

			$str  = "<tr>
						<td align='center'><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input-text-c'></td>
						<td align='center'>\$id</td>
						<td >\$spacer\$catname &nbsp;</td>
						<td align='center'>\$modulename</td>
						<td align='center'>\$dis</td>
						<td align='center'><a href='\$url' target='_blank'>".L('fangwen')."</a></td>
						<td align='center'>\$str_manage</td>
					</tr>";
			import ( '@.ORG.Tree' );
			$tree = new Tree ($array);	
			$tree->icon = array('&nbsp;&nbsp;&nbsp;'.L('tree_1'),'&nbsp;&nbsp;&nbsp;'.L('tree_2'),'&nbsp;&nbsp;&nbsp;'.L('tree_3'));
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			
			$categorys = $tree->get_tree(0, $str);
			$this->assign('categorys', $categorys);
		}
        $this->display();
    }

	 public function _before_add()
    {

		$templates= template_file();
		$this->assign ( 'templates',$templates );

		$parentid =	intval($_GET['parentid']); 
		
		$vo['moduleid'] =$this->categorys[$parentid]['moduleid'];
		$this->assign('vo', $vo);
		foreach($this->categorys as $r) {
			$array[] = $r;
		}
		import ( '@.ORG.Tree' );	
		$str  = "<option value='\$id' \$selected>\$spacer \$catname</option>";
		$tree = new Tree ($array);		 
		$select_categorys = $tree->get_tree(0, $str,$parentid);
		$usergroup=F('role');
		$this->assign('rlist',$usergroup);
		$this->assign('select_categorys', $select_categorys);
	}

    /**
     * 提交录入
     *
     */
    public function insert()
    {
		
		/*
		if($_POST['parentid']){
			if($_POST['moduleid']!=$this->categorys[$_POST['parentid']]['moduleid']){
				$this->success(L('chose_notop_module'));
			}			
		}
		*/

		if(empty($_POST['type'])){
			$_POST['readgroup'] = implode(',',$_POST['readgroup']);	
			$_POST['module'] = $this->module[$_POST['moduleid']]['name'];
		}
 
        if($this->dao->create())
        {  
			$id = $this->dao->add();
            if($id)
            {
				if($_POST['module']=='Page'){
					$_POST['id']=$id;
					if(empty($_POST['title']))$_POST['title'] = $_POST['catname'];
					$Page=D('Page');
					if($Page->create()){
						$Page->add();
					}
				}

				if($_POST['aid']) {
					$Attachment =M('Attachment');		
					$aids =  implode(',',$_POST['aid']);
					$data['catid']= $_POST['catid'];
					$data['status']= '1';
					$Attachment->where("aid in (".$aids.")")->save($data);
				}

				$this->repair();
				savecache('Category');


				if($_POST['ishtml']){
					$this->categorys = F('Category');
					if($this->sysConfig['HOME_ISHTML']) $this->create_index();
					$cat = $this->categorys[$id];					
					$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
					foreach($arrparentid as $catid) {
						if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
					}
				}
				$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
                $this->success(L('add_ok'));
			}else{
			   $this->error(L('add_error'));
			}
        }else{
            $this->error($this->dao->getError());
        }
    }

    /**
     * 编辑
     *
     */
    public function edit()
    {
		$id = intval($_GET['id']);

		$templates= template_file();
		$this->assign ( 'templates',$templates );

        $record = $this->categorys[$id];
		$record['readgroup'] = explode(',',$record['readgroup']);
        if(empty($id) || empty($record)) $this->error(L('do_empty'));

       	$parentid =	intval($record['parentid']);
		import ( '@.ORG.Tree' );		
		$result = $this->categorys;
		foreach($result as $r) {
			if($r['type']==1) continue;
			$r['selected'] = $r['id'] == $parentid ? 'selected' : '';
			$array[] = $r;
		}
		$str  = "<option value='\$id' \$selected>\$spacer \$catname</option>";
		$tree = new Tree ($array);		 
		$select_categorys = $tree->get_tree(0, $str,$parentid);
		$this->assign('select_categorys', $select_categorys);
        $this->assign('vo', $record);
		$usergroup=F('role');
		$this->assign('rlist',$usergroup); 
		$this->display ();
		
    }

    /**
     * 提交编辑
     *
     */
    public function update()
    {

		$_POST['module'] = $this->module[$_POST['moduleid']]['name'];		
		$_POST['readgroup'] = implode(',',$_POST['readgroup']);
		$_POST['arrparentid'] = $this->get_arrparentid($_POST['id']);
		if(empty($_POST['listtype']))$_POST['listtype']=0;
		$_POST['url'] ?  $_POST['type']=1 : $_POST['type']=0;
 
		if (false === $this->dao->create ()) {
			$this->error ( $this->dao->getError () );
		}
		if (false !== $this->dao->save ()) {

			if($_POST['aid']) {
					$Attachment =M('Attachment');		
					$aids =  implode(',',$_POST['aid']);
					$data['catid']= $_POST['id'];
					$data['status']= '1';
					$Attachment->where("aid in (".$aids.")")->save($data);
				}

			if($_POST['chage_all']){
				$arrchildid = $this->get_arrchildid($_POST['id']);
				$data['ismenu'] = $_POST['ismenu'];
				$data['ishtml'] = $_POST['ishtml'];
				$data['pagesize'] = $_POST['pagesize'];
				$data['template_list'] = $_POST['template_list'];
				$data['template_show'] = $_POST['template_show'];
				$data['readgroup'] = $_POST['readgroup'] ? $_POST['readgroup'] : '';
				$this->dao->where( ' id in ('.$arrchildid.')')->data($data)->save();
			}
			$this->repair();
			$this->repair();
			savecache('Category');
			if($_POST['ishtml']){
				$cat=$this->categorys[$_POST['id']];
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}
			}
			$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
			$this->success (L('edit_ok'));
		} else {
			$this->success (L('edit_error').': '.$this->dao->getDbError());
		}
 
    }

	public function repair_cache() {
		$this->repair();
		$this->repair();
		savecache('Category');
		$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
		$this->success(L('do_success'));
	}

	public function repair() {
		
		@set_time_limit(500);
		$this->categorys = $categorys = array();
		$categorys = $this->dao->where("parentid=0")->Order('listorder ASC,id ASC')->findAll();
		$this->set_categorys($categorys);
		if(is_array($this->categorys)) {
			foreach($this->categorys as $id => $cat) {
				if($id == 0 || $cat['type']==1) continue;
				$this->categorys[$id]['arrparentid'] = $arrparentid = $this->get_arrparentid($id);
				$this->categorys[$id]['arrchildid'] = $arrchildid = $this->get_arrchildid($id);
				$this->categorys[$id]['parentdir'] = $parentdir = $this->get_parentdir($id);
				$child = is_numeric($arrchildid) ? 0 : 1;
				$url =  geturl($cat,'',$this->sysConfig);
				$url = $url[0];
				$this->dao->save(array('url'=>$url,'parentdir'=>$parentdir,'arrparentid'=>$arrparentid,'arrchildid'=>$arrchildid,'child'=>$child,'id'=>$id));
			}
		}
	}

	public function set_categorys($categorys = array()) {
		if (is_array($categorys) && !empty($categorys)) {
			foreach ($categorys as $id => $c) {
				$this->categorys[$c[id]] = $c;				
				$r = $this->dao->where("parentid = $c[id]")->Order('listorder ASC,id ASC')->findAll();
				$this->set_categorys($r);
			}
		}
		return true;
	}

	public function get_parentdir($id) {
		if($this->categorys[$id]['parentid']==0) return '';
		 
		$arrparentid = $this->categorys[$id]['arrparentid'];
		unset($r);
		if ($arrparentid) {
				$arrparentid = explode(',', $arrparentid);
				$arrcatdir = array();
				foreach($arrparentid as $pid) {
					if($pid==0) continue;
					$arrcatdir[] = $this->categorys[$pid]['catdir'];
				}
				return implode('/', $arrcatdir).'/';
		}
	}


	public function get_arrparentid($id, $arrparentid = '') {
		if(!is_array($this->categorys) || !isset($this->categorys[$id])) return false;
		$parentid = $this->categorys[$id]['parentid'];
		$arrparentid = $arrparentid ? $parentid.','.$arrparentid : $parentid;
		if($parentid) {
			$arrparentid = $this->get_arrparentid($parentid, $arrparentid);
		} else {
			$this->categorys[$id]['arrparentid'] = $arrparentid;
		}
		return $arrparentid;
	}

	public function get_arrchildid($id) {
		$arrchildid = $id;
		if(is_array($this->categorys)) {
			foreach($this->categorys as $catid => $cat) {
				if($cat['parentid'] && $id != $catid) {
					$arrparentids = explode(',', $cat['arrparentid']);
					if(in_array($id, $arrparentids)) $arrchildid .= ','.$catid;
				}
			}
		}
		return $arrchildid;
	}

	public function delete() {
		$catid = intval($_GET['id']);
		$module = $this->categorys[$catid]['module'];
		if($this->categorys[$catid]['type']==1){
			$this->dao->delete($catid);
		}else{
			$module  = M($module);
			$arrchildid = $this->categorys[$catid]['arrchildid'];
			$where =  "catid in(".$arrchildid.")";
			$count = $module->where($where)->count();
			if($count) $this->error(L('category_does_not_allow_delete'));
			$this->dao->delete($arrchildid);
			$arr=explode(',',$arrchildid);
			foreach((array)$arr as $r){
				if($this->categorys[$r]['module']=='Page'){
				$module=M('Page');
				$module->delete($r);
				}
			}
		}
		$this->repair();
		savecache('Category');
		$this->success(L('do_success'));
	}
}
