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

function ajaxReturn($code, $msg,  $data=[], $count=null)
{
    header("Content-type:text/html;charset=utf-8");
    $restult = array(
        'code'  => $code,
        'msg'   => $msg,
        'count' => $count?$count:count($data),
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

function num_to_rmb($num){
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    //精确到分后面就不要了，所以只留两个小数位
    $num = round($num, 2); 
    //将数字转化为整数
    $num = $num * 100;
    if (strlen($num) > 10) {
        return "金额太大，请检查";
    } 
    $i = 0;
    $c = "";
    while (1) {
        if ($i == 0) {
            //获取最后一位数字
            $n = substr($num, strlen($num)-1, 1);
        } else {
            $n = $num % 10;
        }
        //每次将最后一位数字转化为中文
        $p1 = substr($c1, 3 * $n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
        $i = $i + 1;
        //去掉数字最后一位了
        $num = $num / 10;
        $num = (int)$num;
        //结束循环
        if ($num == 0) {
            break;
        } 
    }
    $j = 0;
    $slen = strlen($c);
    while ($j < $slen) {
        //utf8一个汉字相当3个字符
        $m = substr($c, $j, 6);
        //处理数字中很多0的情况,每次循环去掉一个汉字“零”
        if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
            $left = substr($c, 0, $j);
            $right = substr($c, $j + 3);
            $c = $left . $right;
            $j = $j-3;
            $slen = $slen-3;
        } 
        $j = $j + 3;
    } 
    //这个是为了去掉类似23.0中最后一个“零”字
    if (substr($c, strlen($c)-3, 3) == '零') {
        $c = substr($c, 0, strlen($c)-3);
    }
    //将处理的汉字加上“整”
    if (empty($c)) {
        $c = "零元整";
    }else{
        $c.= "整";
    }

    $str = preg_replace("/([\x{4e00}-\x{9fa5}])/u", "\\1 ", $c);
    return $str;
}

// 请购单打印数据，按字段合并
function mergeCells($data,$field,$pagesize)
{
    $res = [];
    foreach ($data as $k => $arr) {
        $rowslpan = count($arr);
        $page = ceil($rowslpan/$pagesize);// 有多少页
        foreach ($arr as $i => &$v) {
            if ($rowslpan>$pagesize) {
                // 获取索引数组
                $nArr = [];
                for ($n = 0;$n<$page;$n++) {
                    $nArr[] = $n*$pagesize;
                }
                if (in_array($i, $nArr)) {
                    $nArr[] = $rowslpan;
                    $keyArr = array_keys($nArr, $i);
                    $v[$field.'_rowspan'] = $nArr[$keyArr[0]+1]-$nArr[$keyArr[0]];
                } else {
                    unset($v[$field]);
                }
            } else {
                if ($i == 0) {
                    $v[$field.'_rowspan'] = $rowslpan;
                } else {
                    unset($v[$field]);
                }
            }
            $res[] = $v;
        }
    }
    return $res;
}

