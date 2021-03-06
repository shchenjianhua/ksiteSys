<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/9/17
 * Time: 10:20
 * Desc: -
 */


namespace Kengine;

use Kengine\LkkVolt;
use Kengine\Server\LkkServer;
use Lkk\Helpers\CommonHelper;
use Lkk\Helpers\DirectoryHelper;
use Lkk\Phalwoo\Phalcon\Mvc\Router as PwRouter;
use Lkk\Phalwoo\Phalcon\Mvc\View as PwView;
use Lkk\Phalwoo\Server\AutoReload;
use Phalcon\Loader;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;
use Phalcon\Mvc\View;

class Engine {

    /**
     * 初始化
     */
    public static function init() {
        $comConf = getConf('common');

        //开启调试
        if($comConf['debug']) {
            define('SYSDEBUG', true);
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

        }else{
            define('SYSDEBUG', false);
            error_reporting(0); //关闭所有PHP错误报告
            ini_set('display_errors', 0); //禁止把错误输出到页面
        }

        //设置时区
        date_default_timezone_set($comConf['timezone']);
        mb_substitute_character('none');

        register_shutdown_function('\Lkk\Helpers\CommonHelper::errorHandler', PHPERRLOG);

        self::defineAppConstant();
        self::loadNamespaces();
        self::setRouter();

        //加载xhprof类库
        require WWWDIR. 'monitor/xhprof/xhprof_lib/utils/xhprof_lib.php';
        require WWWDIR. 'monitor/xhprof/xhprof_lib/utils/xhprof_runs.php';

    }


    /**
     * 定义项目应用常量
     */
    public static function defineAppConstant() {
        //定义项目URL相关常量
        $siteConf = getConf('site');
        $url = $siteConf['sourceFullUrl'] ? getSiteUrl() : '/';

        define('SITE_URL',      $url );
        define('HTML_URL',      $url .'html'     . DS ); //url-html html生成目录
        define('STATIC_URL',    $url .'statics'  . DS ); //url-static 静态资源目录
        define('UPLOAD_URL',    $url .'upload'   . DS ); //url-upload 上传资源目录
        define('CSS_URL',       $url .'statics/css'   . DS ); //url-css css资源目录
        define('JS_URL',        $url .'statics/js'    . DS ); //url-js js资源目录

    }


    /**
     * 载入各模块的命名空间
     * @return bool
     */
    public static function loadNamespaces() {
        $loader = new Loader();
        $workNamespaces = [
            'Apps\Modules'      => MODULDIR,
            'Apps\Models'       => MODELDIR,
            'Apps\Services'     => APPSDIR . 'Services/',
        ];

        //加载各模块目录
        $allmodules = getConf('modules');
        foreach ($allmodules as $name=>$module) {
            $moduleClassName = substr($module['className'], 0, strrpos ($module['className'],'\\'));
            $modulePath = str_replace('/Module.php', '', $module['path']);

            if('cli' ==$name) {
                $moduleNamespaces = [
                    "{$moduleClassName}" => "{$modulePath}",
                    "{$moduleClassName}\Tasks" => "{$modulePath}/Tasks/",
                ];
            }else{
                $moduleNamespaces = [
                    "{$moduleClassName}" => "{$modulePath}",
                    "{$moduleClassName}\Controllers" => "{$modulePath}/Controllers/",
                ];
            }

            $workNamespaces = array_merge($workNamespaces, $moduleNamespaces);
        }

        $loader->registerNamespaces($workNamespaces);
        $loader->register();
        return true;
    }


    /**
     * 设置路由器
     * @return mixed
     */
    public static function setRouter() {
        static $router;
        if(is_null($router)) {
            $router = new PwRouter();

            //默认路由
            $defaultModule = getConf('site', 'defaultModule', 'home');
            $defaultNamespace = '/';
            $router->setDefaultModule($defaultModule);
            $router->setDefaultController(getConf('site', 'defaultController', 'index'));
            $router->setDefaultAction(getConf('site', 'defaultAction', 'index'));

            $allmodules = getConf('modules');
            foreach ($allmodules as $module => $options) {
                $module = strtolower($module);
                $namespace = preg_replace('/Module$/', 'Controllers', $options["className"]);
                if($defaultModule == $module) $defaultNamespace = $namespace;

                $router->add('/'.$module.'/:params', [
                    'namespace' => $namespace,
                    'module' => $module,
                    'controller' => 'index',
                    'action' => 'index',
                    'params' => 1
                ])->setName($module);
                $router->add('/'.$module.'/:controller/:params', [
                    'namespace' => $namespace,
                    'module' => $module,
                    'controller' => 1,
                    'action' => 'index',
                    'params' => 2
                ]);
                $router->add('/'.$module.'/:controller/:action/:params', [
                    'namespace' => $namespace,
                    'module' => $module,
                    'controller' => 1,
                    'action' => 2,
                    'params' => 3
                ]);

                //载入各模块的路由组设置
                $rouGroups = new Group([
                    'module' => $module,
                ]);

                //设置模块别名
                $rouGroups->setPrefix('/' . (isset($options['alias']) ? $options['alias'] : $module));
                $rouGroups->add('/((?!\d)\w+)/((?!\d)\w+)/:params', [
                    'controller' => 1, //非数字开头的\w, \w is [a-zA-Z_0-9]
                    'action' => 2,
                    'params' => 3
                ]);

                $routesClassName = pathinfo($options['className'])['dirname'].'\\Routes';
                if (class_exists($routesClassName)) {
                    $routesClass = new $routesClassName();
                    $routesClass->add($rouGroups);
                }

                //挂载模块路由组
                $router->mount($rouGroups);
            }

            //首页
            $router->add('/', [
                'namespace' => $defaultNamespace,
                'module' => $defaultModule,
                'controller' => 'index',
                'action' => 'index',
            ])->setName('default');

            //处理结尾额外的斜杆
            $router->removeExtraSlashes(true);
        }

        //TODO 以下交给具体onRequest去处理
        //$di->setShared('router', $router);

        return $router;
    }


