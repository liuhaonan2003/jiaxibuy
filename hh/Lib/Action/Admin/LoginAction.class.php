<?php
/**
 *
 * Login(后台登陆页面)
 *
 */

class LoginAction extends Action{
    private $adminid ,$groupid ,$sysConfig ,$cache_model,$siteConfig,$menudata ;
	private $o_qq;
    function _initialize()
    {
		$this->siteConfig = F('Config');
		$this->sysConfig = F('sys.config');
		C('ADMIN_ACCESS',$this->sysConfig['ADMIN_ACCESS']);

		import('@.TagLib.TagLibYP');
        $this->adminid = $_SESSION['adminid'];
        $this->groupid = $_SESSION['groupid'];
		
		import("@.ORG.Oauth_qq");
		
		$config['appid']    = '100481834';
		$config['appkey']   = '76ff40384731e295a81021d4ceaf7f7f';
		$config['callback'] = 'http://www.jiaxibuy.com/index.php?g=Admin&m=Login&a=index1';
		$this->o_qq = Oauth_qq::getInstance($config);
    }
    /**
     * 登录页
     *
     */
    public function index()
    {		
		if(is_file(RUNTIME_PATH.'~app.php'))unlink(RUNTIME_PATH.'~app.php');
		if(is_file(RUNTIME_PATH.'~runtime.php'))unlink(RUNTIME_PATH.'~runtime.php');
		if(is_file(RUNTIME_PATH.'~allinone.php'))@unlink(RUNTIME_PATH.'~allinone.php');

		$this->menudata = F('Menu');
		$this->cache_model=array('Menu','Config','Module','Role','Category','Posid','Field','Type');
		if(empty($this->siteConfig) || empty($this->sysConfig) || empty($this->menudata)){
			foreach($this->cache_model as $r){
				savecache($r);
			}
		}
		if($this->_adminid){
			$this->assign('jumpUrl',U('Index/index'));
			$this->success(L('logined'));
		}
        $this->display();
    }
	function lo(){
		$this->o_qq->login();
	}
    function index1(){
    	$this->o_qq->callback();
		dump($this->o_qq->get_openid());
		dump($this->o_qq->get_user_info());exit;
    	$this->display();
    }
	
    /**
     * 提交登录
     *
     */
    public function doLogin()
    {

        if(!$_POST[C('TOKEN_NAME')] || $_POST[C('TOKEN_NAME')]!=$_SESSION[C('TOKEN_NAME')]){
				$this->error (L('_TOKEN_ERROR_'));
		}
        unset($_POST['__hash__']);

		if(empty($this->sysConfig)) $this->error(L('NO SYSTEM CONFIG FILE'));
		$username = trim($_POST['username']);
        $password = trim($_POST['password']);
        //$verifyCode = trim($_POST['verifyCode']);

        if(empty($username) || empty($password)){
           $this->error(L('empty_username_empty_password'));
        }/*elseif(md5($verifyCode) != $_SESSION['verify']){
           $this->error(L('error_verify'));
        }*/


        $condition = array();
        $condition['username'] = $username;

		import ( '@.ORG.RBAC' );
        $authInfo = RBAC::authenticate($condition);
        //使用用户名、密码和状态的方式进行认证
        if(false === $authInfo) {
            $this->error(L('empty_userid'));
        }else {
            if($authInfo['password'] != sysmd5($_POST['password'])) {
            	$this->error(L('password_error'));
            }

			$_SESSION['username'] = $authInfo['username'];
			$_SESSION['adminid'] = $_SESSION['userid'] = $authInfo['id'];
			$_SESSION['groupid'] = $authInfo['groupid'];
			$_SESSION['adminaccess'] = C('ADMIN_ACCESS');
            $_SESSION[C('USER_AUTH_KEY')]	=	$authInfo['id'];
            $_SESSION['email']	=	$authInfo['email'];
            $_SESSION['lastLoginTime']		=	$authInfo['last_logintime'];
			$_SESSION['login_count']	=	$authInfo['login_count']+1;

            if($authInfo['groupid']==1) {
				$_SESSION[C('ADMIN_AUTH_KEY')]=true;
            }

            //保存登录信息
			$dao = M('User');
			$data = array();
			$data['id']	=	$authInfo['id'];
			$data['last_logintime']	=	time();
			$data['last_ip']	=	 get_client_ip();
			$data['login_count']	=	array('exp','login_count+1');
			$dao->save($data);

           // 缓存访问权限
            RBAC::saveAccessList();
			$this->ajaxReturn($authInfo,L('login_ok'),1);
		}

    }

    /**
     * 验证码
     *
     */
    public function verify()
    {
        header ( 'Content-type: image/jpeg' );
		$type = isset ( $_GET ['type'] ) ? $_GET ['type'] : 'jpeg';
        import("@.ORG.Image");
        Image::buildImageVerify(4,1,$type);
    }


    /**
     * 退出登录
     *
     */
    public function logout()
    {
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			unset($_SESSION[C('USER_AUTH_KEY')]);
			unset($_SESSION);
			session_destroy();
            $this->assign('jumpUrl',U('Login/index'));
			$this->success(L('loginouted'));
        }else {
			$this->assign('jumpUrl',U('Login/index'));
            $this->error(L('logined'));
        }
    }

    function checkEmail(){
		$user=M('User');

        $email=$_GET['email'];
		$userid=intval($_GET['userid']);
		if(empty($userid)){
			if($user->getByEmail($email)){
				 echo 'false';
			}else{
				echo 'true';
			}
		}else{
			//判断邮箱是否已经使用
			if($user->where("id!={$userid} and email='{$email}'")->find()){
				 echo 'false';
			}else{
				echo 'true';
			}
		}
        exit;
	}
}
