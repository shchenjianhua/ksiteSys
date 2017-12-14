<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/13
 * Time: 23:32
 * Desc: -用户服务类
 */


namespace Apps\Services;

use Lkk\Helpers\ArrayHelper;
use Lkk\Helpers\ValidateHelper;
use Apps\Models\UserBase;

class UserService extends ServiceBase {

    //用户名最小长度
    const USER_NAME_MINLEN = 5;
    //用户名最大长度
    const USER_NAME_MAXLEN = 30;
    //邮箱最小长度
    const USER_MAIL_MINLEN = 5;
    //邮箱最大长度
    const USER_MAIL_MAXLEN = 30;
    //用户密码最小长度
    const USER_PWD_MINLEN = 5;
    //用户密码最大长度
    const USER_PWD_MAXLEN = 32;

    //保留用户名,禁止注册
    public static $holdNames = ['root','admin','test','manage','system','super','vip','guanli','guest'];
    //保留昵称,禁止使用
    public static $holdNicks = ['管理','测试','系统','游客','后台'];


    /**
     * 构造函数
     */
    public function __construct($vars=[]) {
        parent::__construct($vars);

    }


    /**
     * 验证用户名的字符
     * @param string $str
     * @return int
     */
    public static function validateUsernameChar(string $str) {
        return preg_match('/^[_0-9a-z]+$/i',$str);
    }


    /**
     * 验证用户密码的字符
     * @param string $str
     * @return int
     */
    public static function validateUserpwdChar(string $str) {
        return preg_match('/^[\\~!@#$%^&*()-_=+|{}\[\],.?\/:;\'\"\d\w]+$/i',$str);
    }


    /**
     * 是否包含保留名
     * @param string $str
     * @return bool
     */
    public static function hasHoldName(string $str) {
        if(in_array($str, self::$holdNames)) {
            return true;
        }else{
            foreach (self::$holdNames as $holdName) {
                if(stripos($holdName, $str)===0) { //禁止以保留名为前缀
                    return true;
                    break;
                }
            }
        }
        
        return false;
    }


    /**
     * 是否包含保留昵称
     * @param string $str
     * @return bool
     */
    public static function hasHoldNick(string $str) {
        if(in_array($str, self::$holdNicks)) {
            return true;
        }else{
            foreach (self::$holdNicks as $holdNick) {
                if(mb_stripos($holdNick, $str)===0) { //禁止以保留昵称为前缀
                    return true;
                    break;
                }
            }
        }

        return false;
    }


    /**
     * 检查用户名是否已存在
     * @param string $str 用户名
     * @param int $uid 对比UID
     * @return bool
     */
    public static function isUsernameExist(string $str, $uid=0) {
        $user = UserBase::getRow(['username'=>$str]);

        return $user ? ($uid ? ($uid==$user->uid ? false : true) : true ) : false;
    }


    /**
     * 检查邮箱是否已存在
     * @param string $str
     * @param int $uid
     * @return bool
     */
    public static function isEmailExist(string $str, $uid=0) {
        $user = UserBase::getRow(['email'=>$str]);

        return $user ? ($uid ? ($uid==$user->uid ? false : true) : true ) : false;
    }


    /**
     * 验证用户名是否合法
     * @param string $str
     * @return bool
     */
    public function validateUsername(string $str) {
        $res = false;
        $len = strlen($str);
        if(empty($str)) {
            $this->setError('用户名不能为空');
            return $res;
        }elseif (!self::validateUsernameChar($str)) {
            $this->setError('用户名只能使用英文、数字和下划线');
            return $res;
        }elseif ($len < self::USER_NAME_MINLEN) {
            $this->setError('用户名至少'.self::USER_NAME_MINLEN.'个字符');
            return $res;
        }elseif ($len > self::USER_NAME_MAXLEN) {
            $this->setError('用户名至多'.self::USER_NAME_MAXLEN.'个字符');
            return $res;
        }

        return true;
    }


    /**
     * 验证用户密码是否合法
     * @param string $str
     * @return bool
     */
    public function validateUserpwd(string $str) {
        $res = false;
        $len = strlen($str);
        if(empty($str)) {
            $this->setError('密码不能为空');
            return $res;
        }elseif (!self::validateUsernameChar($str)) {
            $this->setError('密码只能使用英文、数字和特殊字符');
            return $res;
        }elseif ($len < self::USER_PWD_MINLEN) {
            $this->setError('密码至少'.self::USER_PWD_MINLEN.'个字符');
            return $res;
        }elseif ($len > self::USER_PWD_MAXLEN) {
            $this->setError('密码至多'.self::USER_PWD_MAXLEN.'个字符');
            return $res;
        }

        return true;
    }


    /**
     * 验证邮箱是否合法
     * @param string $str
     * @return bool
     */
    public function validateEmail(string $str) {
        $res = false;
        $len = strlen($str);
        if(empty($str)) {
            $this->setError('邮箱不能为空');
            return $res;
        }elseif (!ValidateHelper::isEmail($str)) {
            $this->setError('无效的邮箱');
            return $res;
        }elseif ($len < self::USER_MAIL_MINLEN) {
            $this->setError('邮箱至少'.self::USER_MAIL_MINLEN.'个字符');
            return $res;
        }elseif ($len > self::USER_MAIL_MAXLEN) {
            $this->setError('邮箱至多'.self::USER_MAIL_MAXLEN.'个字符');
            return $res;
        }

        return true;
    }


    /**
     * 验证手机号是否合法
     * @param string $str
     * @return bool
     */
    public function validateMobile(string $str) {
        $res = false;
        if(empty($str)) {
            $this->setError('手机号不能为空');
            return $res;
        }elseif(!ValidateHelper::isMobile($str)) {
            $this->setError('无效的手机号');
            return $res;
        }

        return true;
    }


    /**
     * 验证身份证号是否合法
     * @param string $str
     * @return bool
     */
    public function validateIdentityNo(string $str) {
        $res = false;
        if(empty($str)) {
            $this->setError('身份证号不能为空');
            return $res;
        }elseif(!ValidateHelper::isCreditNo($str)) {
            $this->setError('无效的身份证号');
            return $res;
        }

        return true;
    }


    /**
     * 检查用户是否存在
     * @param string $str
     * @param int $uid
     * @return bool
     */
    public function checkUsernameExist(string $str, $uid=0) {
        $chk = self::isUsernameExist($str, $uid);
        if($chk) $this->setError('该用户名已存在');

        return !$chk;
    }


    /**
     * 检查邮箱是否存在
     * @param string $str
     * @param int $uid
     * @return bool
     */
    public function checkEmailExist(string $str, $uid=0) {
        $chk = self::isEmailExist($str, $uid);
        if($chk) $this->setError('该邮箱已存在');

        return !$chk;
    }


    /**
     * 检查是否保留的名字
     * @param string $str
     * @return bool
     */
    public function checkIsHoldName(string $str) {
        $chk = self::hasHoldName($str);
        if($chk) $this->setError('该用户名已存在');

        return !$chk;
    }


    /**
     * 检查是否保留的昵称
     * @param string $str
     * @return bool
     */
    public function checkIsHoldNick(string $str) {
        $chk = self::hasHoldNick($str);
        if($chk) $this->setError('该用户名已存在');

        return !$chk;
    }












}