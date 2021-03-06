<?php
namespace app\index\controller;
use think\Db;
use app\index\controller\Base;
use think\Config;

/*
* 车辆管理
* jieson 2019.06.06
*/
class Car extends Base
{
    protected $datas = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->datas  = $this->req->param(true);
    }

    // 车辆维修view
    public function car_view()
    {
        return $this->fetch('car/car');
    }
    
    // 车辆维修详情
    public function car_detail_view()
    {
        $carid = $this->datas['carid'];
        $res = Db::name('car_number')->where('id', $carid)->find();
        $this->assign('car_number', $res['car_number']);
        $this->assign('carid', $res['id']);
        return $this->fetch('car/car_detail');
    }

    // 车辆维修数据
    public function car_data()
    {
        $where = '1 ';
        if (isset($this->datas['search'])) {
            $where.=" and concat(car_number,maintenance_situation) like '%{$this->datas['search']}%'";
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

        $count = Db::name('car_maintenance')->where($where)->count();
        $list = Db::name('car_maintenance')
            ->field('*')
            ->where($where)
            ->order('id', 'desc')
            ->limit($startLimit, $limit)
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['maintenance_time'] = date('Y-m-d', strtotime($v['maintenance_time']));
            $v['nianjian_time'] = date('Y-m-d', strtotime($v['nianjian_time']));
            $v['next_nianjian_time'] = date('Y-m-d', strtotime($v['next_nianjian_time']));
            $v['insurance_time'] = date('Y-m-d', strtotime($v['insurance_time']));
            $v['next_insurance_time'] = date('Y-m-d', strtotime($v['next_insurance_time']));
            $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        return ajaxReturn(0,'success', $list, $count);
    }

    // 添加车辆维修view
    public function car_add_view()
    {
        $carid = $this->datas['carid'];
        $res = Db::name('car_number')->where('id', $carid)->find();
        $this->assign('car_number', $res['car_number']);
        $this->assign('carid', $res['id']);
        return $this->fetch('car/car_add');
    }

    public function car_number()
    {
        $where = '1 ';
        if (isset($this->datas['search'])) {
            $where.=" and concat(car_number,car_area) like '%{$this->datas['search']}%'";
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

        $count = Db::name('car_number')->where($where)->count();
        $list = Db::name('car_number')
            ->field('*')
            ->where($where)
            ->order('id', 'desc')
            ->limit($startLimit, $limit)
            ->select();
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        return ajaxReturn(0,'success', $list, $count);
    }

    // 保存添加车牌号
    public function car_add()
    {
        $bool = Db::name('car_number')->where('car_number', $this->datas['car_number'])->find();
        if ($bool) {
            return ajaxReturn(-1, '该车牌号：'.$this->datas['car_number'].' 已存在');
        }
        $data = array(
            'car_number'            => $this->datas['car_number'],
            'car_area'              => $this->datas['car_area'],
            'addtime'               => time()
        );
        

        Db::startTrans();
        try {
            Db::name('car_number')->insert($data);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 保存添加的数据
    public function car_add_data()
    {
        $data = array(
            'car_id'                => $this->datas['car_id'],
            'mileage'               => $this->datas['mileage'],
            'amount'                => $this->datas['amount'],
            'nianjian_time'         => $this->datas['nianjian_time'],
            'next_nianjian_time'    => $this->datas['next_nianjian_time'],
            'insurance_time'        => $this->datas['insurance_time'],
            'next_insurance_time'   => $this->datas['next_insurance_time'],
            'maintenance_situation' => $this->datas['maintenance_situation'],
            'maintenance_time'      => $this->datas['maintenance_time'],
            'remarks'               => $this->datas['remarks'],
            'addtime'               => time()
        );

        Db::startTrans();
        try {
            Db::name('car_maintenance')->insert($data);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }
    
    // 修改数据
    public function car_edit()
    {
        Db::startTrans();
        try {
            Db::name('car_maintenance')->where('id', $this->datas['id'])->update($this->datas);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 车辆删除
    public function car_del()
    {
        Db::startTrans();
        try {
            Db::name('car_number')->where('id',  $this->datas['id'])->delete();
            Db::name('car_maintenance')->where('car_id',  $this->datas['id'])->delete();

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 车辆维修详情删除
    public function car_detail_del()
    {
        Db::startTrans();
        try {
            Db::name('car_maintenance')->where('id',  $this->datas['id'])->delete();
            
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 车辆用油情况view
    public function caroil_view()
    {
        return $this->fetch('car/caroil');
    }
    
    // 车辆用油情况数据
    public function caroil_data()
    {
        $where = '1 ';
        if (isset($this->datas['search'])) {
            $where.=" and concat(car_number,caroil_number,user) like '%{$this->datas['search']}%'";
        }
        if (isset($this->datas['startTime']) && !empty($this->datas['startTime'])) {
            $where.=" and oil_add_time like '%".$this->datas['startTime']."%'";
        }

        $page = isset($this->datas['page'])?$this->datas['page']:1;
        $limit = isset($this->datas['limit'])?$this->datas['limit']:Config::get('paginate.list_rows');
        $startLimit = ($page-1)*$limit;

        $count = Db::name('car_oil')->where($where)->count();
        $list = Db::name('car_oil')
            ->field('*')
            ->where($where)
            ->order('id', 'desc')
            ->limit($startLimit, $limit)
            ->select();

        $oil_amount_arr = Db::name('car_oil')->where($where)->field('caroil_number,sum(oil_amount) as amount')->group('caroil_number')->select();
        
        foreach ($list as $k => &$v) {
            $v['index']    = $k+1;
            $v['oil_add_time'] = date('Y-m-d', strtotime($v['oil_add_time']));
            $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);

            foreach ($oil_amount_arr as $o) {
                if ($o['caroil_number'] = $v['caroil_number']) {
                    $v['amount'] = $o['amount'];
                }
            }
        }
        return ajaxReturn(0,'success', $list, $count);
    }

    // 车辆用油情况add_view
    public function caroil_add_view()
    {
        return $this->fetch('car/caroil_add');
    }

    public function caroil_add_data()
    {
        $data = array(
            'caroil_number' => $this->datas['caroil_number'],
            'car_number'    => $this->datas['car_number'],
            'user'          => $this->datas['user'],
            'oil_add_time'  => $this->datas['oil_add_time'],
            'oil_amount'    => $this->datas['oil_amount'],
            // 'amount'        => $this->datas['amount'],
            'addtime'       => time()
        );

        Db::startTrans();
        try {
            Db::name('car_oil')->insert($data);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 车辆用油情况编辑
    public function caroil_edit()
    {
        Db::startTrans();
        try {
            Db::name('car_oil')->where('id', $this->datas['id'])->update($this->datas);

            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }

    // 车辆用油删除
    public function caroil_del_data()
    {
        Db::startTrans();
        try {
            Db::name('car_oil')->where('id',  $this->datas['id'])->delete();
            
            Db::commit();
            return ajaxReturn(0, 'success');
        } catch (Exception $e) {
            Db::rollback();
            return ajaxReturn(-1, $e->getMessage());
        }
    }


}