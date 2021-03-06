<?php

namespace app\admin\model;

use think\Model;
use think\Session;

class Admin extends Model
{


    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';



    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
    }

    /**
     * 根据ID获取名字
     * @param $id
     */
    public static function getAdminNameByID($id){
        return (new Admin)->where(['id'=>$id])->column("username");
    }

    protected function getNickNameAttr($value){
        return (string)$value;
    }

}
