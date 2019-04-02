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
