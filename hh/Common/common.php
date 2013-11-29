<?php
function template_if($str){
if($str=='else')return '<?php else :?>';
if($str=='endif')return  '<?php endif; ?>';
if(strpos($str,'elseif(')===0) return  '<?php '.$str.': ?>';
if($str=='/')return  '<?php endif; ?>';
return  '<?php if('.$str.') : ?>';
}
function template_else($str){
	return '<?php else :?>';
}
function template_elseif($str){
	return '<?php elseif('.$str.'): ?>';
}

function fieldoption($fields,$value=''){
	$options = explode("\n",$fields['setup']['options']);
	foreach($options as $r) {
		$v = explode("|",$r);
		$k = trim($v[1]);
		$optionsarr[$k] = $v[0];
	}
	if(isset($value)){
		return $optionsarr[$value];
	}else{
		return $optionsarr;
	}
}

function get_arrparentid($pid, $array=array(),$arrparentid='') {
		if(!is_array($array) || !isset($array[$pid])) return $pid;
		$parentid = $array[$pid]['parentid'];
		$arrparentid = $arrparentid ? $parentid.','.$arrparentid : $parentid;
		if($parentid) {
			$arrparentid = get_arrparentid($parentid,$array, $arrparentid);
		}else{
			$data = array();
			$data['bid'] = $pid;
			$data['arrparentid'] = $arrparentid;
		}

		return $arrparentid;
}

function getform($form,$info,$value=''){
	return  $form->$info['type']($info,$value);
}

function getvalidate($info){
        $validate_data=array();
        if($info['minlength']) $validate_data['minlength'] = ' minlength:'.$info['minlength'];
		if($info['maxlength']) $validate_data['maxlength'] = ' maxlength:'.$info['maxlength'];
		if($info['required']) $validate_data['required'] = ' required:true';
		if($info['pattern']) $validate_data['pattern'] = ' '.$info['pattern'].':true';
        if($info['errormsg']) $errormsg = ' title="'.$info['errormsg'].'"';
        $validate= implode(',',$validate_data);
        $validate= 'validate="'.$validate.'" ';
        $parseStr = $validate.$errormsg;
        return $parseStr;
}

function sendmail($tomail,$subject,$body,$config=''){

		if(!$config)$config = F('Config');

		import("@.ORG.PHPMailer");
		$mail = new PHPMailer();

		if($config['mail_type']){
			$mail->IsSMTP();
		}else{
			$mail->IsMail(); // 使用(其他就用SMTP)
		}
		if($config['mail_auth']){
			$mail->SMTPAuth = true; // 开启SMTP认证
		}else{
			$mail->SMTPAuth = false; // 开启SMTP认证
		}

		$mail->PluginDir=LIB_PATH."ORG/";
		$mail->CharSet='utf-8';
		$mail->SMTPDebug  = false;        // 改为2可以开启调试
		$mail->Host = $config['mail_server'];      // GMAIL的SMTP
		//$mail->SMTPSecure = "ssl"; // 设置连接服务器前缀
		//$mail->Encoding = "base64";
		$mail->Port = $config['mail_port'];    // GMAIL的SMTP端口号
		$mail->Username = $config['mail_user']; // GMAIL用户名,必须以@gmail结尾
		$mail->Password = $config['mail_password']; // GMAIL密码
		//$mail->From ="yourphp@163.com";
		//$mail->FromName = "yourphp企业建站系统";
		$mail->SetFrom($config['mail_from'], $config['site_name']);     //发送者邮箱
		$mail->AddAddress($tomail); //可同时发多个
		//$mail->AddReplyTo('147613338@qq.com', 'yourphp'); //回复到这个邮箱
		//$mail->WordWrap = 50; // 设定 word wrap
		//$mail->AddAttachment("/var/tmp/file.tar.gz"); // 附件1
		//$mail->AddAttachment("/tmp/image.jpg", "new.jpg"); // 附件2
		$mail->IsHTML(true); // 以HTML发送
		$mail->Subject = $subject;
		$mail->Body = $body;
		//$mail->AltBody = "This is the body when user views in plain text format";		//纯文字时的Body
		if(!$mail->Send())
		{
			return false;
		}else{
			return true;
		}

}

