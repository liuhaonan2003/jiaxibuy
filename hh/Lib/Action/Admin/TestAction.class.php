<?php 
/**
 * 
 * Category(分类)
 *
 */
class TestAction extends AdminbaseAction
{
    protected $dao, $categorys , $module;
    function _initialize()
    {		
        parent::_initialize();    
        /*foreach ((array)$this->module as $rw){
			if($rw['type']==1 && $rw['status']==1)  $data['module'][$rw['id']] = $rw;
        }
		$this->module=$data['module'];
        $this->assign($data);
		unset($data);
        $this->dao = D('Admin.category');*/
    }

    /**
     * 列表
     *
     */
    public function index()
    {
		dump('test');
        /*if($this->categorys){
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
		}*/
        $this->display();
    }

	
}
