<?php
/**
 * Created by PhpStorm.
 * User: Suu
 * Date: 2019/12/24
 * Time: 15:24
 */

namespace app\admin\validate\rfid;


use think\Validate;

class Touser extends Validate
{
    protected $validate = __CLASS__;
    /**
     * 验证规则
     */
    protected $rule = [
        'user_name|用户姓名' => 'require|max:6|chs',
        'idcard|身份证号码' => 'require|max:18|:idCard'
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
        'add'  => ['user_name','idcard'],
    ];

    protected function idCard($value){
        $id_card = "/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/";
        return (self::regex($value,$id_card)) ? true : "身份证格式错误";
    }


}