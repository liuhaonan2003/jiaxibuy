<?php
if(!defined("YOURPHP")) exit("Access Denied");
class BaseAction extends Action
{
	protected   $Config ,$sysConfig,$categorys,$module,$moduleid,$mod,$dao;
    public function _initialize() {
		 
			$this->categorys = F('Category');
			$this->Config = F('Config');
			$this->sysConfig = F('sys.config');
			$this->module = F('Module');
			$this->assign($this->Config);
			$this->mod= F('Mod');
			$this->moduleid=$this->mod[MODULE_NAME];
			$this->assign('Module',$this->module);
			$this->assign('Categorys',$this->categorys);
			//dump($this->categorys);
			import("@.ORG.Form");
			import("@.TagLib.TagLibYP");
		
			if(empty($_COOKIE['groupid'])){
				cookie('groupid',4,3600*24);
			}
	}

    public function index($catid='',$module='')
    {

		if(empty($catid)) $catid =  intval($_REQUEST['id']);
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);
		$sub=list_to_tree($this->categorys,"id","parentid",'_son',$catid);
		$this->assign("sub",$sub);
		
		$cat = $this->categorys[$catid];		
		$bcid = explode(",",$cat['arrparentid']); 
		$bcid = $bcid[1];
		if($bcid == '') $bcid=intval($catid);
		if(empty($module))$module=$cat['module'];
		$left=list_to_tree($this->categorys,"id","parentid",'_son',0);
		foreach ($left as $k=>$v){
			if($v['id']==$bcid){
				$left=$v;
			}
		}
		$this->assign("left",$left);	
		unset($cat['id']);
		$this->assign($cat);
		$cat['id']=$catid;
		$this->assign('catid',$catid);
		$this->assign('bcid',$bcid);

