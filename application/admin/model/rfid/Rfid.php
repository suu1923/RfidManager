<?php

namespace app\admin\model\rfid;

use app\admin\model\Admin;
use think\Model;


class Rfid extends Model
{
    // 表名
    protected $name = 'rfid';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $dateFormat = "Y-m-d H:i:s";
    // 定义时间戳字段名
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
        'update_time_text',
        'delete_time_text',
        'write_time_text'
    ];
    

    public function getRIdAttr($value){
        return mb_substr($value,0,mb_strlen($value)-2);
    }


    public function getCreateUserIdAttr($value){
        $username = Admin::get($value);
        return $username ? $username->username : "未知";
    }

//    public function getIsWriteAttr($value){
//        $status = [0=>"未写入",1=>"已写入"];
//        return $status[$value];
//    }
//    public function getStatusAttr($value){
//        $status = [0=>"正常",1=>"已过期",2=>"损坏",3=>"异常"];
//        return $status[$value];
//    }
    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDeleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delete_time']) ? $data['delete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function getWriteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delete_time']) ? $data['delete_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setDeleteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
    protected function setWriteTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

}
