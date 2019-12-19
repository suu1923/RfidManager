<?php

namespace app\admin\validate\rfid;

use think\Validate;

class AttrEs extends Validate
{
    protected $validate = __CLASS__;
    /**
     * 验证规则
     */
    protected $rule = [
        'es|执行标准代号' => 'require|max:2|isRepeat:',
        'name|执行标准说明' => 'require|max:50|isRepeat:'
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
        'add'  => ['es','name'],
        'edit' => ['es'],
    ];
    
}
