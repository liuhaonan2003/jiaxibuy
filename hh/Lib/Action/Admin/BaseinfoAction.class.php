<?php 
/**
 * 
 * Baseinfo(基本信息类)
 *
 */
class BaseinfoAction extends AdminbaseAction
{
    protected $dao;
    function _initialize()
    {
		parent::_initialize();		
    }
    /**
     * 主页
     */
    public function index()
    {
        $this->display();
    }
	/**
     * 模具类型
     */
	public function moldtype()
    {
        $this->display();		
	}
	/**
     * 模具厂家
     */
	public function moldfactory()
    {
		$this->display();
	}
	/**
     * 模具归属
     */
	public function moldvesting()
    {
		$this->display();
	}
	/**
     * 发放性质
     */
	public function granttype()
    {
		$this->display();
	}
	/**
     * 发放地址
     */
	public function grantaddress()
    {
		$this->display();
	}
    /**
     * 插入
     */
    public function insert()
    {
		
    }    
    /**
     * 更新
     */
    public function update()
    {
		
    }
	/**
     * 删除
     */
	public function delete() {
		
	}
	/**
     * 查询
     */
    public function select()
    {
		
    }
}