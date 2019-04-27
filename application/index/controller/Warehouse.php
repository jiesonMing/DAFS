<?php
namespace app\index\controller;
use think\Db;
use app\index\controller\Base;
use think\Config;

/*
* 仓库管理
* jieson 2019.04.27
*/
class Warehouse extends Base
{
    protected $datas = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->datas  = $this->req->param(true);
    }

    # 入库view
    public function inwarehouse_view()
    {
        return $this->fetch('warehouse/inwarehouse');
    }
}