    /**
     * 设置视图
     * @param string $moduleName 模块名
     * @param object $di DI
     * @return PwView|null
     */
    public static function setModuleViewer($moduleName, $di) {
        $view = null;
        if(!is_string($moduleName) || empty($moduleName) || !is_object($di)) {
            return $view;
        }

        $viewConf = getConf('view')->toArray();
        $compPath = RUNTDIR . 'volt/';
        if(!file_exists($compPath)) {
            DirectoryHelper::mkdirDeep($compPath);
        }

        $view = new PwView();
        if(in_array($moduleName, $viewConf['denyModules'])) {
            //设置渲染等级
            $view->setRenderLevel(View::LEVEL_NO_RENDER);
            $view->disable();
        }else{
            //视图模板目录
            $viewpath = APPSDIR . 'Views/' . getConf('common','theme') . "/{$moduleName}/";
            //$di->setShared('assets', 'Phalcon\Assets\Manager');
            $di->setShared('assets', 'Lkk\Phalwoo\Phalcon\Assets\Manager');
            $view->setViewsDir($viewpath);
            $view->registerEngines([
                '.php' => function($view) use($compPath, $di) {
                    $volt = new LkkVolt($view);
                    $volt->setOptions([
                        //模板缓存目录
                        'compiledPath' => $compPath,
                        //编译后的扩展名
                        'compiledExtension' => '',
                        //编译分隔符
                        'compiledSeparator' => '%',
                    ]);

                    //添加自定义模板函数
                    $volt->extendFuncs();
                    $volt->setDI($di);

                    $compiler = $volt->getCompiler();
                    $compiler->setDI($di);

                    return $volt;
                }
            ]);
            //$view->setDI($di);
        }

        return $view;
    }


    /**
     * 运行web应用
     */
    public static function runWebApp() {
        self::init();

        LkkServer::parseCommands();

        $conf = getConf('server')->toArray();

        //开启热更新
        if($conf['server_reload'] && true) {
            $pid = getmypid();
            $res = self::openReloadCodesProcess(['pid'=>$pid,'isChild'=>1]);
            if($res!=-1) {
                echo "open reloadCodesProcess sucess\r\n";
            }
        }

        LkkServer::instance()->setConf($conf)->run();
    }


    /**
     * 运行cli应用
     */
    public static function runCliApp() {
        self::init();

        //DI容器
        $di = LkkCmponent::cliDi();

        $app = new LkkConsole($di);

        //注册各模块
        $moduleConf = getConf('modules')->toArray();
        $app->registerModules($moduleConf);

        self::loadNamespaces();

        //设置事件管理器
        $eventsManager = $di->get('eventsManager');
        $app->setEventsManager($eventsManager);

        //分发器,设置默认命名空间
        $dispatcher = $di->get('dispatcher');
        $dispatcher->setDefaultNamespace('Apps\Modules\Cli\Tasks');
        $di->setShared('dispatcher', $dispatcher);
        
        // URL设置
        $di->setShared('url', LkkCmponent::url());

        //数据库-主从
        $dbMaster = LkkCmponent::syncDbMaster('cli');
        $dbSlave = LkkCmponent::syncDbSlave('cli');
        $di->setShared('dbMaster', $dbMaster);
        $di->setShared('dbSlave', $dbSlave);

        //crypt
        $di->setShared('crypt', LkkCmponent::crypt());

        //缓存服务
        $di->setShared('cache', LkkCmponent::siteCache());

        //注入app,以便actioin里面访问
        $di->setShared('app', $app);
        $app->setDI($di);

        //处理命令行应用的参数
        //例如 php private/cli.php main main
        //$argc = $_SERVER['argc'];
        //$argv = $_SERVER['argv'];
        global $argc, $argv;
        $arguments = ['module' => 'cli'];
        foreach ($argv as $k => $arg) {
            if ($k == 1) {
                $arguments['task'] = $arg;
            } elseif ($k == 2) {
                $arguments['action'] = $arg;
            } elseif ($k >= 3) {
                $arguments['params'][] = $arg;
            }
        }

        if(!isset($arguments['task'])) $arguments['task'] = 'main';
        if(!isset($arguments['action'])) $arguments['action'] = 'main';

        global $cliArguments;
        $cliArguments= $arguments;

        try {
            $app->handle($arguments);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
            exit(255);
        }

    }



    /**
     * 开启代码热更新进程
     * @param array $params
     * @return int
     */
    public static function openReloadCodesProcess($params=[]) {
        $tmp = [];
        foreach ($params as $k=>$v) {
            if(is_numeric($k)) {
                $tmp[] = trim($v);
            }else{
                $tmp[] = $k.'='. trim($v);
            }
        }
        $params = implode(' ', $tmp);

        $file = BINDIR .'reload.php';
        $cmd = "php {$file} {$params} &";

        $res = pclose(popen("{$cmd}", 'r'));
        return $res;
    }


    /**
     * 根据uri获取模块名
     * @param string $str
     * @return string
     */
    public static function getModuleNameByUri($str = '') {
        $default = getConf('site', 'defaultModule', 'home');
        if(empty($str)) return $default;

        $str = preg_replace('/\/+/', '/', str_replace('\\', '/', $str));
        $arr = array_filter(explode('/', $str));
        if($arr) $arr = array_values($arr);

        $num = count($arr);
        if($num==0) {
            $res = $default;
        }else{
            $res = $arr[0];
        }

        unset($arr, $str);
        return $res;
    }



}