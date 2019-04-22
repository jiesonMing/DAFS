<?php
namespace app\index\controller;
use think\Db;
use app\index\controller\Base;
use app\index\model\PurchasePre;
use app\index\model\PurchasePreItem;

/*
* 采购管理的采购单
*/
class Purchase extends Base
{
    # 请购单
    public function purchase_requisition_view()
    {
        return $this->fetch('purchase/purchase_requisition');
    }

    # 请购单主数据
    public function purchase_requisition_data()
    {
        $this->datas  = $this->req->param(true);

        $where = 'isdel = 0';
        if (isset($this->datas['search'])) {
            $where.=" and concat(purchase_code, filename) like '%{$this->datas['search']}%'";
        }
        if (isset($this->datas['startTime']) && !empty($this->datas['startTime'])) {
            $where.=" and addtime >=".strtotime($this->datas['startTime']);
        }
        if (isset($this->datas['endTime']) && !empty($this->datas['endTime'])) {
            $where.=" and addtime <=".strtotime($this->datas['endTime']);
        }
        
        $PurchasePre = new PurchasePre();
        $list = $PurchasePre
            ->field('id,title,purchase_code,filepath,remarks,addtime,filename,pdfname,pdfpath')
            ->where($where)
            ->order('id', 'desc')
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['addtime']  = date('Y-m-d H:i:s', $v['addtime']);
            $v['pdfpath'] = "<a href='http://{$_SERVER['SERVER_NAME']}{$v['pdfpath']}' target='_blank' style='color:#1E9FFF'>{$v['pdfname']}</a>";
        }
        return ajaxReturn(0,'success', $list);
    }

    # 请购单items
    public function purchase_requisition_items_view()
    {
        $this->datas  = $this->req->param(true);
        $PurchasePre = PurchasePre::get($this->datas['preId']);
        if($PurchasePre->filetype == 2) {
            // 查看pdf文件
            header("location:http://{$_SERVER['SERVER_NAME']}{$PurchasePre->filepath}");exit;
        }
        $this->assign('preId', $this->datas['preId']);
        return $this->fetch('purchase/purchase_requisition_item'); 
    }

    # 请购单items 数据
    public function purchase_requisition_items_data()
    {
        $this->datas  = $this->req->param(true);
        $data = PurchasePreItem::all(['ppid' => $this->datas['preId']]);
        foreach ($data as $k => &$v) {
            $v['index']    = $k+1;
        }
        return ajaxReturn(0,'success', $data);
    }

    # 请购单上传数据
    public function purchase_requisition_add_view()
    {
        $template = "<a href='http://{$_SERVER['SERVER_NAME']}/template/请购单上传模版.xlsx' style='color:#1E9FFF' target='_blank'>请购单模版</a>";
        $this->assign('template', $template);
        return $this->fetch('purchase/purchase_requisition_add');
    }

    # 手动新增请购单items数据
    public function purchase_requisition_add_data()
    {
        $this->datas  = $this->req->param(true);
        $mainData = array(
            'title'               => $this->datas['title'],
            'purchase_code'        => $this->datas['purchase_code'],
            'applicant'            => $this->datas['applicant'],
            'projectmanager'       => $this->datas['projectmanager'],
            'purchasingdepartment' => $this->datas['purchasingdepartment'],
            'remarks'              => $this->datas['remarks'],
            'addtime'              => time()
        );
        
        $itemsData = $this->datas['itemsData'];
        foreach ($itemsData as &$v) {
            if (empty($v)) unset($v);
        }

        Db::startTrans();
        try {
            $PurchasePre = new PurchasePre;
            $PurchasePre->save($mainData);

            $PurchasePre = PurchasePre::find($PurchasePre->id);
            $PurchasePre->items()->saveAll($itemsData);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 编辑请购单数据
    public function purchase_requisition_edit()
    {
        $this->datas  = $this->req->param(true);

        Db::startTrans();
        try {
            $PurchasePre = PurchasePre::get($this->datas['pid']);
            $PurchasePre->title         = isset($this->datas['title'])?$this->datas['title']:'';
            $PurchasePre->purchase_code = isset($this->datas['purchase_code'])?$this->datas['purchase_code']:'';
            $PurchasePre->remarks       = isset($this->datas['remarks'])?$this->datas['remarks']:'';
            $PurchasePre->save();
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 删除请购单数据
    public function purchase_requisition_del()
    {
        $this->datas  = $this->req->param(true);

        Db::startTrans();
        try {
            $PurchasePre = PurchasePre::get($this->datas['pid']);
            $PurchasePre->isdel = 1;
            $PurchasePre->save();
            
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 编辑请购单items数据
    public function purchase_requisition_items_edit()
    {
        $this->datas  = $this->req->param(true);
        Db::startTrans();
        try {
            $PurchasePreItem = PurchasePreItem::get($this->datas['id']);
            $newArr = array_keys($this->datas);
            $data[$newArr[0]] = $this->datas[$newArr[0]];
            $PurchasePreItem->save($data);
            
           Db::commit();
            return ajaxReturn(0, 'success', $newArr);
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 请购单数据上传,若文件为pdf,则只需保存文件
    public function purchase_requisition_upload()
    {
        $this->datas  = $this->req->param(true);
        $pid = isset($this->datas['pid'])?$this->datas['pid']:'';

        require_once EXTEND_PATH."export/PHPExcel.php";
        $objPHPExcel = new \PHPExcel();

        // 获取表单上传文件
        $file = request()->file('file');
        $filename_old = $_FILES['file']['name'];
        $date = date('Ymd', time());
        // $info = $file->validate(['ext' => 'xlsx,xls,pdf'])->move(ROOT_PATH . 'public' . DS . 'uploads'. DS .$date, iconv("UTF-8","gb2312", $filename1));
        $info = $file->validate(['ext' => 'xlsx,xls,pdf'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        // 数据为空返回错误
        if( empty($info) ){
            return ajaxReturn(-1, '导入数据失败');
        }

        // 获取文件名
        $exclePath = $info->getSaveName();
        // 上传文件的地址
        $filename = ROOT_PATH . 'public' . DS . 'uploads'. DS . $exclePath;

        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );

        $data = $mainData = [];$amount = 0;
        if ($extension != 'pdf' ) {
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

            foreach ($excel_array as $k => $v){
                // item表数据
                if (empty($v[1])) continue;
                $data[$k]["name"]     = $v[1]; //拟采购物品名称
                $data[$k]["spec"]     = $v[2]; //规格
                $data[$k]["unit"]     = $v[3]; //单位
                $data[$k]["qty"]      = $v[4]; //数量
                $data[$k]["price"]    = changeStr($v[5]); //单件估价，元
                $data[$k]["amount"]   = changeStr($v[6]); //预算总价，元
                $data[$k]["use"]      = $v[7]; //用途
                $data[$k]["needtime"] = $v[8]; //需求日期
                $data[$k]["remarks"]  = isset($v[9])?$v[9]:''; //备注

                $amount+=changeStr($v[6]);
            }
        }

        $filepath = DS . 'uploads'. DS . $exclePath;
        $mainData = array(
            'purchase_code' => 'Q-'.date('YmdHis',time()),
            'amount'        => $amount,
            'filename'      => $filename_old,
            'filepath'      => $filepath,
            'filetype'      => $extension == 'pdf'? 2 : 1,
            'addtime'       => time(),
        );

        if ($extension == 'pdf') {
            $mainData['pdfname'] = $filename_old;
            $mainData['pdfpath'] = $filepath;
        }

        // 把数据插入数据表
        Db::startTrans();
        try {
            if (!empty($pid)) {
                $PurchasePre = PurchasePre::get($pid);
                $PurchasePre->pdfname = $filename_old;
                $PurchasePre->pdfpath = $filepath;
                $PurchasePre->save();
                Db::commit();
                return ajaxReturn(0, 'success');
            } else {
                $PurchasePre = new PurchasePre();
                $PurchasePre->save($mainData);

                if ($PurchasePre->id) {
                    $PurchasePre = PurchasePre::get($PurchasePre->id);

                    $items = $PurchasePre->items()->saveAll($data);
                
                    if ($items || empty($data)) {
                        Db::commit();
                        return ajaxReturn(0, 'success');
                    }
                }
            }
            
            Db::rollback();
            return ajaxReturn(-1, 'failed');

        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 上次扫描件view
    public function purchase_requisition_pdf_view()
    {
        $this->datas  = $this->req->param(true);
        $this->assign('pid', $this->datas['preId']);
        return $this->fetch('purchase/purchase_requisition_pdf');
    }

    # 打印数据
    public function print_data($isExport = false)
    {
        header("Content-type:text/html;charset=utf-8");
        vendor('mpdf.mpdf');

        $this->datas  = $this->req->param(true);
        $preData   = PurchasePre::get($this->datas['preId']);
        $itemsData = PurchasePreItem::all(['ppid' => $this->datas['preId']]);

        // 组装数据
        $div = "<div>";
        $div .= "<h2 style='text-align:center;'>
        <b style='border-bottom: 1px solid black;padding: 5px;'>{$preData->title}</b> 项目工程 请 购 单</h2>";
        $div .= "<table border='1' width='800' style='border-collapse: collapse; text-align:center'>";
        $div .= "<tr>";
        $div .= "<td width='40' height='60'>序号</td><td width='150'>拟采购物品名称</td><td width='100'>规格</td>
        <td width='100'>单位</td><td width='80'>数量</td><td width='100'>单件估价(元)</td>
        <td width='100'>预算总价(元)</td><td width='220'>用途</td><td width='100'>需求日期</td><td width='200'>备注</td>";
        $div .= "</tr>";

        $amount = 0;$total = 15;$count = count($itemsData);
        foreach ($itemsData as $k => $v) {
            $k++;
            $div .= "<tr>";
            $div .= "<td height='60'>{$k}</td>";
            $div .= "<td>{$v['name']}</td>";
            $div .= "<td>{$v['spec']}</td>";
            $div .= "<td>{$v['unit']}</td>";
            $div .= "<td>{$v['qty']}</td>";
            $div .= "<td>{$v['price']}</td>";
            $div .= "<td>{$v['amount']}</td>";
            $div .= "<td>{$v['use']}</td>";
            $div .= "<td>{$v['needtime']}</td>";
            $div .= "<td>{$v['remarks']}</td>";
            $div .= "</tr>";

            // 总金额
            $amount+=$v['amount'];
        }
        $chineAmount = convert_2_cn($amount);

        if ($count < $total) {
            // 空白行
            for ($i=0; $i<($total - $count); $i++) {
                $div .= "<tr><td height='60'></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
            }
        }
        

        // 占位符
        $nbsp = '&nbsp;';
        for ($i=0; $i<90; $i++ ) {
            $nbsp .= '&nbsp;';
        }

        $div .= "<tr>";
        $div .= "<td colspan='10' height='85' style='text-align:left;font-size:36px;'>备注：{$preData ->remarks}</td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='40' style='text-align:left;font-size:36px;'>预算总金额：  人 民 币 {$chineAmount} 元 整（￥{$amount}）    </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='80' style='text-align:left;font-size:36px;'>
        <p style='position:absolute;top:1'>申请人：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='80' style='text-align:left;font-size:36px;'>
        <p style='position:absolute;top:1'>项目经理审批：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='80' style='text-align:left;font-size:36px;'>
        <p style='position:absolute;top:1'>采购部门执行结果：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";


        $div .= "</table>";
        $div .= "</div>";

        if ($isExport) {
            return $div;exit;
        }

        $filename = '';
        $mpdf = new \mPDF('zh-CN','A4','','', 5,5,5,5);

        // $mpdf->SetHTMLHeader("
        // <divstyle='margin-top:10px;'>
        // <h2 style='text-align:center;'><b style='border-bottom: 1px solid black;padding: 5px;'>{$preData->title}</b> 项目工程 请 购 单</h2>
        // </div>");

        $mpdf->WriteHTML($div);
        $mpdf->Output(ROOT_PATH."public/template/purchase_pre.pdf");

        header("location:http://{$_SERVER['SERVER_NAME']}/template/purchase_pre.pdf");exit;
    }

    # 导出数据
    public function export_data()
    {
        $this->datas  = $this->req->param(true);
        $preData   = PurchasePre::get($this->datas['preId']);

        $isExport = true;
        $content = $this->print_data($isExport); // 获取table数据然后把html转为xls

        $filetime = $preData->title.' 项目工程 请 购 单';
        $path = $_SERVER['DOCUMENT_ROOT'] . "/uploads/";
        $fp = @fopen($path . ".html", "wb");
        @fwrite($fp, $content);
        unset($htmlinfo);
        @fclose($fp);
        rename($path . ".html", $path . ".xls");

        $file = fopen($path . ".xls", "r");
        header("Content-Type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($path . ".xls"));
        header("Content-Disposition: attachment; filename=" . $filetime . ".xls");
        echo fread($file, filesize($path . ".xls"));
        fclose($file);
        @unlink($path . ".xls");
        exit;
    }

    ####################################################################################################################################
                                                                      # 请购单end
                                                                      
                                                                      # 采购单start
    ####################################################################################################################################

    # 采购单
    public function purchase_view()
    {
        return $this->fetch('purchase/purchase');
    }

    # 采购单创建数据
    public function purchase_add_view()
    {
        $template = "<a href='http://{$_SERVER['SERVER_NAME']}/template/采购单上传模版.xlsx' style='color:#1E9FFF' target='_blank'>采购单模版</a>";
        $this->assign('template', $template);
        return $this->fetch('purchase/purchase_add');
    }

    # 采购单上传数据
    public function purchase_add_upload()
    {
        require_once EXTEND_PATH."export/PHPExcel.php";
        $objPHPExcel = new \PHPExcel();

        // 获取表单上传文件
        $file = request()->file('file');
        $info = $file->validate(['ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'uploads');

        // 数据为空返回错误
        if( empty($info) ){
            return ajaxReturn(-1, '导入数据失败');
        }

        // 获取文件名
        $exclePath = $info->getSaveName();
        // 上传文件的地址
        $filename = ROOT_PATH . 'public' . DS . 'uploads'. DS . $exclePath;

        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );

        $data = $mainData = [];$amount = 0;

        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objExcel = $objReader->load($filename);
        }

        $sheet = $objExcel->getsheet(0); // 第一个sheet
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数

        $dataset=array();
        for ($row = 1; $row <= $highestRow; $row++){//行数是以第1行开始
            for ($column = 'A'; $column <= $highestColumm; $column++) {//列数是以A列开始
                $dataset[$row][] = $sheet->getCell($column.$row)->getValue();
                // echo $column.$row.":".$sheet->getCell($column.$row)->getValue()."<br />";
            }
        }


        $excel_array = $sheet->toArray(null,true,true,true);   //转换为数组格式
        $cellsArr = $sheet->getMergeCells();

        $cellsArrData = [];
        foreach ($cellsArr as $v) {
            $cellsArrData[] = $sheet->rangeToArray($v);
        }
        array_shift($excel_array);  //删除第一个数组(标题);
        array_shift($excel_array);  //删除th


        return ajaxReturn(0, 'success', $dataset);

        $filepath = DS . 'uploads'. DS . $exclePath;
    }


    ####################################################################################################################################
                                                                      # 采购单end
                                                                      
                                                                      # 采购报销单start
    ####################################################################################################################################

    # 采购报销单
    public function purchase_expense_view(){
        return $this->fetch('purchase/purchase_expense');
    }

}
