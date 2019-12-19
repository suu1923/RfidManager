<?php

namespace app\admin\validate\rfid;

use think\Validate;

class AttrProductName extends Validate
{
    protected $validate = __CLASS__;

    /**
     * 验证规则
     */
    protected $rule = [
        'p_id|产品编号' => 'require|max:2|number|isRepeat:',
        'name|产品名' => 'require|max:10|chs|isRepeat:'
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
        'add'  => ['p_id','name'],
        'edit' => ['name'],
    ];
    
}
