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
Route::get('purchase_requisition_pdf','index/purchase/purchase_requisition_pdf_view');
// 请购单数据
Route::post('purchase_operate_upload','index/purchase/purchase_requisition_upload');
Route::post('purchase_requisition_add_data','index/purchase/purchase_requisition_add_data');
Route::get('purchase_requisition_data','index/purchase/purchase_requisition_data');
Route::get('purchase_requisition_items_data','index/purchase/purchase_requisition_items_data');
Route::put('purchase_requisition_edit','index/purchase/purchase_requisition_edit');
Route::post('purchase_requisition_items_add_data','index/purchase/purchase_requisition_items_add_data');
Route::delete('purchase_requisition_items_del_data','index/purchase/purchase_requisition_items_del_data');
Route::put('purchase_requisition_items_edit','index/purchase/purchase_requisition_items_edit');
Route::delete('purchase_requisition_del','index/purchase/purchase_requisition_del');
Route::get('purchase_print_data','index/purchase/print_data');
Route::get('purchase_export_data','index/purchase/export_data');
// 采购单
Route::get('purchase','index/purchase/purchase_view');
Route::get('purchase_add','index/purchase/purchase_add_view');
Route::get('purchase_items','index/purchase/purchase_items_view');
Route::get('purchase_pdf','index/purchase/purchase_pdf_view');
// 采购单数据
Route::get('purchase_data','index/purchase/purchase_data');
Route::post('purchase_add_data','index/purchase/purchase_add_data');
Route::put('purchase_edit','index/purchase/purchase_edit');
Route::put('purchase_items_edit','index/purchase/purchase_items_edit');
Route::delete('purchase_del','index/purchase/purchase_del');
Route::post('purchase_upload','index/purchase/purchase_upload_data');
Route::get('purchase_items_data','index/purchase/purchase_items_data');
Route::post('purchase_items_add_data','index/purchase/purchase_items_add_data');
Route::delete('purchase_items_del_data','index/purchase/purchase_items_del_data');
Route::get('purchase_print','index/purchase/purchase_print_data');
// 采购报销单
Route::get('purchase_expense','index/purchase/purchase_expense_view');


/*
* 仓库管理
*/
// 入库
Route::get('inwarehouse','index/warehouse/inwarehouse_view');


/*
* 车辆管理
*/
// 车辆维修
Route::get('car','index/car/car_view');
Route::get('car_detail','index/car/car_detail_view');
Route::get('car_data','index/car/car_data');
Route::get('car_number','index/car/car_number');
Route::put('car_edit','index/car/car_edit');
Route::get('car_add','index/car/car_add_view');
Route::post('car_add','index/car/car_add');
Route::post('car_add_data','index/car/car_add_data');
Route::delete('car_del','index/car/car_del');
Route::delete('car_detail_del','index/car/car_detail_del');

// 车辆用油情况
Route::get('caroil','index/car/caroil_view');
Route::get('caroil_data','index/car/caroil_data');
Route::get('caroil_add','index/car/caroil_add_view');
Route::post('caroil_add_data','index/car/caroil_add_data');
Route::put('caroil_edit','index/car/caroil_edit');
Route::delete('caroil_del','index/car/caroil_del');
