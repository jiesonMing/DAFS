<?php
// 路由
use think\Route;

// return [
//     '__pattern__' => [
//         'name' => '\w+',
//     ],
//     '[hello]'     => [
//         ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//         ':name' => ['index/hello', ['method' => 'post']],
//     ],

// ];

/*
* 采购管理
*/
Route::get('purchase_requisition','index/purchase/purchase_requisition');
Route::get('purchase_requisition_items','index/purchase/purchase_requisition_items');
Route::get('purchase_operate','index/purchase/purchase_requisition_add');
Route::post('purchase_operate_upload','index/purchase/purchase_requisition_upload');
// 数据
Route::get('purchase_requisition_data','index/purchase/purchase_requisition_data');
Route::get('purchase_requisition_items_data','index/purchase/purchase_requisition_items_data');
Route::put('purchase_requisition_edit','index/purchase/purchase_requisition_edit');
Route::delete('purchase_requisition_del','index/purchase/purchase_requisition_del');