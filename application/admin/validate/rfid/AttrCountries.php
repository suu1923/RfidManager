<?php

namespace app\admin\validate\rfid;

use app\admin\validate\BaseValidate;
use think\Db;
use think\Validate;

class AttrCountries extends Validate
{
    protected $validate = __CLASS__;
    /**
     * 验证规则
     */
    protected $rule = [
        'c_id|省行政区编号' => 'require|max:2|number|isRepeat:',
        'name|省行政区名称' => 'require|max:10|chs|isRepeat:'
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
        'add'  => ['c_id','name'],
        'edit' => ['c_id','name']
    ];

}
