<?php
if(!defined("YOURPHP")) exit("Access Denied");
class EmptyAction extends Action
{	
	public function _empty()
	{
		$mod = F('Mod');
		if(!$mod[MODULE_NAME]){ 
			//header('HTTP/1.1 404  Bad Request');
			//header("Location: /404.html");
			throw_exception('404');
		}

		if(GROUP_NAME=='Admin'){
			R('Admin.Content',ACTION_NAME);
		}else{
			$id =  intval($_REQUEST['id']);
			$a=ACTION_NAME;
			import('@.Action.Base');
			$bae=new BaseAction();
			if(!method_exists($bae,$a)){
				//header('HTTP/1.1 404  Bad Request');
				//header("Location: /404.html");
				throw_exception('404');
			}
			$bae->$a($id,MODULE_NAME);		 
			//BaseAction::_initialize();
			//BaseAction::$a($id,MODULE_NAME);
			//$do =A('Home.index');
			//$do->index($id,MODULE_NAME);			
			//parent::index($id,MODULE_NAME); 
		}
	}

}
?>