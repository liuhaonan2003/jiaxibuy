<?php
define('YOURPHP', 'YourPHP');
define('UPLOAD_PATH', './Uploads/');
define('VERSION', 'v1.0');
define('UPDATETIME', '20110613');
$database = require ('./config.php');
$config	= array(
		'DEFAULT_CHARSET' => 'utf-8',
		'APP_GROUP_LIST' => 'Admin',
		'DEFAULT_GROUP' =>'Admin',
		'TMPL_FILE_DEPR' => '_',
		'DB_FIELDS_CACHE' => false,
		'DB_FIELDTYPE_CHECK' => true,
		'URL_CASE_INSENSITIVE'=>true,
		'URL_ROUTER_ON' => true,
		'URL_AUTO_REDIRECT' => false,
		'DEFAULT_LANG'   =>    'zh-cn',//默认语言
		'LANG_SWITCH_ON' => true, //多语言
		'TMPL_DETECT_THEME'     => true,
		'TAG_EXTEND_PARSE' =>array(
			'if' => 'template_if',
			'else' =>'template_else',
			'elseif' => 'template_elseif'
		),
		'APP_AUTOLOAD_PATH'=> 'Think.Util.,@.TagLib.',
		'TAGLIB_LOAD' => true,
		'TAGLIB_PRE_LOAD' => 'html,yp',
        'HTML_PATH'=>'./',
		'TMPL_PARSE_STRING'		=>array(
			'../Public' => __ROOT__.'/Yourphp/Tpl/'.C('DEFAULT_THEME').'/Public',
			'__TMPL__' => __ROOT__.'/Yourphp/Tpl/'.C('DEFAULT_THEME')
		)
);
return array_merge($database, $config);
?>