<?php
namespace app\index\controller;
use think\Db;
use app\index\controller\Base;
use app\index\model\PurchasePre;
use app\index\model\PurchasePreItem;
use app\index\model\PurchaseModel;
use app\index\model\PurchaseItems;
use think\Config;

/*
* 采购管理的采购单
*/
class Purchase extends Base
{
    protected $datas = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->datas  = $this->req->param(true);
    }

    # 请购单
    public function purchase_requisition_view()
    {
        return $this->fetch('purchase/purchase_requisition');
    }

    # 请购单主数据
    public function purchase_requisition_data()
    {
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
        $page = isset($this->datas['page'])?$this->datas['page']:1;
        $limit = isset($this->datas['limit'])?$this->datas['limit']:Config::get('paginate.list_rows');
        $startLimit = ($page-1)*$limit;
        
        $PurchasePre = new PurchasePre();

        $count = $PurchasePre->where($where)->count();
        $list = $PurchasePre
            ->field('id,title,purchase_code,filepath,remarks,addtime,filename,pdfname,pdfpath')
            ->where($where)
            ->order('id', 'desc')
            ->limit($startLimit, $limit)
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['addtime']  = date('Y-m-d H:i:s', $v['addtime']);
            $v['pdfpath'] = "<a href='http://{$_SERVER['SERVER_NAME']}{$v['pdfpath']}' target='_blank' style='color:#1E9FFF'>{$v['pdfname']}</a>";
        }
        return ajaxReturn(0,'success', $list, $count);
    }

    # 请购单items
    public function purchase_requisition_items_view()
    {
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
        Db::startTrans();
        try {
            $PurchasePre = PurchasePre::get($this->datas['pid']);
            $PurchasePre->delete();
            $PurchasePre->items()->delete();
            
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 编辑请购单数据的时候新增一条数据
    public function purchase_requisition_items_add_data()
    {
        $items = new PurchasePreItem;
        $items->data(['ppid' => $this->datas['preId']]);
        $items->save();
        return ajaxReturn(0, 'success');
    }

    # 编辑请购单数据的时候删除一条数据
    public function purchase_requisition_items_del_data()
    {
        $items = PurchasePreItem::get($this->datas['id']);
        $items->delete();
        return ajaxReturn(0, 'success');
    }

    # 编辑请购单items数据
    public function purchase_requisition_items_edit()
    {
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

    # 上传扫描件view
    public function purchase_requisition_pdf_view()
    {
        $this->assign('pid', $this->datas['preId']);
        return $this->fetch('purchase/purchase_requisition_pdf');
    }

    # 请购单打印数据
    public function print_data($isExport = false)
    {
        header("Content-type:text/html;charset=utf-8");

        $preData   = PurchasePre::get($this->datas['preId']);
        $itemsData = PurchasePreItem::all(['ppid' => $this->datas['preId']]);

        // 组装数据
        $div = "<div>";
        $div .= "<h2 style='text-align:center;'>
        <b style='border-bottom: 1px solid black;padding: 2px;'>{$preData->title}</b> 项目工程 请 购 单</h2>";
        $div .= "<table border='1' width='800' style='border-collapse: collapse; text-align:center;font-size:24px'>";
        $div .= "<tr>";
        $div .= "<td width='60' height='58'>序号</td><td width='180'>拟采购物品名称</td><td width='100'>规格</td>
        <td width='100'>单位</td><td width='80'>数量</td><td width='100'>单件估价(元)</td>
        <td width='100'>预算总价(元)</td><td width='220'>用途</td><td width='100'>需求日期</td><td width='200'>备注</td>";
        $div .= "</tr>";

        $amount = 0;$total = 15;$count = count($itemsData);
        
        // 把需要合并的字段提取分组
        $tempArr = [];
        foreach ($itemsData as $k => $v) {
            $tempArr['use'][$v['use']][] = $v->toArray();
            $tempArr['needtime'][$v['needtime']][] = $v->toArray();
            $tempArr['remarks'][$v['remarks']][] = $v->toArray();
        }

        $newArr1 = $newArr2 = $newArr3 = [];

        $pagesize = 24 ;//每页的条数
        $newArr1 = mergeCells($tempArr['use'], 'use',$pagesize);
        $newArr2 = mergeCells($tempArr['needtime'], 'needtime',$pagesize);
        $newArr3 = mergeCells($tempArr['remarks'], 'remarks',$pagesize);

        // 合并数组
        $newArray = [];
        foreach ($newArr1 as $k => $v) {
            $newArray[] = array_merge($v,$newArr2[$k],$newArr3[$k]);
        }

        foreach ($newArray as $k => $v) {
            $kk = $k;
            $kk++;
            $div .= "<tr>";
            $div .= "<td height='56'>{$kk}</td>";
            $div .= "<td>{$v['name']}</td>";
            $div .= "<td>{$v['spec']}</td>";
            $div .= "<td>{$v['unit']}</td>";
            $div .= "<td>{$v['qty']}</td>";
            $div .= "<td>{$v['price']}</td>";
            $div .= "<td>{$v['amount']}</td>";
            if(isset($v['use']) && isset($v['use_rowspan'])) {
                $div .= "<td rowspan='{$v['use_rowspan']}'>{$v['use']}</td>";
            }

            if(isset($v['needtime']) && isset($v['needtime_rowspan'])) {
                $div .= "<td rowspan='{$v['needtime_rowspan']}'>{$v['needtime']}</td>";
            }
            if(isset($v['remarks']) && isset($v['remarks_rowspan'])) {
                $div .= "<td rowspan='{$v['remarks_rowspan']}'>{$v['remarks']}</td>";
            }
            $div .= "</tr>";
           
            // 总金额
            $amount+=$v['amount'];
            $k++;
        }

        // 金额转为大写人名币
        $chineAmount = num_to_rmb($amount);

        if ($count < $total) {
            // 空白行
            for ($i=0; $i<($total - $count); $i++) {
                $div .= "<tr><td height='58'></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
            }
        }
        
        // 占位符
        $nbsp = '&nbsp;';
        for ($i=0; $i<70; $i++ ) {
            $nbsp .= '&nbsp;';
        }

        $div .= "<tr>";
        $div .= "<td colspan='10' height='82' style='text-align:left;font-size:30px;'>备注：{$preData ->remarks}</td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='38' style='text-align:left;font-size:30px;'>预算总金额：  人 民 币 {$chineAmount}（￥{$amount}）    </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='78' style='text-align:left;font-size:30px;'>
        <p style='position:absolute;top:1'>申请人：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='78' style='text-align:left;font-size:30px;'>
        <p style='position:absolute;top:1'>项目经理审批：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='10' height='78' style='text-align:left;font-size:30px;'>
        <p style='position:absolute;top:1'>采购部门执行结果：</p><p style='position:absolute;right:10;padding-left:400'>{$nbsp}签字/日期：</p>
        </td>";
        $div .= "</tr>";

        $div .= "</table>";
        $div .= "</div>";

        if ($isExport) {
            return $div;exit;
        }
        
        vendor('mpdf.mpdf');
        $mpdf = new \mPDF('zh-CN','A4','','', 1,1,1,0);

        $mpdf->WriteHTML($div);
        $mpdf->Output(ROOT_PATH."public/template/purchase_pre.pdf");

        header("location:http://{$_SERVER['SERVER_NAME']}/template/purchase_pre.pdf");exit;
    }

    # 导出请购单数据
    public function export_data()
    {
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

    # 采购单数据
    public function purchase_data()
    {
        $where = "1 ";
        if (isset($this->datas['search'])) {
            $where.=" and concat(purchase_number,purchase_project,purchaser,phone) like '%{$this->datas['search']}%'";
        }
        if (isset($this->datas['startTime']) && !empty($this->datas['startTime'])) {
            $where.=" and addtime >=".strtotime($this->datas['startTime']);
        }
        if (isset($this->datas['endTime']) && !empty($this->datas['endTime'])) {
            $where.=" and addtime <=".strtotime($this->datas['endTime']);
        }
        $page = isset($this->datas['page'])?$this->datas['page']:1;
        $limit = isset($this->datas['limit'])?$this->datas['limit']:Config::get('paginate.list_rows');
        $startLimit = ($page-1)*$limit;
        
        $PurchaseModel = new PurchaseModel();

        $count = $PurchaseModel->where($where)->count();
        $list = $PurchaseModel
            ->field('id,purchase_number,purchase_project,purchaser,phone,remarks,addtime')
            ->where($where)
            ->order('id', 'desc')
            ->limit($startLimit, $limit)
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['addtime']  = date('Y-m-d H:i:s', $v['addtime']);
        }
        return ajaxReturn(0,'success', $list, $count);
    }

    # 采购单创建数据
    public function purchase_add_view()
    {
        $template = "<a href='http://{$_SERVER['SERVER_NAME']}/template/采购单上传模版.xlsx' style='color:#1E9FFF' target='_blank'>采购单模版</a>";
        $this->assign('template', $template);
        return $this->fetch('purchase/purchase_add');
    }

    # 采购单创建数据保存数据
    public function purchase_add_data()
    {
        $mainData = array(
            'purchase_project' => $this->datas['purchase_project'],
            'purchase_number'  => $this->datas['purchase_number'],
            'purchaser'        => $this->datas['purchaser'],
            'phone'            => $this->datas['phone'],
            'remarks'          => $this->datas['remarks'],
            'addtime'          => time()
        );
        
        $itemsData = $this->datas['itemsData'];
        foreach ($itemsData as &$v) {
            if (empty($v)) unset($v);
        }

        Db::startTrans();
        try {
            $PurchaseModel = new PurchaseModel;
            $PurchaseModel->save($mainData);

            $PurchaseModel = PurchaseModel::find($PurchaseModel->id);
            $PurchaseModel->items()->saveAll($itemsData);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 编辑采购单数据
    public function purchase_edit()
    {
        Db::startTrans();
        try {
            $PurchaseModel = PurchaseModel::get($this->datas['id']);
            $newArr = array_keys($this->datas);
            $data[$newArr[0]] = $this->datas[$newArr[0]];
            $PurchaseModel->save($data);
            
           Db::commit();
            return ajaxReturn(0, 'success', $newArr);
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 删除采购单数据
    public function purchase_del()
    {
        Db::startTrans();
        try {
            $PurchaseModel = PurchaseModel::get($this->datas['pid']);
            $PurchaseModel->delete();
            $PurchaseModel->items()->delete();
            
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 采购单items
    public function purchase_items_view()
    {
        $this->assign('pId', $this->datas['pId']);
        return $this->fetch('purchase/purchase_items'); 
    }

    # 采购单items 数据
    public function purchase_items_data()
    {
        $data = PurchaseItems::all(['pid' => $this->datas['pId']]);
        foreach ($data as $k => &$v) {
            $v['index']    = $k+1;
        }
        return ajaxReturn(0,'success', $data);
    }

    # 编辑采购单items数据
    public function purchase_items_edit()
    {
        Db::startTrans();
        try {
            $PurchaseItems = PurchaseItems::get($this->datas['id']);
            $newArr = array_keys($this->datas);
            $data[$newArr[0]] = $this->datas[$newArr[0]];
            $PurchaseItems->save($data);
            
           Db::commit();
            return ajaxReturn(0, 'success', $newArr);
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    # 编辑采购单items数据的时候新增一条数据
    public function purchase_items_add_data()
    {
        $items = new PurchaseItems;
        $items->data(['pid' => $this->datas['pId']]);
        $items->save();
        return ajaxReturn(0, 'success');
    }

    # 编辑采购单items数据的时候删除一条数据
    public function purchase_items_del_data()
    {
        $items = PurchaseItems::get($this->datas['id']);
        $items->delete();
        return ajaxReturn(0, 'success');
    }

    #采购单打印数据
    public function purchase_print_data($isExport = false)
    {
        header("Content-type:text/html;charset=utf-8");

        $purchaseData   = PurchaseModel::get($this->datas['pId']);
        $itemsData      = PurchaseItems::all(['pid' => $this->datas['pId']]);

        // 组装数据
        $div = "<div>";
        $div .= "<h2 style='text-align:center;'>
        <b style='border-bottom: 1px solid black;padding: 2px;'>釆购报销单明细表</b> </h2>";
        $div .= "<table border='1' width='800' style='border-collapse: collapse; text-align:center;font-size:24px'>";

        $div .= "<tr>";
        $div .= "<td colspan='4' height='60' style='text-align:left;'>采购项目部：{$purchaseData->purchase_project}</td>
        <td colspan='3' style='text-align:left;'>采购员：{$purchaseData->purchaser}</td>
        <td colspan='4' style='text-align:left;'>联系电话：{$purchaseData->phone}</td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td colspan='11' height='80' style='text-align:left;'>注：{$purchaseData->remarks} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$purchaseData->purchase_number}</td>";
        $div .= "</tr>";

        $div .= "<tr>";
        $div .= "<td width='150'>采购时间</td><td width='60' height='58'>序号</td>
        <td width='180'>物料明细</td><td width='120'>牌子</td><td width='100'>规格型号</td>
        <td width='100'>单位</td><td width='80'>数量</td><td width='100'>单价</td>
        <td width='100'>合计(元)</td><td width='200'>供应商</td><td width='200'>备注</td>";
        $div .= "</tr>";

        $amount = 0;$total = 15;$count = count($itemsData);
        
        // 把需要合并的字段提取分组
        $tempArr = [];
        foreach ($itemsData as $k => $v) {
            $tempArr['purchase_time'][$v['purchase_time']][] = $v->toArray();
            $tempArr['supplier'][$v['supplier']][] = $v->toArray();
            $tempArr['remarks'][$v['remarks']][] = $v->toArray();
        }

        $newArr1 = $newArr2 = $newArr3 = [];

        $pagesize = 24 ;//每页的条数
        $newArr1 = mergeCells($tempArr['purchase_time'], 'purchase_time',$pagesize);
        $newArr2 = mergeCells($tempArr['supplier'], 'supplier',$pagesize);
        $newArr3 = mergeCells($tempArr['remarks'], 'remarks',$pagesize);

        // 合并数组
        $newArray = [];
        foreach ($newArr1 as $k => $v) {
            $newArray[] = array_merge($v,$newArr2[$k],$newArr3[$k]);
        }

        foreach ($newArray as $k => $v) {
            $kk = $k;
            $kk++;
            $div .= "<tr>";
            if(isset($v['purchase_time']) && isset($v['purchase_time_rowspan'])) {
                $div .= "<td rowspan='{$v['purchase_time_rowspan']}'>{$v['purchase_time']}</td>";
            }
            $div .= "<td height='56'>{$kk}</td>";
            $div .= "<td>{$v['material']}</td>";
            $div .= "<td>{$v['brand']}</td>";
            $div .= "<td>{$v['brand']}</td>";
            $div .= "<td>{$v['spec']}</td>";
            $div .= "<td>{$v['unit']}</td>";
            $div .= "<td>{$v['price']}</td>";
            $div .= "<td>{$v['amount']}</td>";
            
            if(isset($v['supplier']) && isset($v['supplier_rowspan'])) {
                $div .= "<td rowspan='{$v['supplier_rowspan']}'>{$v['supplier']}</td>";
            }
            if(isset($v['remarks']) && isset($v['remarks_rowspan'])) {
                $div .= "<td rowspan='{$v['remarks_rowspan']}'>{$v['remarks']}</td>";
            }
            $div .= "</tr>";
           
            // 总金额
            $amount+=$v['amount'];
            $k++;
        }

        // 金额转为大写人名币
        // $chineAmount = num_to_rmb($amount);

        if ($count < $total) {
            // 空白行
            for ($i=0; $i<($total - $count); $i++) {
                $div .= "<tr><td height='58'></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
            }
        }
        
        // // 占位符
        // $nbsp = '&nbsp;';
        // for ($i=0; $i<70; $i++ ) {
        //     $nbsp .= '&nbsp;';
        // }
        $style = "style='border:0px solid white;font-size:32px;text-align:left;'";
        $div .= "<tr {$style}>";
        $div .= "<td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td>
        <td {$style}>合计：</td><td {$style}>{$amount}</td>
        <td {$style}></td><td {$style}></td>";
        $div .= "</tr>";

        $div .= "<tr {$style}>";
        $div .= "<td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td>
        <td {$style}></td>
        <td {$style}></td>";
        $div .= "</tr>";

        $div .= "<tr {$style}>";
        $div .= "<td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td>
        <td {$style}>采购组长审阅：</td>
        <td {$style}></td>";
        $div .= "</tr>";

        $div .= "<tr {$style}>";
        $div .= "<td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td><td {$style}></td>
        <td {$style}>签单日期：</td>
        <td {$style}></td>";
        $div .= "</tr>";


        $div .= "</table>";
        $div .= "</div>";

        if ($isExport) {
            return $div;exit;
        }
        // var_dump();
        vendor('mpdf.mpdf');
        $mpdf = new \mPDF('zh-CN','A4','','', 1,1,1,0);

        $mpdf->WriteHTML($div);
        $mpdf->Output(ROOT_PATH."public/template/purchase.pdf");

        header("location:http://{$_SERVER['SERVER_NAME']}/template/purchase.pdf");exit;
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
