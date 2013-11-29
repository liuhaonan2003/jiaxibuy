<?php
class OrderAction extends AdminbaseAction
{

	protected $dao;
    function _initialize()
    {	
		parent::_initialize();
		$this->dao=M('Order');

	
    }

    public function index()
    {
		$template =  file_exists(TEMPLATE_PATH.'/'.GROUP_NAME.'/'.MODULE_NAME.'_index.html') ? MODULE_NAME.'_index' : 'content_index';
	    $this->_list(MODULE_NAME);
        $this->display ($template);
    }
 
 	public function edit(){
		$sn = $_REQUEST['sn'];
		$id= $_REQUEST['id'];
		$model =M('Order');
		if($sn){
			$cart = $model->getBySn($sn); 
		}elseif($id){
		   $cart = $model->find($orderid); 
		}else{
			$this->error ( L('do_empty'));
		}
		 
		$cart['productlist']=unserialize($cart['productlist']);

		foreach((array)$cart['productlist'] as $key =>$rs){
			$cart['totalnum'] +=$rs['num'];
			$cart['totalprice'] += $rs['num']*$rs['price'];
			$cart['productlist'][$key]['countprice'] = $rs['num']*$rs['price'];
		}
		$this->assign('cart',$cart);
		$this->display();
		
	}
	public function show(){
		$sn = $_REQUEST['sn'];
		$id= $_REQUEST['id'];
		$model =M('Order');
		if($sn){
			$cart = $model->getBySn($sn); 
		}elseif($id){
		   $cart = $model->find($orderid); 
		}else{
			$this->error ( L('do_empty'));
		}
		 
		$cart['productlist']=unserialize($cart['productlist']);

		foreach((array)$cart['productlist'] as $key =>$rs){
			$cart['totalnum'] +=$rs['num'];
			$cart['totalprice'] += $rs['num']*$rs['price'];
			$cart['productlist'][$key]['countprice'] = $rs['num']*$rs['price'];
		}
		$this->assign('cart',$cart);
		$this->display();
		
	}

}
?>