<?php

namespace app\admin\model\rfid;

use function GuzzleHttp\Psr7\str;
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


    // 去掉最后两位防伪码
//    public function getRIdAttr($value){
//        return $value;
////        return mb_substr($value,0,mb_strlen($value)-2);
//    }

    public function getRfidAttrCountriesIdAttr($value){
        $value = (string)sprintf("%02d",$value);
        return $this->belongsTo('AttrCountries',"attr_countries_id",'c_id')->where(['c_id'=>$value])->field('name')->find()['name'];
    }

    public function getRfidAttrEsIdAttr($value){
        $value = (string)sprintf("%02d",$value);
        return $this->belongsTo('AttrEs',"attr_es_id",'es')->where(['es'=>$value])->field('name')->find()['name'];
    }

    public function getRfidAttrProductNameIdAttr($value){
        $value = (string)sprintf("%02d",$value);
        return $this->belongsTo('AttrProductName',"attr_productname_id",'p_id')->where(['p_id'=>$value])->field('name')->find()['name'];
    }

    public function getRfidAttrSpecsIdAttr($value){
        $value = (string)sprintf("%02d",$value);
        return $this->belongsTo('AttrSpecs',"attr_specs_id",'s_id')->where(['s_id'=>$value])->field('name')->find()['name'];
    }

    public function getStatusAttr($value){
        $timec = time()-strtotime($this->create_time);
        if ($timec > 31536000 && $value != 9 && $value != 1){  // 返回并设置状态为待回收
            $value = 9;
            $this->update(['id'=>$this->id,'status'=>9]);
            // 设置通知
        }
        return $value;
    }

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
        $value = $value ? $value : (isset($data['write_time']) ? $data['write_time'] : '');
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
