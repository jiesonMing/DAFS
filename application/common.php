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

// 1将金额数字转化为中文大写
function toChineseNumber($money){
    $money = round($money,2);
    $cnynums = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖"); 
    $cnyunits = array("圆","角","分");
    $cnygrees = array("拾","佰","仟","万","拾","佰","仟","亿"); 
    list($int,$dec) = explode(".",$money,2);
    $dec = array_filter(array($dec[1],$dec[0])); 
    $ret = array_merge($dec,array(implode("",cnyMapUnit(str_split($int),$cnygrees)),"")); 
    $ret = implode("",array_reverse(cnyMapUnit($ret,$cnyunits))); 
    return str_replace(array_keys($cnynums),$cnynums,$ret); 
}
function cnyMapUnit($list,$units) { 
    $ul=count($units); 
    $xs=array(); 
    foreach (array_reverse($list) as $x) { 
        $l=count($xs); 
        if ($x!="0" || !($l%4)) {
            $n=($x=='0'?'':$x).($units[($l-1)%$ul]); 
        } else {
            $n=is_numeric($xs[0][0])?$x:''; 
        } 
        array_unshift($xs,$n); 
   } 
   return $xs; 
}
// 2将金额数字转化为中文大写
function convert_2_cn($num) {
    $convert_cn = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
    $repair_number = array('零仟零佰零拾零','万万','零仟','零佰','零拾');
    $unit_cn = array("拾","佰","仟","万","亿");
    $exp_cn = array("","万","亿");
    $max_len = 12;
    
    $len = strlen($num);
    if($len > $max_len) {
        return 'outnumber';
    }
    $num = str_pad($num,12,'-',STR_PAD_LEFT);
    $exp_num = array();
    $k = 0;
    for($i=12;$i>0;$i--){
        if($i%4 == 0) {
            $k++;
        }
        $exp_num[$k][] = substr($num,$i-1,1);
    }
    $str = '';
    foreach($exp_num as $key=>$nums) {
        if(array_sum($nums)){
            $str = array_shift($exp_cn) . $str;
        }
        foreach($nums as $nk=>$nv) {
            if($nv == '-'){continue;}
            if($nk == 0) {
                $str = $convert_cn[$nv] . $str;
            } else {
                $str = $convert_cn[$nv].$unit_cn[$nk-1] . $str;
            }
        }
    }
    $str = str_replace($repair_number,array('万','亿','-'),$str);
    $str = preg_replace("/-{2,}/","",$str);
    $str = str_replace(array('零','-'),array('','零'),$str);

    $str = preg_replace("/([\x{4e00}-\x{9fa5}])/u", "\\1 ", $str);

    return $str;
}