function template_file($module=''){
	$sysConfig = F('sys.config');
	$tempfiles = dir_list(TMPL_PATH.$sysConfig['DEFAULT_THEME'].'/Home/','html');
	foreach ($tempfiles as $key=>$file){
		$dirname = basename($file);
		if($module){
			if(strstr($dirname,$module.'_')) {
				$arr[$key]['value'] =  substr($dirname,0,strrpos($dirname, '.'));
				$arr[$key]['filename'] = $dirname;
				$arr[$key]['filepath'] = $file;
			}
		}else{
			$arr[$key]['value'] = substr($dirname,0,strrpos($dirname, '.'));
			$arr[$key]['filename'] = $dirname;
			$arr[$key]['filepath'] = $file;
		}
	}
	return  $arr;
}

function fileext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

function dir_path($path) {
	$path = str_replace('\\', '/', $path);
	if(substr($path, -1) != '/') $path = $path.'/';
	return $path;
}

function dir_create($path, $mode = 0777) {
	if(is_dir($path)) return TRUE;
	$ftp_enable = 0;
	$path = dir_path($path);
	$temp = explode('/', $path);
	$cur_dir = '';
	$max = count($temp) - 1;
	for($i=0; $i<$max; $i++) {
		$cur_dir .= $temp[$i].'/';
		if (@is_dir($cur_dir)) continue;
		@mkdir($cur_dir, 0777,true);
		@chmod($cur_dir, 0777);
	}
	return is_dir($path);
}

function dir_copy($fromdir, $todir) {
	$fromdir = dir_path($fromdir);
	$todir = dir_path($todir);
	if (!is_dir($fromdir)) return FALSE;
	if (!is_dir($todir)) dir_create($todir);
	$list = glob($fromdir.'*');
	if (!empty($list)) {
		foreach($list as $v) {
			$path = $todir.basename($v);
			if(is_dir($v)) {
				dir_copy($v, $path);
			} else {
				copy($v, $path);
				@chmod($path, 0777);
			}
		}
	}
    return TRUE;
}

function dir_list($path, $exts = '', $list= array()) {
	$path = dir_path($path);
	$files = glob($path.'*');
	foreach($files as $v) {
		$fileext = fileext($v);
		if (!$exts || preg_match("/\.($exts)/i", $v)) {
			$list[] = $v;
			if (is_dir($v)) {
				$list = dir_list($v, $exts, $list);
			}
		}
	}
	return $list;
}

function dir_tree($dir, $parentid = 0, $dirs = array()) {
	if ($parentid == 0) $id = 0;
	$list = glob($dir.'*');
	foreach($list as $v) {
		if (is_dir($v)) {
            $id++;
			$dirs[$id] = array('id'=>$id,'parentid'=>$parentid, 'name'=>basename($v), 'dir'=>$v.'/');
			$dirs = dir_tree($v.'/', $id, $dirs);
		}
	}
	return $dirs;
}

function dir_delete($dir) {
	$dir = dir_path($dir);
	if (!is_dir($dir)) return FALSE;
	$list = glob($dir.'*');
	foreach($list as $v) {
		is_dir($v) ? dir_delete($v) : @unlink($v);
	}
    return @rmdir($dir);
}


