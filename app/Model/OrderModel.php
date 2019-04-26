<?php

namespace App\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'shop_order';
    public $timestamps = false;

    // 生成订单编号
    public static function generateOrderSN($uid)
    {
        $order_sn = '1809a_'. date("ymdH").'_';
        $str = time() . rand(1111,9999) . Str::random(16);
        $order_sn .=  substr(md5($str),5,16);
        return $order_sn;
    }
}
