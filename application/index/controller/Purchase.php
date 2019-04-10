<?php
namespace app\index\controller;
use think\Db;
use app\index\controller\Base;
use app\index\model\PurchasePre;
use app\index\model\PurchasePreItem;

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
            ->field('id,purchase_code,filepath,remarks,addtime,filename')
            ->where($where)
            ->order('id', 'desc')
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['addtime']  = date('Y-m-d H:i:s', $v['addtime']);
            $v['filepath'] = "<a href='http://{$_SERVER['SERVER_NAME']}{$v['filepath']}' target='_blank'>{$v['filename']}</a>";
        }
        return ajaxReturn(0,'success', $list);
    }

    # 请购单items
    public function purchase_requisition_items()
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
    public function purchase_requisition_add()
    {
        return $this->fetch('purchase/purchase_requisition_add');
    }

    # 编辑请购单数据
    public function purchase_requisition_edit()
    {
        $this->datas  = $this->req->param(true);

        Db::startTrans();
        try {
            $PurchasePre = PurchasePre::get($this->datas['pid']);
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

    # 请购单数据上传,若文件为pdf,则只需保存文件
    public function purchase_requisition_upload()
    {
        // \think\Loader::import('export.PHPExcel.PHPExcel');
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
                $data[$k]["remarks"]  = $v[9]; //备注

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

        // 把数据插入数据表
        Db::startTrans();
        try {
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
            Db::rollback();
            return ajaxReturn(-1, 'failed');

        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

}