function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}
function savecache($name = '',$id='') {

	if($name=='Field'){
		if($id){
			$Model = M ( $name );
			$list = $Model->order('listorder')->where('moduleid='.$id)->select ();
			$pkid = 'field';
			$data = array ();
			foreach ( $list as $key => $val ) {
				$data [$val [$pkid]] = $val;
			}
			$name=$id.'_'.$name;
			F($name,$data);
		}else{
			$module = F('Module');
			foreach ( $module as $key => $val ) {
				savecache($name,$key);
			}
		}
	}elseif($name=='Config'){

		$Model = M ( $name );
		$list = $Model->select ();
		$data=$sysdata=array();
		foreach($list as $key=>$r) {
			if($r['groupid']==6){
				$sysdata[$r['varname']]=$r['value'];
			}else{
				$data[$r['varname']]=$r['value'];
			}
		}
		F('Config',$data);
		F('sys.config',$sysdata);
	}elseif($name=='Module'){
		$Model = M ( $name );
		$list = $Model->order('listorder')->select ();
		$pkid = $Model->getPk ();
		$data = array ();
		foreach ( $list as $key => $val ) {
			$data [$val [$pkid]] = $val;
			$smalldata[$val['name']] =  $val [$pkid];
		}
		F($name,$data);
		F('Mod',$smalldata);
		//savecache

	}else{
		$Model = M ( $name );
		$list = $Model->order('listorder')->select ();
		$pkid = $Model->getPk ();
		$data = array ();
		foreach ( $list as $key => $val ) {
			$data [$val [$pkid]] = $val;
		}
		F($name,$data);
	}
	return true;
}


function string2array($info) {
	if($info == '') return array();
	$info=stripcslashes($info);
	eval("\$r = $info;");
	return $r;
}

function array2string($info) {
	if($info == '') return '';
	if(!is_array($info)) $string = stripslashes($info);
	foreach($info as $key => $val) $string[$key] = stripslashes($val);
	return addslashes(var_export($string, TRUE));
}

/**
	 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
	 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
	 +----------------------------------------------------------
 * @return string
	 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
	$str = '';
	switch ($type) {
		case 0 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		case 1 :
			$chars = str_repeat ( '0123456789', 3 );
			break;
		case 2 :
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
			break;
		case 3 :
			$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
			break;
		default :
			// 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
			$chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
			break;
	}
	if ($len > 10) { //位数过长重复字符串一定次数
		$chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
	}
	if ($type != 4) {
		$chars = str_shuffle ( $chars );
		$str = substr ( $chars, 0, $len );
	} else {
		// 中文随机字
		for($i = 0; $i < $len; $i ++) {
			$str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
		}
	}
	return $str;
}
function sysmd5($str,$type='sha1'){
	$sysConfig = F('sys.config');
	return hash ( $type, $str.$sysConfig['ADMIN_ACCESS'] );
}
function pwdHash($password, $type = 'md5') {
	return hash ( $type, $password );
}
// 获取客户端IP地址
function get_client_ip(){
   if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
       $ip = getenv("HTTP_CLIENT_IP");
   else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
       $ip = getenv("HTTP_X_FORWARDED_FOR");
   else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
       $ip = getenv("REMOTE_ADDR");
   else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
       $ip = $_SERVER['REMOTE_ADDR'];
   else
       $ip = "unknown";
   return($ip);
}


//字符串截取
function str_cut($sourcestr,$cutlength,$suffix='...')
{
   $returnstr='';
   $i=0;
   $n=0;
   $str_length=strlen($sourcestr);//字符串的字节数
   while (($n<$cutlength) and ($i<=$str_length))
   {
      $temp_str=substr($sourcestr,$i,1);
      $ascnum=Ord($temp_str);//得到字符串中第$i位字符的ascii码
      if ($ascnum>=224)    //如果ASCII位高与224，
      {
         $returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
         $i=$i+3;            //实际Byte计为3
         $n++;            //字串长度计1
      }
      elseif ($ascnum>=192) //如果ASCII位高与192，
      {
         $returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
         $i=$i+2;            //实际Byte计为2
         $n++;            //字串长度计1
      }
      elseif ($ascnum>=65 && $ascnum<=90) //如果是大写字母，
      {
         $returnstr=$returnstr.substr($sourcestr,$i,1);
         $i=$i+1;            //实际的Byte数仍计1个
         $n++;            //但考虑整体美观，大写字母计成一个高位字符
      }
      else                //其他情况下，包括小写字母和半角标点符号，
      {
         $returnstr=$returnstr.substr($sourcestr,$i,1);
         $i=$i+1;            //实际的Byte数计1个
         $n=$n+0.5;        //小写字母和半角标点等与半个高位字符宽...
      }
   }

   if ($str_length/3>$cutlength){
          $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
      }
    return $returnstr;

}

function IP($ip='',$file='UTFWry.dat') {
	import("@.ORG.IpLocation");
	$iplocation = new IpLocation($file);
	$location = $iplocation->getlocation($ip);
	return $location;
}

function byte_format($input, $dec=0)
{
  $prefix_arr = array("B", "K", "M", "G", "T");
  $value = round($input, $dec);
  $i=0;
  while ($value>1024)
  {
     $value /= 1024;
     $i++;
  }
  $return_str = round($value, $dec).$prefix_arr[$i];
  return $return_str;
}

/**
 +----------------------------------------------------------
 * 代码加亮
 +----------------------------------------------------------
 * @param String  $str 要高亮显示的字符串 或者 文件名
 * @param Boolean $show 是否输出
 +----------------------------------------------------------
 * @return String
 +----------------------------------------------------------
 */
