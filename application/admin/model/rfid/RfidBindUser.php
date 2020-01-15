<?php
/**
 * Created by PhpStorm.
 * User: Suu
 * Date: 2019/12/25
 * Time: 15:43
 */

namespace app\admin\model\rfid;


use think\Model;

class RfidBindUser extends Model
{
    // 表名
    protected $name = 'rfid_bind_user';


    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $dateFormat = "Y-m-d H:i:s";
    // 定义时间戳字段名
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'create_time_text',
    ];



    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }
}