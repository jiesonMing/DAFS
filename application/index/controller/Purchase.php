<?php
namespace app\index\controller;
use app\index\controller\Base;

/*
* 采购管理
*/
class Purchase extends Base
{
    # 请购单
    public function purchase_requisition()
    {
        return $this->fetch('purchase/purchase_requisition');
    }
}
