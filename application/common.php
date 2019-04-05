<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function ajaxReturn($code, $msg,  $data=[])
{
    header("Content-type:text/html;charset=utf-8");
    $restult = array(
        'code'  => $code,
        'msg'   => $msg,
        'count' => count($data),
        'data'  => $data,
    );
    echo json_encode($restult);die;
}

// 去掉字符中的,并且转为数字
function changeStr($str)
{
    return (double)str_replace(',', '', $str);
}

// 截取两个字符之间的字符串
function get_between($input, $start, $end) {
    $substr = substr($input, strlen($start)+strpos($input, $start),(strlen($input) - strpos($input, $end))*(-1));
    return $substr;
}

