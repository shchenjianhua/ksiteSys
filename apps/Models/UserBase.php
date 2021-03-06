<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/4/23
 * Time: 19:11
 * Desc: -数据表模型 user_base 用户基本表
 */


namespace Apps\Models;

class UserBase extends BaseModel {

    const USER_TYPE_MEMBER = 0; //用户类P型-普通用户
    const USER_TYPE_TESTER = 1; //用户类P型-测试用户
    const USER_TYPE_ADMNER = 2; //用户类P型-后台用户
    const USER_TYPE_APIER  = 3; //用户类P型-接口用户

    //默认字段
    public static $defaultFields = 'uid,site_id,status,mobile_status,email_status,type,mobile,email,username,password,create_time,update_time';
    //连表管理员字段
    public static $joinAdmnFields = 'uid AS adm_uid,level AS adm_level,status AS adm_status,logins AS adm_logins,login_fails AS adm_login_fails,last_login_ip AS adm_last_login_ip,last_login_time AS adm_last_login_time';



    public function initialize() {
        parent::initialize();
    }


    /**
     * 获取单个连表管理员字段
     * @return array
     */
    public static function getJoinAdmnFields() {
        $fieldsU = self::makeAliaFields(self::$defaultFields, self::class);
        $fieldsA = self::makeAliaFields(self::$joinAdmnFields, 'a');
        $fields = array_merge($fieldsU, $fieldsA);

        return $fields;
    }


    /**
     * 获取状态数组
     * @return array
     */
    public static function getStatusArr() {
        return [
            '10' => '正常',
            '-1' => '禁登录',
            '0' => '待激活',
            '1' => '禁发布',
            '2' => '禁评论',
        ];
    }


    /**
     * 获取手机号状态数组
     * @return array
     */
    public static function getMobileStatusArr( ){
        return [
            '-1' => '已解绑',
            '0' => '未验证',
            '1' => '已验证',
        ];
    }


    /**
     * 获取邮箱状态数组
     * @return array
     */
    public static function getEmailStatusArr( ){
        return [
            '-1' => '已解绑',
            '0' => '未验证',
            '1' => '已验证',
        ];
    }


    /**
     * 获取用户类型数组
     * @return array
     */
    public static function getTypesArr() {
        return [
            '0' => '普通用户',
            '1' => '测试用户',
            '2' => '后台用户',
            '3' => '接口用户',
        ];
    }


    /**
     * 根据Username获取用户基本信息
     * @param string $str
     * @return \Phalcon\Mvc\Model|bool
     */
    public static function getInfoByUsername(string $str='') {
        if(empty($str)) return false;
        $res = self::findFirst([
            'columns'    => '*',
            'conditions' => 'username = ?1 ',
            'bind'       => [
                1 => $str,
            ]
        ]);

        return $res;
    }


    /**
     * 根据Email获取用户基本信息
     * @param string $str
     * @return \Phalcon\Mvc\Model|bool
     */
    public static function getInfoByEmail(string $str='') {
        if(empty($str)) return false;
        $res = self::findFirst([
            'columns'    => '*',
            'conditions' => 'email = ?1 ',
            'bind'       => [
                1 => $str,
            ]
        ]);

        return $res;
    }


    /**
     * 根据关键词[用户名或邮箱]获取用户信息
     * @param string $str
     *
     * @return bool|\Phalcon\Mvc\Model
     */
    public static function getInfoByKeyword(string $str='') {
        if(empty($str)) return false;

        $res = self::findFirst([
            'columns'    => '*',
            'conditions' => 'username = ?1 OR email = ?2 ',
            'bind'       => [
                1 => $str,
                2 => $str,
            ],
            'order' => 'uid asc'
        ]);

        return $res;
    }



    /**
     * 根据username联合获取管理员信息
     * @param string $str
     * @param bool $check 严格检查adm是否存在
     * @return bool|\Phalcon\Mvc\ModelInterface
     */
    public static function joinAdmInfoByUsername(string $str='', bool $check=false) {
        if(empty($str)) return false;

        $usr = self::class;
        $adm = AdmUser::class;
        $fields = self::getJoinAdmnFields();

        $query = self::query()
            ->columns($fields)
            ->leftJoin($adm, "a.uid = {$usr}.uid", 'a');

        if($check) {
            $query->where("{$usr}.username = :username: AND a.uid>0 ", ['username'=>$str]);
        }else{
            $query->where("{$usr}.username = :username: ", ['username'=>$str]);
        }

        $result = $query->limit(1)->execute();

        return ($result->count()>0) ? $result->getFirst() : false;
    }


    /**
     * 根据username获取管理员信息
     * @param string $str
     * @return bool|\Phalcon\Mvc\ModelInterface
     */
    public static function getAdmByUsername(string $str='') {
        if(empty($str)) return false;

        return self::joinAdmInfoByUsername($str, true);
    }





}