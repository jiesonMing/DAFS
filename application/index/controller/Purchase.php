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
        // \think\Loader::import('export.PHPExcel.PHPExcel');
        require_once EXTEND_PATH."export/PHPExcel.php";
        $objPHPExcel = new \PHPExcel();

        // 获取表单上传文件
        $file = request()->file('file');
        $info = $file->validate(['ext' => 'xlsx,xls,pdf'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        // 数据为空返回错误
        if( empty($info) ){
            return ajaxReturn(-1, '导入数据失败');
        }

        // 获取文件名
        $exclePath = $info->getSaveName();
        // 上传文件的地址
        $filename = ROOT_PATH . 'public' . DS . 'uploads'.DS . $exclePath;

        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );

        // \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objExcel = $objReader->load($filename);
        }

        $excel_array=$objExcel->getsheet(0)->toArray();   //转换为数组格式
        array_shift($excel_array);  //删除第一个数组(标题);
        array_shift($excel_array);  //删除th

        $data = $mainData = [];
        foreach ($excel_array as $k => $v){
            // 主表数据
            if (strstr($v[0], '采购部门执行结果')) {
                $mainData['remarks'] = $v[0];
            } else {
                // var_dump($v[0]);exit;
            }

            // item表数据
            if (empty($v[1])) continue;
            $data[$k]["name"]     = $v[1]; //拟采购物品名称
            $data[$k]["spec"]     = $v[2]; //规格
            $data[$k]["unit"]     = $v[3]; //单位
            $data[$k]["qty"]      = $v[4]; //数量
            $data[$k]["price"]    = $v[5]; //单件估价，元
            $data[$k]["amount"]   = $v[6]; //预算总价，元
            $data[$k]["use"]      = $v[7]; //用途
            $data[$k]["needtime"] = $v[8]; //需求日期
            $data[$k]["remarks"]  = $v[9]; //备注
        }

        // unlink($filename);
        // var_dump($excel_array);exit;

        return ajaxReturn(0, 'success', $excel_array);
    }

    # 截取两个字符之间的字符串
    function get_between($input, $start, $end) {
        $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));
        return $substr;
    }
}
