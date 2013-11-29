<?php 
/**
 * 
 * Moldfactory(模具厂家类)
 *
 */
class MoldfactoryAction extends AdminbaseAction
{    
    function _initialize()
    {
		parent::_initialize();		
    }
    /**
     * 主页
     */
    public function index()
    {
    	$this->_list(MODULE_NAME,'','',false,10);
        $this->display();
    }
}