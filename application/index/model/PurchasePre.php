<?php
/**
 * jieson 2019.04.05
 * 请购单主表
 */
namespace app\index\model;
use think\Model;

class PurchasePre extends Model
{
    // protected $table = '';
    protected $field = true;

    // 请购单item
    public function items()
    {
        return $this->hasMany('PurchasePreItem','ppid');
    }
}