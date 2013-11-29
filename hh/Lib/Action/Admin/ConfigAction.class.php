<?php

/**
 * 
 * Config(系统配置文件)
 *
 */
class ConfigAction extends AdminbaseAction {
	
	protected $dao, $config,$seo_config ,$user_config, $site_config, $mail_config, $attach_config;
    function _initialize()
    {	
		parent::_initialize();
		$this->dao = M('Config');
		$this->assign($this->siteConfig);

    }
	public function index() {
	  
		$this->config = $config = $this->dao->findAll();
		foreach($config as $key=>$r) {
			if($r['groupid']==1)$this->user_config[$r['varname']]=$r;
			if($r['groupid']==2)$this->site_config[$r['varname']]=$r;			
		}
		$this->assign('user_config',$this->user_config);
		$this->assign('site_config',$this->site_config);
		$this->display(); 
	}

	public function sys() {
		$sysconfig = F("sys.config");
		$this->assign('yesorno',array(0 => L('no'),1  => L('yes')));
		$this->assign('openarr',array(0 => L('close_select'),1  => L('open_select')));
		$this->assign('enablearr',array(0 => L('disable'),1  => L('enable')));
		$this->assign('urlmodelarr',array(0 => L('URL_MODEL0').'(m=module&a=action&id=1)',1  => L('URL_MODEL1').'(index.php/Index_index_id_1)',2 => L('URL_MODEL2').'(Index_index_id_1)' ,3=>L("URL_MODEL3") ));
		$this->assign('readtypearr', array(0=>'readfile',1=> 'redirect'));
		$this->assign($sysconfig);
		$this->display();
	}
 
 
	public function add() {		 
		$this->display();
	}

	public function insert() {
		if (false === $this->dao->create ()) {
			$this->error ( $this->dao->getError () );
		}
		//保存当前数据对象
		$list=$this->dao->add ();
		savecache('Config');
		if ($list!==false) {
			$this->success (L('add_ok'));
		} else {
			$this->error (L('add_error'));
		}
	}

	public function seo() {
		$this->display();
	}

	public function attach(){
		$this->display();
	}
	

	public function mail() { 
		$this->display();
	}
 
 	public function dosite() {
		if(!$_POST[C('TOKEN_NAME')] || $_POST[C('TOKEN_NAME')]!=$_SESSION[C('TOKEN_NAME')]){
				$this->error (L('_TOKEN_ERROR_'));
		}
		unset($_POST['__hash__']);
		 

		foreach($_POST as $key=>$value) {			
			$data['value']=$value;
			$f = $this->dao->where("varname='".$key."'")->save($data);				 
		}
		$f = savecache(MODULE_NAME);
		if($f){
			$this->success(L('do_ok'));
		}else{
			$this->error (L('do_error'));
		}	
	}

	public function testmail(){ 
		$mailto = $_GET['mail_to'];
		$message = 'hezen test mail';
	 
		$r = sendmail($mailto,$this->siteConfig['site_name'],$message,$_POST); 
		if($r){
			$this->ajaxReturn($r,L('mailsed_ok'),1);
		}else{
			$this->ajaxReturn($r,L('mailsed_error'),1);
		}
	}
}
?>