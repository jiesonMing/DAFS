<?php
/**
 * jieson 2019.04.26
 * 采购单主表
 */
namespace app\index\model;
use think\Model;

class PurchaseModel extends Model
{
    protected $table = 'purchase';
    protected $field = true;

    // 请购单item
    public function items()
    {
        return $this->hasMany('PurchaseItems','pid');
    }
}