function highlight_code($str,$show=false)
{
    if(file_exists($str)) {
        $str    =   file_get_contents($str);
    }
    $str  =  stripslashes(trim($str));
    $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);
    $str = str_replace(array('&lt;?php', '?&gt;',  '\\'), array('phptagopen', 'phptagclose', 'backslashtmp'), $str);
    $str = '<?php //tempstart'."\n".$str.'//tempend ?>'; // <?
    $str = highlight_string($str, TRUE);
    if (abs(phpversion()) < 5)
    {
        $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
        $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
    }
    // Remove our artificially added PHP
    $str = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $str);
    $str = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $str);
    $str = preg_replace("#//tempend.+#is", "</span>\n</code>", $str);
    // Replace our markers back to PHP tags.
    $str = str_replace(array('phptagopen', 'phptagclose', 'backslashtmp'), array('&lt;?php', '?&gt;', '\\'), $str); //<?
    $line   =   explode("<br />", rtrim(ltrim($str,'<code>'),'</code>'));
    $result =   '<div class="code"><ol>';
    foreach($line as $key=>$val) {
        $result .=  '<li>'.$val.'</li>';
    }
    $result .=  '</ol></div>';
    $result = str_replace("\n", "", $result);
    if( $show!== false) {
        echo($result);
    }else {
        return $result;
    }
}