		//Page
		if($module=='Page'){
			$module=M('Page');
			$data = $module->find($catid);
			
			$seo_title = $cat['title'] ? $cat['title'] : $data['title'];
			$this->assign ('seo_title',$seo_title);
			$this->assign ('seo_keywords',$data['keywords']);
			$this->assign ('seo_description',$data['description']);

			unset($data['id']);

			//分页
			$CONTENT_POS = strpos($data['content'], '[page]');
			if($CONTENT_POS !== false) {			
				$urlrule = geturl($cat);
				$urlrule[0] =  urldecode($urlrule[0]);
				$urlrule[1] =  urldecode($urlrule[1]);
				$contents = array_filter(explode('[page]',$data['content']));
				$pagenumber = count($contents);
				for($i=1; $i<=$pagenumber; $i++) {
					$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
				} 
				$pages = content_pages($pagenumber,$p, $pageurls);
				//判断[page]出现的位置
				if($CONTENT_POS<7) {
					$data['content'] = $contents[$p];
				} else {
					$data['content'] = $contents[$p-1];
				}
				$this->assign ('pages',$pages);	
			}

			$template = $cat['template_list'] ? $cat['template_list'] : MODULE_NAME ;
			$this->assign ($data);		
			$this->display($template);

		}else{
 
			$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
			$this->assign ('seo_title',$seo_title);
			$this->assign ('seo_keywords',$cat['keywords']);
			$this->assign ('seo_description',$cat['description']);
			

			$where = " status=1 ";
			if($cat['child']){							
				$where .= " and catid in(".$cat['arrchildid'].")";			
			}else{
				$where .=  " and catid=".$catid;			
			}
			if(empty($cat['listtype'])){
				$this->dao= M($module);
				$count = $this->dao->where($where)->count();
				if($count){
					import ( "@.ORG.Page" );
					$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');
					$page = new Page ( $count, $listRows );
					$page->urlrule = geturl($cat,'');
					$pages = $page->show();
					$field =  $this->module[$cat['moduleid']]['listfields'];
					$field =  $field ? $field : 'id,catid,userid,url,username,title,title_style,keywords,description,thumb,createtime,hits';
					$list = $this->dao->field($field)->where($where)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
					$this->assign('pages',$pages);
					$this->assign('list',$list);
				}
				$template_r = '_list';
			}else{
				$template_r = '_index';
			}
			$template = $cat['template_list'] ? $cat['template_list'] : $module.$template_r;
			$this->display($template);
		}
    }

 

	public function show($id='',$module='')
    {

		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);		
		$id = $id ? $id : intval($_REQUEST['id']);
		$module = $module ? $module : MODULE_NAME;
		$this->dao= M($module);;
		$data = $this->dao->find($id);
		$this->assign("info_id",$id);	
		$catid = $data['catid'];		
		$cat = $this->categorys[$data['catid']];		
		$bcid = explode(",",$cat['arrparentid']); 
		$bcid = $bcid[1]; 
		
		if($bcid == '') $bcid=intval($catid);
		$left=list_to_tree($this->categorys,"id","parentid",'_son',0);
		foreach ($left as $k=>$v){
			if($v['id']==$bcid){
				$left=$v;
			}
		}
		$this->assign("left",$left);
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
				if(!empty($data[$key])){
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
			}
			unset($setup);
		}
		$this->assign('fields',$fields); 
 
		//手动分页
		$CONTENT_POS = strpos($data['content'], '[page]');
		if($CONTENT_POS !== false) {
			
			$urlrule = geturl($cat,$id);
			$urlrule =  str_replace('%7B%24page%7D','{$page}',$urlrule); 
			$contents = array_filter(explode('[page]',$data['content']));
			$pagenumber = count($contents);
			for($i=1; $i<=$pagenumber; $i++) {
				$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
			} 
			$pages = content_pages($pagenumber,$p, $pageurls);
			//判断[page]出现的位置是否在文章开始
			if($CONTENT_POS<7) {
				$data['content'] = $contents[$p];
			} else {
				$data['content'] = $contents[$p-1];
			}
			$this->assign ('pages',$pages);	
		}

		if(!empty($data['template'])){
			$template = $data['template'];
		}elseif(!empty($cat['template_show'])){
			$template = $cat['template_show'];
		}else{
			$template = $module.'_show';
		}

		$this->assign('catid',$catid);
		$this->assign ($cat);
		$this->assign('bcid',$bcid);
 
		$this->assign ($data);
        $this->display($template);	 
    }

	public function down()
	{

		$module = $module ? $module : MODULE_NAME;
		$id = $id ? $id : intval($_REQUEST['id']);
		$this->dao= M($module);
		$filepath = $this->dao->getField('file',"id=".$id);
		$this->dao->setInc('downs',"id=".$id);

		if(strpos($filepath, ':/')) { 
			header("Location: $filepath");
		} else {			
			if(!$filename) $filename = basename($filepath);
			$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
			if(strpos($useragent, 'msie ') !== false) $filename = rawurlencode($filename);
			$filetype = strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
			$filesize = sprintf("%u", filesize($filepath));
			if(ob_get_length() !== false) @ob_end_clean();
			header('Pragma: public');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Encoding: none');
			header('Content-type: '.$filetype);
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Content-length: '.$filesize);
			readfile($filepath);
		}
		exit;
	}

	public function hits()
	{
		$module = $module ? $module : MODULE_NAME;
		$id = $id ? $id : intval($_REQUEST['id']);
		$this->dao= M($module);
		$this->dao->setInc('hits',"id=".$id);

		if($module=='Download'){
			$r = $this->dao->find($id);
			echo '$("#hits").html('.$r['hits'].');$("#downs").html('.$r['downs'].');';
		}else{
			$hits = $this->dao->getField('hits',"id=".$id);
			echo '$("#hits").html('.$hits.');';
		}
		exit;
	}


	public function insert()
    {
		$module = $module ? $module : MODULE_NAME;
		$model = M($module);
		$fields = F($this->moduleid.'_Field');

 
		foreach ( $_POST as $key => $val ) {
				$setup='';

				$setup=string2array($fields[$key]['setup']);

				if(!empty($fields[$key]['required']) && empty($_POST[$key])) $this->error (L('do_empty'));

				if($setup['multiple'] || $setup['inputtype']=='checkbox' || $fields[$key]['type']=='checkbox'){
					$_POST[$key] = implode(',',$_POST[$key]);		
				}elseif($fields[$key]['type']=='datetime'){
					$_POST[$key] =strtotime($_POST[$key]);
				}elseif($fields[$key]['type']=='textarea'){
					$_POST[$key]=addslashes($_POST[$key]);
				}elseif($fields[$key]['type']=='images' || $fields[$key]['type']=='files'){
					$name = $key.'_name';
					$arrdata =array();
					foreach($_POST[$key] as $k=>$res){
						 $arrdata[]=$_POST[$key][$k].'|'.$_POST[$name][$k];
					}
					$_POST[$key]=implode(':::',$arrdata);
				}elseif($fields[$key]['type']=='editor'){					
					//自动提取摘要
					if(isset($_POST['add_description']) && $_POST['description'] == '' && isset($_POST['content'])) {
						$content = stripslashes($_POST['content']);
						$description_length = intval($_POST['description_length']);
						$_POST['description'] = str_cut(str_replace(array("\r\n","\t",'[page]','[/page]','&ldquo;','&rdquo;'), '', strip_tags($content)),$description_length);
						$_POST['description'] = addslashes($_POST['description']);
					}
					//自动提取缩略图
					if(isset($_POST['auto_thumb']) && $_POST['thumb'] == '' && isset($_POST['content'])) {
						$content = $content ? $content : stripslashes($_POST['content']);
						$auto_thumb_no = intval($_POST['auto_thumb_no']) * 3;
						if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $content, $matches)) {
							$_POST['thumb'] = $matches[$auto_thumb_no][0];
						}
					}
				}else{
					
				}
		}
		if($fields['createtime']  && empty($_POST['createtime']) )$_POST['createtime'] = time();	
		if($fields['updatetime'])$_POST['updatetime'] = $_POST['createtime'];	
        if($fields['userid'] && empty($_POST['userid']))$_POST['userid'] = $_SESSION['userid'];
		if($fields['username'] && empty($_POST['username']))$_POST['username'] = $_SESSION['username'];
		if($_POST['style_color']) $_POST['style_color'] = 'color:'.$_POST['style_color'];
		if($_POST['style_bold']) $_POST['style_bold'] =  ';font-weight:'.$_POST['style_bold'];
		if($_POST['style_color'] || $_POST['style_bold'] ) $_POST['title_style'] = $_POST['style_color'].$_POST['style_bold'];
 
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		$id= $model->add();
		if ($id !==false) {
			if($_POST['aid']) {
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']=$id;
				$data['catid']= MODULE_NAME =='Page' ? $id : $_POST['catid'];
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}

			$data='';
			$cat = $this->categorys[$_POST['catid']];
			$url = geturl($cat,$id,$this->sysConfig);
			$data['id']= $id;
			$data['url']= $url[0];
			$model->save($data);
 
 
			$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
			$this->success (L('add_ok'));
		} else {
			$this->error (L('add_error').': '.$model->getDbError());
		}

    }
}
?>