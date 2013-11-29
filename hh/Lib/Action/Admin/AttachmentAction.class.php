<?php
/**
 * 
 * Attachment(附件管理)
 *
 */
if(!defined("YOURPHP")) exit("Access Denied");
class AttachmentAction extends AdminbaseAction {

	protected $dao;
    function _initialize()
    {	
		if($_POST['PHPSESSID'] && $_POST['userid'] && $_POST['swf_auth_key']){
			if($_POST['swf_auth_key']==md5(C('ADMIN_ACCESS').$_POST['PHPSESSID'].$_POST['userid'])){
				$_SESSION[C('USER_AUTH_KEY')] = $_POST['userid'];			
			}
			if($_POST['isadmin']) $_SESSION[C('ADMIN_AUTH_KEY')] = true;
		}
		parent::_initialize();
		$this->assign($this->siteConfig);
		$this->dao=M('Attachment');
    }
	public function index(){

		if($_SESSION[C('ADMIN_AUTH_KEY')]) $isadmin=1;
		$sessid =time();

		$types = '*.'.str_replace(",",";*.",$_REQUEST['file_types']); ;
		$this->assign('moduleid',$_REQUEST['moduleid']);
		$this->assign('file_size',$_REQUEST['file_size']);
		$this->assign('file_limit',$_REQUEST['file_limit']);
		$this->assign('file_types',$types);
		$this->assign('isthumb',$_REQUEST['isthumb']);
		$this->assign('isadmin',$isadmin);
		$this->assign('sessid',$sessid);
		$this->assign('userid',$_SESSION[C('USER_AUTH_KEY')]);
		$swf_auth_key = md5(C('ADMIN_ACCESS').$sessid.$_SESSION[C('USER_AUTH_KEY')]);
		$this->assign('swf_auth_key',$swf_auth_key);

		$this->display();
	}

	public function upload(){

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
			$this->ajaxReturn(0,$upload->getErrorMsg(),0);
        } else { 
            //取得成功上传的文件信息 
            $uploadList = $upload->getUploadFileInfo(); 
			
			if($_POST['addwater']){
				import("@.ORG.Image");  
				Image::watermark($uploadList[0]['savepath'].$uploadList[0]['savename'],'',$this->siteConfig);
			}
			
			$imagearr = explode(',', 'jpg,gif,png,jpeg,bmp,ttf,tif'); 
			$data=array();
			$model = M('Attachment');
			//保存当前数据对象
			$data['moduleid'] = $_REQUEST['moduleid'];
			$data['catid'] = 0;
			$data['userid'] = $_REQUEST['userid'];
			$data['filename'] = $uploadList[0]['name'];
			$data['filepath'] = $uploadList[0]['savepath'].$uploadList[0]['savename'];
			$data['filesize'] = $uploadList[0]['size']; 
			$data['fileext'] = $uploadList[0]['extension']; 
			$data['isimage'] = in_array($uploadList[0]['extension'],$imagearr) ? 1 : 0;
			$data['isthumb'] = intval($_REQUEST['isthumb']);
			$data['createtime'] = time();
			$data['uploadip'] = get_client_ip();
			$aid = $model->add($data); 
			$returndata['aid']		= $aid;
			$returndata['filepath'] = $data['filepath'];
			$returndata['fileext']  = $data['fileext'];
			$returndata['isimage']  = $data['isimage'];
			$returndata['filename'] = $data['filename'];
			$returndata['filesize'] = $data['filesize']; 

			$this->ajaxReturn($returndata,L('upload_ok'), '1');
			//print_r($uploadList[0]['savepath'].$uploadList[0]['savename']);
        }
	
	}

	public function filelist(){

		$where= $_REQUEST['typeid'] ?  " status=1 " : " status=0 ";
		if(!$_SESSION[C('ADMIN_AUTH_KEY')]) $where .=" and userid = ".$_SESSION['userid'] ;
		import ( '@.ORG.Page' );
		$count = $this->dao->where($where)->count();
		$page=new Page($count,12); 

		$page->urlrule = 'javascript:ajaxload('.$_REQUEST['typeid'].',{$page},\''.$_REQUEST['inputid'].'\');';
		$show = $page->show(); 
		$this->assign("page",$show);
		$list=$this->dao->order('aid desc')->where($where)
		->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('list',$list);
		$this->display();
	}
	
}
?>