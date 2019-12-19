<?php

namespace app\admin\validate\rfid;

use think\Validate;

class AttrSpecs extends Validate
{
    protected $validate = __CLASS__;
    /**
     * 验证规则
     */
    protected $rule = [
        's_id|规格编号' => 'require|max:3|number|isRepeat:',
        'name|产品规格' => 'require|max:3|number|isRepeat:|divideFive:'
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['s_id','name'],
        'edit' => ['name'],
    ];

    protected function divideFive($value){
        return ($value % 5 == 0 && $value >= 10) ? true : "必须是5的倍数且大于10";
    }

}
