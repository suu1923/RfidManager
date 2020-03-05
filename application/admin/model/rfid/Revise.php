<?php

namespace app\admin\model\rfid;

use app\admin\model\Admin;
use think\Model;


class Revise extends Model
{
    // 表名
    protected $name = 'rfid_revise';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    protected $dateFormat = "Y-m-d H:i:s";
    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'success_text',
        'option_text',
        'read_text',
        'create_time_text',
        'option_time_text',
        'status_text'
    ];

    public function getRfidIdAttr($value){
        return (new Rfid)->where(["id"=>$value])->column("r_id");
    }

    public function getSubmissionIdAttr($value){
        return Admin::getAdminNameByID($value);
    }

    public function getAuditorIdAttr($value){
        return Admin::getAdminNameByID($value);
    }

    public function getAttrAttr($value){
        $value = json_decode($value,true);
        // 修改名称
        foreach ($value as $key => &$val){
            $val = $this->transform($key,$val);
        }
        $res = implode("",$value);
        return $res;
    }

    /**
     * 返回相应的数据
     * @param $key
     * @param $val
     * @throws \think\exception\DbException
     */
    private function transform($key,$val){
        $result = explode("=>",$val);
        if (count($result) != 2) return false;
        $after = $result[0];
        $before = $result[1];
        $text = "  %s由<b style='color: #000000'>%s</b>更改为<b style='color: red'>%s</b><br />";
        // 查询
        if ($key == "rfid_attr_es_id") {
            $after = $this->belongsTo('AttrEs',"attr_es_id",'es')->where(['es'=>$after])->field('name')->find()['name'];
            $before = $this->belongsTo('AttrEs',"attr_es_id",'es')->where(['es'=>$before])->field('name')->find()['name'];
            return sprintf($text,__("es_id"),$after,$before);
        }
        if ($key == "rfid_attr_specs_id") {
            $after = $this->belongsTo('AttrSpecs',"attr_specs_id",'s_id')->where(['s_id'=>$after])->field('name')->find()['name'];
            $before = $this->belongsTo('AttrSpecs',"attr_specs_id",'s_id')->where(['s_id'=>$before])->field('name')->find()['name'];
            return sprintf($text,__("es_id"),$after."mm",$before."mm");
        }
        if ($key == "rfid_attr_productname_id") {
            $after = $this->belongsTo('AttrProductName',"attr_productname_id",'p_id')->where(['p_id'=>$after])->field('name')->find()['name'];
            $before = $this->belongsTo('AttrProductName',"attr_productname_id",'p_id')->where(['p_id'=>$before])->field('name')->find()['name'];
            return sprintf($text,__("es_id"),$after,$before);
        }
        if ($key == "rfid_attr_countries_id") {
            $after = $this->belongsTo('AttrCountries',"attr_countries_id",'c_id')->where(['c_id'=>$after])->field('name')->find()['name'];
            $before = $this->belongsTo('AttrCountries',"attr_countries_id",'c_id')->where(['c_id'=>$before])->field('name')->find()['name'];
            return sprintf($text,__("es_id"),$after,$before);
        }
        if ($key == "batch_number"){
            $after = $this->formatDate($after);
            $before = $this->formatDate($before);
            return sprintf($text,__("batch_number"),$after,$before);
        }
        if ($key == "serial_number"){
            return sprintf($text,__("serial_number"),$after,$before);
        }
    }

    /**
     * 格式化日期，返回XX年XX月格式
     * @param $date
     * @return mixed
     */
    private function formatDate($date){
        $result = "20".substr($date,0,2)."年".substr($date,2,2)."月";
        return $result;
    }

    public function getSuccessList()
    {
        return ['0' => __('Success 0'), '1' => __('Success 1')];
    }

    public function getOptionList()
    {
        return ['0' => __('Option 0'), '1' => __('Option 1')];
    }

    public function getReadList()
    {
        return ['0' => __('Read 0'), '1' => __('Read 1')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getSuccessTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['success']) ? $data['success'] : '');
        $list = $this->getSuccessList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOptionTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['option']) ? $data['option'] : '');
        $list = $this->getOptionList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getReadTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['read']) ? $data['read'] : '');
        $list = $this->getReadList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCreateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['create_time']) ? $data['create_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getOptionTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['option_time']) ? $data['option_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCreateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setOptionTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
