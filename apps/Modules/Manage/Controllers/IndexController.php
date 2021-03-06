<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/10/28
 * Time: 20:12
 * Desc: -index控制器
 */


namespace Apps\Modules\Manage\Controllers;

use Apps\Modules\Manage\Controller;
use Apps\Models\AdmUser;
use Apps\Services\CaptchaService;
use Lkk\Helpers\CommonHelper;

/**
 * Class 后台首页控制器
 * @package Apps\Modules\Manage\Controllers
 */
class IndexController extends Controller {


    public function initialize () {
        parent::initialize();

        $this->setHeaderSeo('管理后台', '关键词', '描述');

        //视图变量
        $this->view->setVars([
            'headerSeo' => $this->headerSeo,
        ]);

    }


    /**
     * @title -管理后台首页
     * @desc  -管理后台首页
     */
    public function index00Action(){
        //视图变量
        $this->view->setVars([
            'mainUrl' => makeUrl('manage/index/main'),
            'menuUrl' => makeUrl('manage/menu/authlist'),
            'logoutUrl' => makeUrl('manage/index/logout'),
        ]);

        //设置静态资源
        $this->assets->addCss('statics/css/adm-tab.css');
        $this->assets->addJs('statics/js/ace-elements.min.js');
        $this->assets->addJs('statics/js/ace.min.js');
        $this->assets->addJs('statics/js/lkkTabMenu.js');

        return null;
    }


    /**
     * @title -管理后台首页
     * @desc  -管理后台首页
     */
    public function indexAction(){
        //视图变量
        $this->view->setVars([
            'siteUrl' => getSiteUrl(),
            'mainUrl' => makeUrl('manage/index/main'),
            'menuUrl' => makeUrl('manage/menu/authlist'),
            'logoutUrl' => makeUrl('manage/index/logout'),
        ]);

        return null;
    }


    /**
     * @title -管理后台登录页
     * @desc  -管理后台登录页
     */
    public function loginAction() {
        $uid = $this->getLoginUid();
        if($uid>0) return $this->response->redirect(getConf('rbac')->managerDefautlAction);

        //视图变量
        $this->view->setVars([
            'siteUrl' => getSiteUrl(),
            'saveUrl' => makeUrl('manage/index/loginsave'),
            'captchaUrl' => makeUrl('common/captcha/create'),

            //Cross-Site Request Forgery (CSRF)
            'tokenKey' => getConf('site','csrfToken'),
            'tokenVal' => $this->makeCsrfToken(__CLASS__),

            'year' => date('Y'),
            'system' => KSERVER_NAME,
        ]);

        return null;
    }


    /**
     * @title -后台登录保存
     * @desc  -后台登录保存
     */
    public function loginSaveAction() {
        //Csrf检查
        if(!$this->validateCsrfToken('', __CLASS__)) {
            return $this->fail('参数错误,请刷新页面');
        }

        $loginName = $this->getPost('loginName');
        $password = $this->getPost('password');
        $verifyCode = $this->getPost('verifyCode');
        $veriEncode = $this->getPost('veriEncode');
        $remember = intval($this->getPost('remember')); //24小时

        if(empty($verifyCode) || empty($veriEncode)) {
            return $this->fail(20103);
        }

        $captchaServ = new CaptchaService();
        $chkCapt = $captchaServ->validateCode($verifyCode, $veriEncode);
        if(!$chkCapt) {
            return $this->fail($captchaServ->error());
        }

        $this->userService->setRemember($remember);
        $admn = $this->userService->managerLogin($loginName, $password);
        if(!$admn) {
            return $this->fail($this->userService->error());
        }else{
            //$this->userService->makeManagerSession($admn);
            $rbacCnf = getConf('rbac');
            $data = [
                'username' => $loginName,
                'avatar' => '',
                'url' => makeUrl($rbacCnf->managerDefautlAction),
            ];

            return $this->success($data);
        }
    }



    /**
     * @title -管理后台退出
     * @desc  -管理后台退出
     */
    public function logoutAction() {
        $uid = $this->getLoginUid();
        if($uid) {
            $res = $this->userService->managerLogout($uid);
        }

        $loginUrl = makeUrl(getConf('rbac')->managerAuthGateway);
        if($this->request->isAjax()) {
            $data = [
                'loginUrl' => $loginUrl,
            ];
            return $this->success($data);
        }

        return $this->alert('退出成功','success', $loginUrl, 2);
    }


    /**
     * @title -后台主页
     * @desc  -后台主页
     */
    public function mainAction() {


    }


    /**
     * @title -忘记密码页
     * @desc  -忘记密码
     */
    public function forgetpwdAction() {

    }



}