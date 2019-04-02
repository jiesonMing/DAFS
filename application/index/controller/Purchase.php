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

    # 请购单上传数据
    public function purchase_requisition_add()
    {
        return $this->fetch('purchase/purchase_requisition_add');
    }

    # 请购单数据上传
    public function purchase_requisition_upload()
    {
        return json_encode(["code" => 0, "msg" => '', "count" => '', "data" => []]);
    }
}
