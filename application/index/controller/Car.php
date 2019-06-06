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
            $v['car_time'] = date('Y-m-d', strtotime($v['car_time']));
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
        return $this->fetch('car/car_add');
    }

    // 保存添加的数据
    public function car_add_data()
    {
        $data = array(
            'car_number'            => $this->datas['car_number'],
            'mileage'               => $this->datas['mileage'],
            'car_time'              => $this->datas['car_time'],
            'amount'                => $this->datas['amount'],
            'nianjian_time'         => $this->datas['nianjian_time'],
            'next_nianjian_time'    => $this->datas['next_nianjian_time'],
            'insurance_time'        => $this->datas['insurance_time'],
            'next_insurance_time'   => $this->datas['next_insurance_time'],
            'maintenance_situation' => $this->datas['maintenance_situation'],
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
}