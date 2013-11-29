<?php
/**
 *
 * Article(新闻模块)
 *
 */

class ContentAction extends AdminbaseAction
{
    protected  $dao,$fields;
    public function _initialize()
    {
        parent::_initialize();
		$this->dao = D('Admin.'.MODULE_NAME);

		$fields = F($this->moduleid.'_Field');
		foreach($fields as $key => $res){
			$res['setup']=string2array($res['setup']);
			$this->fields[$key]=$res;
		}
		unset($fields);
		unset($res);
		$this->assign ('fields',$this->fields);
    }

    /**
	 * 列表
	 *
	 */
    public function index()
    {
		$template =  file_exists(TEMPLATE_PATH.'/'.GROUP_NAME.'/'.MODULE_NAME.'_index.html') ? MODULE_NAME.'_index' : 'Content_index';
	    $this->_list(MODULE_NAME);
        $this->display ($template);
    }

	public function add()
    {
		$form=new Form();
		$this->assign ( 'form', $form );
		$template =  file_exists(TEMPLATE_PATH.'/'.GROUP_NAME.'/'.MODULE_NAME.'_edit.html') ? MODULE_NAME.'_edit' : 'Content_edit';
		$this->display ( $template);
	}


	public function edit()
    {
		
		$id = $_REQUEST ['id'];

		
		if(MODULE_NAME=='Page'){
					$Page=D('Page');
					$p = $Page->find($id);
					if(empty($p)){
					$data['id']=$id;
					$data['title'] = $this->categorys[$id]['catname'];
					$data['keywords'] = $this->categorys[$id]['keywords'];
					
					$Page->add($data);	
					}
		}
		$vo = $this->dao->getById ( $id );
 		$form=new Form($vo);
		$this->assign ( 'vo', $vo );
		$this->assign ( 'form', $form );
		$template =  file_exists(TEMPLATE_PATH.'/'.GROUP_NAME.'/'.MODULE_NAME.'_edit.html') ? MODULE_NAME.'_edit' : 'Content_edit';
		$this->display ( $template);
	}

    /**
     * 录入
     *
     */
    public function insert()
    {
		$model = $this->dao;
		$fields = $this->fields;
		$_POST = $this->checkfield($fields,$_POST);
		
		if($fields['createtime']  && empty($_POST['createtime']) )$_POST['createtime'] = time();		 
		if($fields['updatetime'])$_POST['updatetime'] = $_POST['createtime'];	
        $_POST['userid'] = $_SESSION['userid'];
		$_POST['username'] = $_SESSION['username'];
		if($_POST['style_color']) $_POST['style_color'] = 'color:'.$_POST['style_color'];
		if($_POST['style_bold']) $_POST['style_bold'] =  ';font-weight:'.$_POST['style_bold'];
		if($_POST['style_color'] || $_POST['style_bold'] ) $_POST['title_style'] = $_POST['style_color'].$_POST['style_bold'];
 
		
		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		$id= $model->add();
		if ($id !==false) {
			$catid = MODULE_NAME =='Page' ? $id : $_POST['catid'];

			if($_POST['aid']) {
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']=$id;
				$data['catid']= $catid;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}

			$data='';
			$cat = $this->categorys[$catid];
			$url = geturl($cat,$id,$this->sysConfig);
			$data['id']= $id;
			$data['url']= $url[0];
			$this->dao->save($data);
 
			if($cat['ishtml'] && $_POST['status']){
				if(MODULE_NAME!='Page'   && $_POST['status'])	$this->create_show($id,MODULE_NAME);
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}				
 			}
			$this->assign ( 'jumpUrl', U(MODULE_NAME.'/index') );
			$this->success (L('add_ok'));
		} else {
			$this->error (L('add_error').': '.$model->getDbError());
		}

    }

	function update()
	{  
		$model = $this->dao;
		$fields = $this->fields;
		$_POST = $this->checkfield($fields,$_POST);
 
		if($fields['updatetime']) $_POST['updatetime'] = time();		
		if($_POST['style_color']) $_POST['style_color'] = 'color:'.$_POST['style_color'];
		if($_POST['style_bold']) $_POST['style_bold'] =  ';font-weight:'.$_POST['style_bold'];
		if($_POST['style_color'] || $_POST['style_bold'] ) $_POST['title_style'] = $_POST['style_color'].$_POST['style_bold'];

		$cat = $this->categorys[$_POST['catid']];
		$_POST['url'] = geturl($cat,$_POST['id'],$this->sysConfig);
		$_POST['url'] =$_POST['url'][0];

		if (false === $model->create ()) {
			$this->error ( $model->getError () );
		}
		
		// 更新数据
		$list=$model->save ();
		if (false !== $list) {
			$id= $_POST['id'];
			$catid = MODULE_NAME =='Page' ? $id : $_POST['catid'];

			if($_POST['aid']) {
				$Attachment =M('Attachment');		
				$aids =  implode(',',$_POST['aid']);
				$data['id']= $id;
				$data['catid']= $catid;
				$data['status']= '1';
				$Attachment->where("aid in (".$aids.")")->save($data);
			}
			$cat = $this->categorys[$catid];
			if($cat['ishtml']){
				if(MODULE_NAME!='Page'  && $_POST['status'])	$this->create_show($_POST['id'],MODULE_NAME);				
				if($this->sysConfig['HOME_ISHTML']) $this->create_index();
				$arrparentid = array_filter(explode(',',$cat['arrparentid'].','.$cat['id']));
				foreach($arrparentid as $catid) {
					if($this->categorys[$catid]['ishtml'])	$this->clisthtml($catid);					
				}				
 			}
			$this->assign ( 'jumpUrl', $_SERVER['HTTP_REFERER'] );
			$this->success (L('edit_ok'));
		} else {
			//错误提示
			$this->success (L('edit_error').': '.$model->getDbError());
		}
	}

	function checkfield($fields,$_POST){
		foreach ( $_POST as $key => $val ) {
				$setup=$fields[$key]['setup'];

				//$setup=string2array($fields[$key]['setup']);

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
		return $_POST;
	}

}
