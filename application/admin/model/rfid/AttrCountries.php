<?php

namespace app\admin\model\rfid;

use think\Model;


class AttrCountries extends Model
{

    

    

    // 表名
    protected $name = 'rfid_attr_countries';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

//    protected $dateFormat =

    // 定义时间戳字段名
    protected $updateTime = false;
    protected $deleteTime = false;
    protected $dateFormat = "Y-m-d H:i:s";
    // 追加属性
    protected $append = [

    ];
    

    







}
