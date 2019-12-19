<?php

namespace app\admin\model\rfid;

use think\Model;


class AttrProductName extends Model
{

    

    

    // 表名
    protected $name = 'rfid_attr_productname';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $dateFormat = "Y-m-d H:i:s";
    // 定义时间戳字段名
    protected $deleteTime = false;


    // 追加属性
    protected $append = [
        'update_time_text'
    ];

    protected $resultSetType = 'collection';




    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
