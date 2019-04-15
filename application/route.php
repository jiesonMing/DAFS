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
// 请购单
Route::get('purchase_requisition','index/purchase/purchase_requisition_view');
Route::get('purchase_requisition_items','index/purchase/purchase_requisition_items_view');
Route::get('purchase_operate','index/purchase/purchase_requisition_add_view');
// 请购单数据
Route::post('purchase_operate_upload','index/purchase/purchase_requisition_upload');
Route::post('purchase_requisition_add_data','index/purchase/purchase_requisition_add_data');
Route::get('purchase_requisition_data','index/purchase/purchase_requisition_data');
Route::get('purchase_requisition_items_data','index/purchase/purchase_requisition_items_data');
Route::put('purchase_requisition_edit','index/purchase/purchase_requisition_edit');
Route::put('purchase_requisition_items_edit','index/purchase/purchase_requisition_items_edit');
Route::delete('purchase_requisition_del','index/purchase/purchase_requisition_del');

// 采购单
Route::get('purchase','index/purchase/purchase_view');

// 采购报销单
Route::get('purchase_expense','index/purchase/purchase_expense_view');