function color_txt($str)
{
    if(function_exists('iconv_strlen')) {
    	$len  = iconv_strlen($str);
    }else if(function_exists('mb_strlen')) {
    	$len = mb_strlen($str);
    }
    $colorTxt = '';
    for($i=0; $i<$len; $i++) {
               $colorTxt .=  '<span style="color:'.rand_color().'">'.msubstr($str,$i,1,'utf-8','').'</span>';
     }

    return $colorTxt;
}
function showExt($ext,$pic=true) {
	static $_extPic = array(
		'dir'=>"folder.gif",
		'doc'=>'msoffice.gif',
		'rar'=>'rar.gif',
		'zip'=>'zip.gif',
		'txt'=>'text.gif',
		'pdf'=>'pdf.gif',
		'html'=>'html.gif',
		'png'=>'image.gif',
		'gif'=>'image.gif',
		'jpg'=>'image.gif',
		'php'=>'text.gif',
	);
	static $_extTxt = array(
		'dir'=>'文件夹',
		'jpg'=>'JPEG图象',
		);
	if($pic) {
		if(array_key_exists(strtolower($ext),$_extPic)) {
			$show = "<IMG SRC='".WEB_PUBLIC_PATH."/Images/extension/".$_extPic[strtolower($ext)]."' BORDER='0' alt='' align='absmiddle'>";
		}else{
			$show = "<IMG SRC='".WEB_PUBLIC_PATH."/Images/extension/common.gif' WIDTH='16' HEIGHT='16' BORDER='0' alt='文件' align='absmiddle'>";
		}
	}else{
		if(array_key_exists(strtolower($ext),$_extTxt)) {
			$show = $_extTxt[strtolower($ext)];
		}else{
			$show = $ext?$ext:'文件夹';
		}
	}

	return $show;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify ($length=4,$mode=1) {
    return rand_string($length,$mode);
}


function geturl($cat,$id='',$sysConfig=''){

		if($sysConfig){
			C('URL_MODEL',$sysConfig['URL_MODEL']);
			C('URL_PATHINFO_DEPR',$sysConfig['URL_PATHINFO_DEPR']);
			C('URL_HTML_SUFFIX',$sysConfig['URL_HTML_SUFFIX']);
		}
		if($id){
			if($cat['ishtml']){
				$url[] = __ROOT__.'/'.$cat['parentdir'].$cat['catdir'].'/show_'.$id.'.html';
				$url[] = __ROOT__.'/'.$cat['parentdir'].$cat['catdir'].'/show_'.$id.'_{$page}.html';
			}else{
				$url[] = U("Home-$cat[module]/show?id=$id");
				$url[] = U("Home-$cat[module]/show?id=".$id.'&'.C('VAR_PAGE').'={$page}');
				if($sysConfig['URL_MODEL']){
					$url = str_replace('Home'.C('URL_PATHINFO_DEPR'),'',$url);
					$url = str_replace('Admin'.C('URL_PATHINFO_DEPR'),'',$url);
					if($sysConfig['URL_MODEL']==2) $url = str_replace('index.php'.C('URL_PATHINFO_DEPR'),'',$url);
				}else{
					$url = str_replace('g=Admin&','',$url);
					$url = str_replace('g=Home&','',$url);
				}
			}
		}else{
			if($cat['ishtml']){
				$url[] = __ROOT__.'/'.$cat['parentdir'].$cat['catdir'].'/';
				$url[] = __ROOT__.'/'.$cat['parentdir'].$cat['catdir'].'/{$page}.html';
			}else{
				$url[] = U("Home-$cat[module]/index?id=$cat[id]");
				$url[] = U("Home-$cat[module]/index?id=$cat[id]&".C('VAR_PAGE').'={$page}');

				if($sysConfig['URL_MODEL']){
					$url = str_replace('Home'.C('URL_PATHINFO_DEPR'),'',$url);
					$url = str_replace('Admin'.C('URL_PATHINFO_DEPR'),'',$url);
					if($sysConfig['URL_MODEL']==2) $url = str_replace('index.php'.C('URL_PATHINFO_DEPR'),'',$url);
				}else{
					$url = str_replace('g=Admin&','',$url);
					$url = str_replace('g=Home&','',$url);
				}
			}
		}
		if($sysConfig) C('URL_MODEL',0);
		return $url;
}

function content_pages($num, $p,$pageurls) {

	$multipage = '';
	$page = 11;
	$offset = 4;
	$pages = $num;
	$from = $p - $offset;
	$to = $p + $offset;
	$more = 0;
	if($page >= $pages) {
		$from = 2;
		$to = $pages-1;
	} else {
		if($from <= 1) {
			$to = $page-1;
			$from = 2;
		} elseif($to >= $pages) {
			$from = $pages-($page-2);
			$to = $pages-1;
		}
		$more = 1;
	}
	if($p>0) {
		$perpage = $p == 1 ? 1 : $p-1;
		if($perpage==1){
			$multipage .= '<a class="a1" href="'.$pageurls[$perpage][0].'">'.L('previous').'</a>';
		}else{
			$multipage .= '<a class="a1" href="'.$pageurls[$perpage][1].'">'.L('previous').'</a>';
		}
		if($p==1) {
			$multipage .= ' <span>1</span>';
		} elseif($p>6 && $more) {
			$multipage .= ' <a href="'.$pageurls[1][0].'">1</a>..';
		} else {
			$multipage .= ' <a href="'.$pageurls[1][0].'">1</a>';
		}
	}
	for($i = $from; $i <= $to; $i++) {
		if($i != $p) {
			$multipage .= ' <a href="'.$pageurls[$i][1].'">'.$i.'</a>';
		} else {
			$multipage .= ' <span>'.$i.'</span>';
		}
	}
	if($p<$pages) {
		if($p<$pages-5 && $more) {
			$multipage .= ' ..<a href="'.$pageurls[$pages][1].'">'.$pages.'</a> <a class="a1" href="'.$pageurls[$p+1][1].'">'.L('next').'</a>';
		} else {
			$multipage .= ' <a href="'.$pageurls[$pages][1].'">'.$pages.'</a> <a class="a1" href="'.$pageurls[$p+1][1].'">'.L('next').'</a>';
		}
	} elseif($p==$pages) {
		$multipage .= ' <span>'.$pages.'</span> <a class="a1" href="'.$pageurls[$p][1].'">'.L('next').'</a>';
	}
	return $multipage;
}

function thumb($f, $tw=300, $th=300 ,$autocat=0, $nopic = 'nopic.gif',$t=''){
	if(empty($f)) return WEB_PUBLIC_PATH.$nopic;
	$pathinfo = pathinfo($f);
	if(empty($t)){
		$t = $pathinfo['dirname'].'/thumb_'.$tw.'_'.$th.'_'.$pathinfo['basename'];
		if(is_file($t)){
			return  $t;
		}
	}
	$temp = array(1=>'gif', 2=>'jpeg', 3=>'png');
	list($fw, $fh, $tmp) = getimagesize($f);
	if(!$temp[$tmp]){	return false; }

	if($autocat){
		if($fw/$tw > $fh/$th){
		$fw = $tw * ($fh/$th);
		}else{
		$fh = $th * ($fw/$tw);
		}
	}else{

		 $scale = min($tw/$fw, $th/$fh); // 计算缩放比例
            if($scale>=1) {
                // 超过原图大小不再缩略
                $tw   =  $fw;
                $th  =  $fh;
            }else{
                // 缩略图尺寸
                $tw  = (int)($fw*$scale);
                $th = (int)($fh*$scale);
            }


	}

	$tmp = $temp[$tmp];
	$infunc = "imagecreatefrom$tmp";
	$outfunc = "image$tmp";
	$fimg = $infunc($f);

	if($tmp != 'gif' && function_exists('imagecreatetruecolor')){
		$timg = imagecreatetruecolor($tw, $th);
	}else{
		$timg = imagecreate($tw, $th);
	}


	if(function_exists('imagecopyresampled'))
		imagecopyresampled($timg, $fimg, 0,0, 0,0, $tw,$th, $fw,$fh);
	else
		imagecopyresized($timg, $fimg, 0,0, 0,0, $tw,$th, $fw,$fh);
	if($tmp=='gif' || $tmp=='png') {
		$background_color  =  imagecolorallocate($timg,  0, 255, 0);  //  指派一个绿色
		imagecolortransparent($timg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
	}
	$outfunc($timg, $t);
	imagedestroy($timg);
	imagedestroy($fimg);
	return $t;
}

/**
 +----------------------------------------------------------
 * 把返回的数据集转换成Tree
 +----------------------------------------------------------
 * @access public
 +----------------------------------------------------------
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function list_to_tree($list, $pk='id',$pid = 'pid',$child = '_child',$root=0)
{
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}
?>